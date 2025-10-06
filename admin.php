<?php
require_once 'config.php';
checkAdmin();
$db = getDB();

// 文件大小格式化函数
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

// 处理各种操作
if ($_POST || $_FILES || $_GET) {
    // 网站名称管理
    if (isset($_POST['action']) && $_POST['action'] == 'update_website_name') {
        $website_name = trim($_POST['website_name']);
        if (empty($website_name)) {
            $error = "网站名称不能为空！";
        } else {
            if (setWebsiteName($website_name)) {
                $success = "网站名称更新成功！";
                // 重新加载常量（当前请求中生效）
                if (defined('WEBSITE_NAME')) {
                    // 注意：PHP 常量一旦定义无法修改，但下个请求会生效
                }
            } else {
                $error = "网站名称更新失败，请检查 config.php 文件权限！";
            }
        }
    }

    // 删除产品
    if (isset($_GET['delete_product'])) {
        $productId = $_GET['delete_product'];
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $success = "产品删除成功！";
        header('Location: admin.php');
        exit();
    }
    
    // 删除公告
    if (isset($_GET['delete_announcement'])) {
        $announcementId = $_GET['delete_announcement'];
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$announcementId]);
        $success = "公告删除成功！";
        header('Location: admin.php');
        exit();
    }
    
    // 删除下载文件
    if (isset($_GET['delete_download'])) {
        $downloadId = $_GET['delete_download'];
        $stmt = $db->prepare("SELECT file_path FROM download_files WHERE id = ?");
        $stmt->execute([$downloadId]);
        $file = $stmt->fetch();
        
        if ($file && file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        $stmt = $db->prepare("DELETE FROM download_files WHERE id = ?");
        $stmt->execute([$downloadId]);
        $success = "文件删除成功！";
        header('Location: admin.php');
        exit();
    }
    
    // 减少用户积分
    if (isset($_POST['action']) && $_POST['action'] == 'reduce_points') {
        $user_id = $_POST['user_id'];
        $points = $_POST['points'];
        $reason = $_POST['reason'];
        
        // 检查用户当前积分
        $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $currentUser = $stmt->fetch();
        
        if ($currentUser && $currentUser['points'] >= $points) {
            logPoints($user_id, -$points, '管理员扣除：' . $reason);
            $success = "积分扣除成功！";
        } else {
            $error = "用户积分不足，无法扣除！";
        }
    }
    
    // 修改管理员密码
    if (isset($_POST['action']) && $_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // 验证当前密码
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($current_password, $user['password'])) {
            if ($new_password === $confirm_password) {
                if (strlen($new_password) >= 6) {
                    $hashedPassword = hashPassword($new_password);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                    $success = "密码修改成功！";
                } else {
                    $error = "新密码长度至少6位！";
                }
            } else {
                $error = "两次输入的新密码不一致！";
            }
        } else {
            $error = "当前密码错误！";
        }
    }
    
    // 修改管理员账号（用户名和邮箱）
    if (isset($_POST['action']) && $_POST['action'] == 'change_account') {
        $new_username = trim($_POST['new_username']);
        $new_email = trim($_POST['new_email']);
        
        // 验证用户名和邮箱格式
        if (empty($new_username) || strlen($new_username) < 3) {
            $error = "用户名至少3个字符！";
        } elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = "邮箱格式不正确！";
        } else {
            // 检查用户名是否已被使用（排除当前用户）
            $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$new_username, $new_email, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                $error = "用户名或邮箱已被其他用户使用！";
            } else {
                $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $_SESSION['user_id']]);
                $success = "账号信息修改成功！";
                // 更新会话中的用户名（如果需要）
            }
        }
    }
    
    // 其他原有操作...
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $stmt = $db->prepare("INSERT INTO products (name, description, price_points, duration_days) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price_points'],
                    $_POST['duration_days']
                ]);
                $success = "产品添加成功！";
                break;
                
            case 'add_announcement':
                $stmt = $db->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
                $stmt->execute([$_POST['title'], $_POST['content']]);
                $success = "公告发布成功！";
                break;
                
            case 'add_discount_code':
                $stmt = $db->prepare("INSERT INTO discount_codes (code, points_value, expires_at) VALUES (?, ?, ?)");
                $expires = !empty($_POST['expires']) ? $_POST['expires'] : null;
                $stmt->execute([$_POST['code'], $_POST['points_value'], $expires]);
                $success = "优惠码生成成功！";
                break;
                
            case 'add_points':
                $user_id = $_POST['user_id'];
                $points = $_POST['points'];
                $reason = $_POST['reason'];
                logPoints($user_id, $points, '管理员发放：' . $reason);
                $success = "积分发放成功！";
                break;
                
            case 'add_download':
                if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                    $uploadDir = 'uploads/downloads/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileName = $_FILES['file']['name'];
                    $fileTmpName = $_FILES['file']['tmp_name'];
                    $fileSize = $_FILES['file']['size'];
                    $filePath = $uploadDir . basename($fileName);
                    
                    $counter = 1;
                    $originalFileName = $fileName;
                    while (file_exists($filePath)) {
                        $pathInfo = pathinfo($originalFileName);
                        $fileName = $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
                        $filePath = $uploadDir . $fileName;
                        $counter++;
                    }
                    
                    if (move_uploaded_file($fileTmpName, $filePath)) {
                        $fileSizeFormatted = formatFileSize($fileSize);
                        $stmt = $db->prepare("INSERT INTO download_files (title, description, file_path, file_size, version, category) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([
                            $_POST['title'],
                            $_POST['description'],
                            $filePath,
                            $fileSizeFormatted,
                            $_POST['version'],
                            $_POST['category']
                        ]);
                        $success = "文件上传成功！";
                    } else {
                        $error = "文件上传失败！";
                    }
                } else {
                    $error = "请选择要上传的文件！";
                }
                break;
                
            case 'update_hero':
                $uploadDir = 'uploads/hero/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // 获取当前配置
                $stmt = $db->prepare("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1");
                $stmt->execute();
                $heroConfig = $stmt->fetch();
                if (!$heroConfig) {
                    $stmt = $db->prepare("INSERT INTO homepage_hero (image_path, title, subtitle, button_text, button_link) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute(['assets/default-hero.jpg', '润知云业务系统', '提供极致体验的企业上云服务...', '立即体验', '#products']);
                    $stmt = $db->prepare("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1");
                    $stmt->execute();
                    $heroConfig = $stmt->fetch();
                }
                
                $imagePath = $heroConfig['image_path'];
                if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] == 0) {
                    $fileName = $_FILES['hero_image']['name'];
                    $fileTmpName = $_FILES['hero_image']['tmp_name'];
                    $filePath = $uploadDir . basename($fileName);
                    
                    $counter = 1;
                    $originalFileName = $fileName;
                    while (file_exists($filePath)) {
                        $pathInfo = pathinfo($originalFileName);
                        $fileName = $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
                        $filePath = $uploadDir . $fileName;
                        $counter++;
                    }
                    
                    if (move_uploaded_file($fileTmpName, $filePath)) {
                        $imagePath = $filePath;
                    }
                }
                
                $videoPath = $heroConfig['video_path'];
                if (isset($_FILES['hero_video']) && $_FILES['hero_video']['error'] == 0) {
                    $fileName = $_FILES['hero_video']['name'];
                    $fileTmpName = $_FILES['hero_video']['tmp_name'];
                    $filePath = $uploadDir . basename($fileName);
                    
                    $counter = 1;
                    $originalFileName = $fileName;
                    while (file_exists($filePath)) {
                        $pathInfo = pathinfo($originalFileName);
                        $fileName = $pathInfo['filename'] . '_' . $counter . '.' . $pathInfo['extension'];
                        $filePath = $uploadDir . $fileName;
                        $counter++;
                    }
                    
                    if (move_uploaded_file($fileTmpName, $filePath)) {
                        $videoPath = $filePath;
                    }
                }
                
                $stmt = $db->prepare("UPDATE homepage_hero SET image_path = ?, video_path = ?, title = ?, subtitle = ?, button_text = ?, button_link = ? WHERE id = ?");
                $stmt->execute([
                    $imagePath,
                    $videoPath,
                    $_POST['hero_title'],
                    $_POST['hero_subtitle'],
                    $_POST['hero_button_text'],
                    $_POST['hero_button_link'],
                    $heroConfig['id']
                ]);
                $success = "主页英雄配置更新成功！";
                break;
        }
    }
}

// 获取主页英雄配置
$stmt = $db->prepare("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$heroConfig = $stmt->fetch();
if (!$heroConfig) {
    $stmt = $db->prepare("INSERT INTO homepage_hero (image_path, title, subtitle, button_text, button_link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['assets/default-hero.jpg', '润知云业务系统', '提供极致体验的企业上云服务，拥有安全有效的解决方案，为您云上旅程保驾护航', '立即体验', '#products']);
    $stmt = $db->prepare("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $heroConfig = $stmt->fetch();
}

// 获取统计数据
$stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$totalUsers = $stmt->fetch()['total_users'];

$stmt = $db->prepare("SELECT COUNT(*) as total_products FROM products");
$stmt->execute();
$totalProducts = $stmt->fetch()['total_products'];

$stmt = $db->prepare("SELECT SUM(points) as total_points FROM users");
$stmt->execute();
$totalPoints = $stmt->fetch()['total_points'] ?? 0;

// 获取用户列表
$stmt = $db->prepare("SELECT id, username, email FROM users");
$stmt->execute();
$users = $stmt->fetchAll();

// 获取产品列表
$stmt = $db->prepare("SELECT * FROM products ORDER BY created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll();

// 获取下载文件列表
$stmt = $db->prepare("SELECT * FROM download_files ORDER BY created_at DESC");
$stmt->execute();
$downloads = $stmt->fetchAll();

// 获取公告列表
$stmt = $db->prepare("SELECT * FROM announcements ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();

// 获取当前管理员信息
$stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentAdmin = $stmt->fetch();

// 获取当前网站名称
$currentWebsiteName = defined('WEBSITE_NAME') ? WEBSITE_NAME : '润知云业务系统';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentWebsiteName); ?> - 管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --secondary-gradient: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
            --card-shadow: 0 4px 20px rgba(0,0,0,0.1);
            --hover-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        body {
            background: linear-gradient(135deg, #f8f9ff 0%, #e6e9ff 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: 0.5px;
        }
        
        .stats-section {
            margin-bottom: 2rem;
        }
        
        .stat-card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        
        .stat-users .stat-icon { background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%); color: white; }
        .stat-products .stat-icon { background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%); color: white; }
        .stat-points .stat-icon { background: linear-gradient(135deg, #f72585 0%, #b5179e 100%); color: white; }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        
        .admin-card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .admin-card .card-header {
            background: white;
            padding: 1.2rem 1.5rem;
            border-bottom: 2px solid var(--primary-gradient);
            font-weight: 600;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-card .card-body {
            padding: 1.5rem;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        
        .btn-admin {
            background: var(--primary-gradient);
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        .btn-success-admin {
            background: var(--secondary-gradient);
            border: none;
            padding: 10px;
            border-radius: 25px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn-success-admin:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.4);
        }
        
        .btn-danger-admin {
            background: linear-gradient(135deg, #f72585 0%, #b5179e 100%);
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            color: white;
            text-decoration: none;
        }
        
        .btn-danger-admin:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.4);
        }
        
        .btn-warning-admin {
            background: linear-gradient(135deg, #ff9e00 0%, #ff6b00 100%);
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            color: white;
            text-decoration: none;
        }
        
        .btn-warning-admin:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(255, 158, 0, 0.4);
        }
        
        .table-container {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .table-hover tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-active {
            background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .status-inactive {
            background: #e9ecef;
            color: #6c757d;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #f72585 0%, #b5179e 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .success-alert {
            background: var(--secondary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border-color: #dee2e6;
        }
        
        .form-control, .form-select {
            border-radius: 12px;
            padding: 10px 15px;
            border: 2px solid #e9ecef;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-gradient);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .download-file {
            font-weight: 500;
            color: var(--primary-gradient);
            text-decoration: none;
        }
        
        .download-file:hover {
            text-decoration: underline;
        }
        
        .hero-preview {
            position: relative;
            width: 100%;
            height: 200px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        
        .hero-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .hero-preview .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 1rem;
            }
            
            .form-section .row > [class*="col-"] {
                margin-bottom: 1rem;
            }
            
            .btn-admin {
                width: 100%;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: var(--primary-gradient);">
        <div class="container">
            <a class="navbar-brand" href="admin.php">
                <i class="fas fa-cloud me-2"></i><?php echo htmlspecialchars($currentWebsiteName); ?>管理后台
            </a>
            <div class="navbar-nav ms-auto align-items-center">
                <a class="nav-link text-white" href="index.php">
                    <i class="fas fa-home me-1"></i>返回前台
                </a>
                <a class="nav-link text-white" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>退出
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- 成功提示 -->
        <?php if (isset($success)): ?>
            <div class="success-alert">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <h5 class="mb-0"><?php echo htmlspecialchars($success); ?></h5>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-custom" style="border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
                <i class="fas fa-exclamation-circle fa-2x"></i>
                <div>
                    <h5 class="mb-0"><?php echo htmlspecialchars($error); ?></h5>
                </div>
            </div>
        <?php endif; ?>

        <!-- 统计卡片 -->
        <div class="stats-section">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card stat-card stat-users">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?php echo $totalUsers; ?></div>
                            <div class="stat-label">总用户数</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card stat-products">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="stat-number"><?php echo $totalProducts; ?></div>
                            <div class="stat-label">产品数量</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card stat-points">
                        <div class="card-body text-center">
                            <div class="stat-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                            <div class="stat-number"><?php echo number_format($totalPoints); ?></div>
                            <div class="stat-label">系统总积分</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- 左侧：管理功能 -->
            <div class="col-lg-8">
                <!-- 账号密码修改 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user-cog"></i>账号密码管理
                    </div>
                    <div class="card-body">
                        <!-- 修改密码 -->
                        <div class="form-section mb-3">
                            <h6 class="mb-3">修改密码</h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">当前密码</label>
                                        <input type="password" class="form-control" name="current_password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">新密码</label>
                                        <input type="password" class="form-control" name="new_password" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">确认新密码</label>
                                        <input type="password" class="form-control" name="confirm_password" required>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-admin">
                                            <i class="fas fa-key me-1"></i>修改密码
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- 修改账号信息 -->
                        <div class="form-section">
                            <h6 class="mb-3">修改账号信息</h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_account">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">新用户名</label>
                                        <input type="text" class="form-control" name="new_username" 
                                               value="<?php echo htmlspecialchars($currentAdmin['username']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">新邮箱</label>
                                        <input type="email" class="form-control" name="new_email" 
                                               value="<?php echo htmlspecialchars($currentAdmin['email']); ?>" required>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-admin">
                                            <i class="fas fa-user-edit me-1"></i>修改账号
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 网站名称管理 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-globe"></i>网站名称管理
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_website_name">
                                <div class="mb-3">
                                    <label class="form-label">网站名称</label>
                                    <input type="text" class="form-control" name="website_name" 
                                           value="<?php echo htmlspecialchars($currentWebsiteName); ?>" 
                                           placeholder="输入网站名称" required>
                                </div>
                                <button type="submit" class="btn btn-admin">
                                    <i class="fas fa-save me-1"></i>保存网站名称
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 主页英雄配置 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-image"></i>主页英雄配置
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_hero">
                                
                                <!-- 英雄图片预览 -->
                                <div class="hero-preview">
                                    <?php if (file_exists($heroConfig['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($heroConfig['image_path']); ?>" alt="英雄图片">
                                    <?php else: ?>
                                        <div class="overlay">默认英雄图片</div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- 图片上传 -->
                                <div class="mb-3">
                                    <label class="form-label">上传英雄图片</label>
                                    <input type="file" class="form-control" name="hero_image" accept="image/*">
                                    <small class="text-muted">支持 JPG、PNG、GIF 格式，推荐尺寸 1920x1080</small>
                                </div>
                                
                                <!-- 视频上传 -->
                                <div class="mb-3">
                                    <label class="form-label">上传英雄视频</label>
                                    <input type="file" class="form-control" name="hero_video" accept="video/*">
                                    <small class="text-muted">支持 MP4、WebM、OGG 格式</small>
                                </div>
                                
                                <!-- 标题 -->
                                <div class="mb-3">
                                    <label class="form-label">标题</label>
                                    <input type="text" class="form-control" name="hero_title" 
                                           value="<?php echo htmlspecialchars($heroConfig['title']); ?>" required>
                                </div>
                                
                                <!-- 副标题 -->
                                <div class="mb-3">
                                    <label class="form-label">副标题</label>
                                    <textarea class="form-control" name="hero_subtitle" rows="2" required>
<?php echo htmlspecialchars($heroConfig['subtitle']); ?>
                                    </textarea>
                                </div>
                                
                                <!-- 按钮文本 -->
                                <div class="mb-3">
                                    <label class="form-label">按钮文本</label>
                                    <input type="text" class="form-control" name="hero_button_text" 
                                           value="<?php echo htmlspecialchars($heroConfig['button_text']); ?>" required>
                                </div>
                                
                                <!-- 按钮链接 -->
                                <div class="mb-3">
                                    <label class="form-label">按钮链接</label>
                                    <input type="text" class="form-control" name="hero_button_link" 
                                           value="<?php echo htmlspecialchars($heroConfig['button_link']); ?>" required>
                                    <small class="text-muted">例如：#products 或 https://example.com</small>
                                </div>
                                
                                <button type="submit" class="btn btn-admin">
                                    <i class="fas fa-save me-1"></i>保存配置
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 产品管理 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-server"></i>产品管理
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_product">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">产品名称</label>
                                        <input type="text" class="form-control" name="name" placeholder="输入产品名称" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">积分价格</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                            <input type="number" class="form-control" name="price_points" placeholder="积分" required>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">使用天数</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                                            <input type="number" class="form-control" name="duration_days" placeholder="天数" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">产品描述</label>
                                        <textarea class="form-control" name="description" placeholder="简要描述" rows="1"></textarea>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="submit" class="btn btn-admin w-100">
                                            <i class="fas fa-plus me-1"></i>添加
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- 产品列表 -->
                        <div class="mt-3">
                            <h6 class="mb-3"><i class="fas fa-list me-2"></i>现有产品</h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>产品名称</th>
                                            <th>积分价格</th>
                                            <th>使用天数</th>
                                            <th>状态</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                                <?php if (!empty($product['description'])): ?>
                                                    <div class="text-muted small mt-1"><?php echo htmlspecialchars(substr($product['description'], 0, 30)); ?>...</div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-coins me-1"></i><?php echo $product['price_points']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-calendar-day me-1"></i><?php echo $product['duration_days']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($product['is_active']): ?>
                                                    <span class="status-active">启用</span>
                                                <?php else: ?>
                                                    <span class="status-inactive">禁用</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="admin.php?delete_product=<?php echo $product['id']; ?>" 
                                                   class="btn-danger-admin" 
                                                   onclick="return confirm('确定要删除此产品吗？删除后无法恢复！')">
                                                    <i class="fas fa-trash me-1"></i>删除
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 公告管理 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-bullhorn"></i>公告管理
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <form method="POST">
                                <input type="hidden" name="action" value="add_announcement">
                                <div class="mb-3">
                                    <label class="form-label">公告标题</label>
                                    <input type="text" class="form-control" name="title" placeholder="输入公告标题" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">公告内容</label>
                                    <textarea class="form-control" name="content" placeholder="输入公告详细内容" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-admin">
                                    <i class="fas fa-paper-plane me-1"></i>发布公告
                                </button>
                            </form>
                        </div>
                        
                        <!-- 公告列表 -->
                        <div class="mt-3">
                            <h6 class="mb-3"><i class="fas fa-list me-2"></i>现有公告</h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>标题</th>
                                            <th>发布时间</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($announcements as $announcement): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                                                <div class="text-muted small mt-1">
                                                    <?php echo htmlspecialchars(substr($announcement['content'], 0, 50)); ?>...
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?>
                                            </td>
                                            <td>
                                                <a href="admin.php?delete_announcement=<?php echo $announcement['id']; ?>" 
                                                   class="btn-danger-admin" 
                                                   onclick="return confirm('确定要删除此公告吗？')">
                                                    <i class="fas fa-trash me-1"></i>删除
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 下载文件管理 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-download"></i>下载文件管理
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_download">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">文件标题</label>
                                        <input type="text" class="form-control" name="title" placeholder="输入文件标题" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">版本</label>
                                        <input type="text" class="form-control" name="version" placeholder="如 v1.0.0" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">分类</label>
                                        <select class="form-select" name="category" required>
                                            <option value="Windows客户端">Windows客户端</option>
                                            <option value="Mac客户端">Mac客户端</option>
                                            <option value="Linux客户端">Linux客户端</option>
                                            <option value="文档资料">文档资料</option>
                                            <option value="工具软件">工具软件</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">上传文件</label>
                                        <input type="file" class="form-control" name="file" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">文件描述</label>
                                        <textarea class="form-control" name="description" placeholder="输入文件描述" rows="2"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-admin">
                                            <i class="fas fa-upload me-1"></i>上传文件
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- 下载文件列表 -->
                        <div class="mt-3">
                            <h6 class="mb-3"><i class="fas fa-list me-2"></i>现有文件</h6>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>文件名</th>
                                            <th>版本</th>
                                            <th>分类</th>
                                            <th>大小</th>
                                            <th>下载次数</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($downloads as $download): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($download['file_path']); ?>" 
                                                   class="download-file" target="_blank">
                                                    <?php echo htmlspecialchars($download['title']); ?>
                                                </a>
                                                <div class="text-muted small mt-1">
                                                    <?php echo htmlspecialchars(basename($download['file_path'])); ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($download['version']); ?></td>
                                            <td><?php echo htmlspecialchars($download['category']); ?></td>
                                            <td><?php echo htmlspecialchars($download['file_size']); ?></td>
                                            <td><?php echo $download['download_count']; ?></td>
                                            <td>
                                                <a href="admin.php?delete_download=<?php echo $download['id']; ?>" 
                                                   class="btn-danger-admin" 
                                                   onclick="return confirm('确定要删除此文件吗？')">
                                                    <i class="fas fa-trash me-1"></i>删除
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 优惠码管理 -->
                <div class="card admin-card">
                    <div class="card-header">
                        <i class="fas fa-percentage"></i>优惠码管理
                    </div>
                    <div class="card-body">
                        <div class="form-section">
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="action" value="add_discount_code">
                                <div class="col-md-3">
                                    <label class="form-label">优惠码</label>
                                    <input type="text" class="form-control" name="code" placeholder="输入优惠码" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">积分值</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                        <input type="number" class="form-control" name="points_value" placeholder="积分" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">过期时间</label>
                                    <input type="date" class="form-control" name="expires">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-admin w-100">
                                        <i class="fas fa-magic me-1"></i>生成
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 右侧：用户管理 -->
            <div class="col-lg-4">
                <!-- 积分发放 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-gift"></i>积分发放
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_points">
                            <div class="mb-3">
                                <label class="form-label">选择用户</label>
                                <select class="form-select" name="user_id" required>
                                    <option value="">选择用户</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">积分数量</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                    <input type="number" class="form-control" name="points" placeholder="输入积分数量" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">发放原因</label>
                                <input type="text" class="form-control" name="reason" placeholder="说明发放原因" required>
                            </div>
                            <button type="submit" class="btn btn-success-admin">
                                <i class="fas fa-paper-plane me-2"></i>发放积分
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 扣除积分 -->
                <div class="card admin-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-minus-circle"></i>扣除积分
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="reduce_points">
                            <div class="mb-3">
                                <label class="form-label">选择用户</label>
                                <select class="form-select" name="user_id" required>
                                    <option value="">选择用户</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">扣除积分</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                    <input type="number" class="form-control" name="points" placeholder="输入扣除积分数量" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">扣除原因</label>
                                <input type="text" class="form-control" name="reason" placeholder="说明扣除原因" required>
                            </div>
                            <button type="submit" class="btn btn-warning-admin w-100">
                                <i class="fas fa-minus me-2"></i>扣除积分
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 用户列表 -->
                <div class="card admin-card">
                    <div class="card-header">
                        <i class="fas fa-users"></i>用户列表
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th>用户名</th>
                                        <th>邮箱</th>
                                        <th>积分</th>
                                        <th>角色</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $stmt = $db->prepare("SELECT points FROM users WHERE id = ?");
                                            $stmt->execute([$user['id']]);
                                            $userPoints = $stmt->fetch()['points'];
                                            echo '<span class="badge bg-warning text-dark">' . $userPoints . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
                                            $stmt->execute([$user['id']]);
                                            $isAdmin = $stmt->fetch()['is_admin'];
                                            echo $isAdmin ? '<span class="admin-badge"><i class="fas fa-crown me-1"></i>管理员</span>' : '<span class="text-muted">普通用户</span>';
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 自动隐藏成功提示
        setTimeout(() => {
            const successAlert = document.querySelector('.success-alert');
            if (successAlert) {
                successAlert.style.opacity = '0';
                successAlert.style.transition = 'opacity 0.5s ease';
                setTimeout(() => successAlert.remove(), 500);
            }
        }, 5000);
    </script>
</body>
</html>