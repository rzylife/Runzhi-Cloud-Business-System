<?php
session_start();

// 安装完成标志
$install_complete = false;
$error = '';
$success = '';

// 如果已安装，跳转到首页
if (file_exists('config.php')) {
    include 'config.php';
    if (defined('DB_HOST') && defined('DB_NAME')) {
        header('Location: index.php');
        exit();
    }
}

// 处理安装表单
if ($_POST) {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $website_name = trim($_POST['website_name'] ?? '润知云业务系统');
    $admin_username = trim($_POST['admin_username'] ?? 'admin');
    $admin_email = trim($_POST['admin_email'] ?? 'admin@example.com');
    $admin_password = $_POST['admin_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 验证输入
    if (empty($db_name) || empty($db_user) || empty($admin_username) || empty($admin_password)) {
        $error = "请填写所有必填字段！";
    } elseif ($admin_password !== $confirm_password) {
        $error = "管理员密码两次输入不一致！";
    } elseif (strlen($admin_password) < 6) {
        $error = "管理员密码长度至少6位！";
    } else {
        try {
            // 测试数据库连接
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            // 创建数据库（如果不存在）
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");

            // 创建数据表
            $tables = [
                "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    points INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    last_login TIMESTAMP NULL,
                    is_admin TINYINT DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description TEXT,
                    price_points INT NOT NULL,
                    duration_days INT NOT NULL COMMENT '使用天数',
                    is_active TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS orders (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    product_id INT NOT NULL,
                    points_used INT NOT NULL,
                    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS sign_ins (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    sign_date DATE NOT NULL,
                    points_awarded INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_user_date (user_id, sign_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS discount_codes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    points_value INT NOT NULL,
                    is_used TINYINT DEFAULT 0,
                    used_by INT NULL,
                    used_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL,
                    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS announcements (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(200) NOT NULL,
                    content TEXT NOT NULL,
                    is_active TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS download_files (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(200) NOT NULL,
                    description TEXT,
                    file_path VARCHAR(500) NOT NULL,
                    file_size VARCHAR(50) NOT NULL,
                    version VARCHAR(50) NOT NULL,
                    category VARCHAR(50) NOT NULL,
                    download_count INT DEFAULT 0,
                    is_active TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS homepage_hero (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    image_path VARCHAR(500) NOT NULL DEFAULT 'assets/default-hero.jpg',
                    video_path VARCHAR(500) DEFAULT NULL,
                    title VARCHAR(200) NOT NULL DEFAULT '润知云业务系统',
                    subtitle VARCHAR(500) NOT NULL DEFAULT '提供极致体验的企业上云服务，拥有安全有效的解决方案，为您云上旅程保驾护航',
                    button_text VARCHAR(100) NOT NULL DEFAULT '立即体验',
                    button_link VARCHAR(500) NOT NULL DEFAULT '#products',
                    is_active TINYINT DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

                "CREATE TABLE IF NOT EXISTS points_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    points_change INT NOT NULL,
                    reason VARCHAR(100) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            ];

            foreach ($tables as $table_sql) {
                $pdo->exec($table_sql);
            }

            // 插入默认主页配置
            $stmt = $pdo->prepare("INSERT INTO homepage_hero (title) VALUES (?)");
            $stmt->execute([$website_name]);

            // 创建管理员账户
            $hashedPassword = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, points, is_admin) VALUES (?, ?, ?, 10000, 1)");
            $stmt->execute([$admin_username, $admin_email, $hashedPassword]);

            // 创建示例产品
            $products = [
                ['基础云电脑', '适合日常办公和轻度娱乐', 100, 30],
                ['标准云电脑', '适合开发和中度游戏', 200, 30],
                ['高级云电脑', '适合专业设计和重度游戏', 500, 30],
                ['企业云电脑', '适合团队协作和企业应用', 1000, 30]
            ];

            $stmt = $pdo->prepare("INSERT INTO products (name, description, price_points, duration_days) VALUES (?, ?, ?, ?)");
            foreach ($products as $product) {
                $stmt->execute($product);
            }

            // 创建示例公告
            $announcements = [
                ['系统升级通知', '我们将于今晚进行系统维护升级，预计维护时间为2小时。'],
                ['新功能上线', '新增下载中心功能，用户可以下载客户端和相关文档。'],
                ['优惠活动', '新用户注册即送100积分，邀请好友最高返现50%。']
            ];

            $stmt = $pdo->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
            foreach ($announcements as $announcement) {
                $stmt->execute($announcement);
            }

            // 创建 config.php 文件
            $configContent = "<?php\n";
            $configContent .= "define('WEBSITE_NAME', '" . addslashes($website_name) . "');\n";
            $configContent .= "define('DB_HOST', '" . addslashes($db_host) . "');\n";
            $configContent .= "define('DB_NAME', '" . addslashes($db_name) . "');\n";
            $configContent .= "define('DB_USER', '" . addslashes($db_user) . "');\n";
            $configContent .= "define('DB_PASS', '" . addslashes($db_pass) . "');\n";
            $configContent .= "\n";
            $configContent .= "// 签到积分规则\n";
            $configContent .= "define('SIGN_IN_POINTS', 10);\n";
            $configContent .= "define('MAX_CONSECUTIVE_DAYS', 7);\n";
            $configContent .= "define('CONSECUTIVE_BONUS', 5);\n";
            $configContent .= "\n";
            $configContent .= "// 会话配置\n";
            $configContent .= "session_start();\n";
            $configContent .= "\n";
            $configContent .= "// 数据库连接函数\n";
            $configContent .= "function getDB() {\n";
            $configContent .= "    try {\n";
            $configContent .= "        \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);\n";
            $configContent .= "        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n";
            $configContent .= "        \$pdo->exec(\"SET NAMES utf8mb4\");\n";
            $configContent .= "        return \$pdo;\n";
            $configContent .= "    } catch (PDOException \$e) {\n";
            $configContent .= "        die(\"数据库连接失败: \" . \$e->getMessage());\n";
            $configContent .= "    }\n";
            $configContent .= "}\n";
            $configContent .= "\n";
            $configContent .= "// 密码加密\n";
            $configContent .= "function hashPassword(\$password) {\n";
            $configContent .= "    return password_hash(\$password, PASSWORD_DEFAULT);\n";
            $configContent .= "}\n";
            $configContent .= "\n";
            $configContent .= "// 验证密码\n";
            $configContent .= "function verifyPassword(\$password, \$hash) {\n";
            $configContent .= "    return password_verify(\$password, \$hash);\n";
            $configContent .= "}\n";
            $configContent .= "\n";
            $configContent .= "// 检查登录状态\n";
            $configContent .= "function checkLogin() {\n";
            $configContent .= "    if (!isset(\$_SESSION['user_id'])) {\n";
            $configContent .= "        header('Location: login.php');\n";
            $configContent .= "        exit();\n";
            $configContent .= "    }\n";
            $configContent .= "}\n";
            $configContent .= "\n";
            $configContent .= "// 检查管理员权限\n";
            $configContent .= "function checkAdmin() {\n";
            $configContent .= "    checkLogin();\n";
            $configContent .= "    if (!isset(\$_SESSION['is_admin']) || \$_SESSION['is_admin'] != 1) {\n";
            $configContent .= "        header('Location: index.php');\n";
            $configContent .= "        exit();\n";
            $configContent .= "    }\n";
            $configContent .= "}\n";
            $configContent .= "\n";
            $configContent .= "// 检查登录状态（可选）\n";
            $configContent .= "function checkLoginOptional() {\n";
            $configContent .= "    return isset(\$_SESSION['user_id']);\n";
            $configContent .= "}\n";
            $configContent .= "\n";
            $configContent .= "// 记录积分变动\n";
            $configContent .= "function logPoints(\$user_id, \$points, \$reason) {\n";
            $configContent .= "    \$db = getDB();\n";
            $configContent .= "    \$stmt = \$db->prepare(\"INSERT INTO points_log (user_id, points_change, reason) VALUES (?, ?, ?)\");\n";
            $configContent .= "    \$stmt->execute([\$user_id, \$points, \$reason]);\n";
            $configContent .= "    \n";
            $configContent .= "    // 更新用户积分\n";
            $configContent .= "    \$stmt = \$db->prepare(\"UPDATE users SET points = points + ? WHERE id = ?\");\n";
            $configContent .= "    \$stmt->execute([\$points, \$user_id]);\n";
            $configContent .= "}\n";
            $configContent .= "?>";

            if (file_put_contents('config.php', $configContent)) {
                $success = "安装成功！系统已配置完成。";
                $install_complete = true;
                // 自动跳转到登录页面
                header('Refresh: 3; url=login.php');
            } else {
                $error = "无法创建 config.php 文件，请确保目录有写权限！";
            }

        } catch (PDOException $e) {
            $error = "数据库错误: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "安装错误: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装向导</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .install-card {
            max-width: 600px;
            margin: 0 auto;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .install-header h1 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .install-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .install-body {
            padding: 2rem;
            background: white;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 12px;
            padding: 10px 15px;
            border: 2px solid #e9ecef;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-install {
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            color: white;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .step-indicator {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #6c757d;
        }
        
        .required {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="install-header">
            <h1><i class="fas fa-cloud me-2"></i>系统安装向导</h1>
            <p>请按照以下步骤完成系统安装</p>
        </div>
        
        <div class="install-body">
            <div class="step-indicator">
                <i class="fas fa-database me-2"></i>数据库配置 & 网站设置
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-custom">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$install_complete): ?>
                <form method="POST">
                    <!-- 数据库配置 -->
                    <h5 class="mb-3"><i class="fas fa-database me-2"></i>数据库配置</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库主机 <span class="required">*</span></label>
                        <input type="text" class="form-control" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>" placeholder="通常为 localhost">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库名称 <span class="required">*</span></label>
                        <input type="text" class="form-control" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" placeholder="例如：runzhi_cloud" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库用户名 <span class="required">*</span></label>
                        <input type="text" class="form-control" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" placeholder="数据库用户名" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">数据库密码</label>
                        <input type="password" class="form-control" name="db_pass" value="<?php echo htmlspecialchars($_POST['db_pass'] ?? ''); ?>" placeholder="数据库密码">
                    </div>
                    
                    <!-- 网站配置 -->
                    <h5 class="mb-3 mt-4"><i class="fas fa-globe me-2"></i>网站配置</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">网站名称 <span class="required">*</span></label>
                        <input type="text" class="form-control" name="website_name" value="<?php echo htmlspecialchars($_POST['website_name'] ?? '润知云业务系统'); ?>" placeholder="例如：我的云业务系统" required>
                    </div>
                    
                    <!-- 管理员账户 -->
                    <h5 class="mb-3 mt-4"><i class="fas fa-user-shield me-2"></i>管理员账户</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">管理员用户名 <span class="required">*</span></label>
                        <input type="text" class="form-control" name="admin_username" value="<?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?>" placeholder="管理员用户名" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">管理员邮箱 <span class="required">*</span></label>
                        <input type="email" class="form-control" name="admin_email" value="<?php echo htmlspecialchars($_POST['admin_email'] ?? 'admin@example.com'); ?>" placeholder="管理员邮箱" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">管理员密码 <span class="required">*</span></label>
                        <input type="password" class="form-control" name="admin_password" placeholder="至少6位字符" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">确认密码 <span class="required">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" placeholder="请再次输入密码" required>
                    </div>
                    
                    <button type="submit" class="btn btn-install">
                        <i class="fas fa-cogs me-2"></i>开始安装
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>