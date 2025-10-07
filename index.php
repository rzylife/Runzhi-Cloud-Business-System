<?php
require_once 'config.php';
$website_name = defined('WEBSITE_NAME') ? WEBSITE_NAME : '润知云业务系统';

// 获取主页英雄配置
$db = getDB();
$stmt = $db->prepare("SELECT * FROM homepage_hero WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$heroConfig = $stmt->fetch();

// 如果没有配置，使用默认值
if (!$heroConfig) {
    $heroConfig = [
        'image_path' => 'assets/default-hero.jpg',
        'video_path' => null,
        'title' => $website_name,
        'subtitle' => '提供极致体验的企业上云服务，拥有安全有效的解决方案，为您云上旅程保驾护航',
        'button_text' => '立即体验',
        'button_link' => '#products'
    ];
}

// 获取产品列表（所有用户都可查看）
$stmt = $db->prepare("SELECT * FROM products WHERE is_active = 1 ORDER BY price_points ASC");
$stmt->execute();
$products = $stmt->fetchAll();

// 获取公告（所有用户都可查看）
$stmt = $db->prepare("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$announcements = $stmt->fetchAll();

// 检查用户是否已登录
$is_logged_in = isset($_SESSION['user_id']);
$user_points = 0;
$is_admin = false;
$hasSignedIn = false;
$consecutiveDays = 0;
$signInPoints = SIGN_IN_POINTS;

if ($is_logged_in) {
    // 获取用户完整信息
    $stmt = $db->prepare("SELECT id, username, points, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user) {
        $user_points = $user['points'] ?? 0;
        $is_admin = (bool)($user['is_admin'] ?? false);
        
        // 检查签到状态
        $today = date('Y-m-d');
        $stmt = $db->prepare("SELECT id FROM sign_ins WHERE user_id = ? AND sign_date = ?");
        $stmt->execute([$_SESSION['user_id'], $today]);
        $hasSignedIn = (bool)$stmt->fetch();
        
        // 检查连续签到天数
        $stmt = $db->prepare("
            SELECT COUNT(*) as consecutive_days 
            FROM sign_ins 
            WHERE user_id = ? 
            AND sign_date >= DATE_SUB(?, INTERVAL 6 DAY)
            AND sign_date <= ?
            ORDER BY sign_date DESC
        ");
        $stmt->execute([$_SESSION['user_id'], $today, $today]);
        $result = $stmt->fetch();
        $consecutiveDays = $result ? $result['consecutive_days'] : 0;
        $signInPoints = SIGN_IN_POINTS + ($consecutiveDays >= MAX_CONSECUTIVE_DAYS ? CONSECUTIVE_BONUS : 0);
    } else {
        // 用户不存在，清理会话
        session_destroy();
        $is_logged_in = false;
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($website_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --secondary-gradient: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
            --light-blue: #e3f2fd;
            --white: #ffffff;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 0.8rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.2rem;
            color: #4361ee;
        }
        
        .nav-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            font-size: 0.9rem;
        }
        
        .nav-menu a {
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .nav-menu a:hover {
            color: #4361ee;
            text-decoration: underline;
        }
        
        .auth-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn-login {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        .btn-register {
            background: var(--secondary-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.4);
        }
        
        /* 英雄区域 - 支持图片和视频 */
        .hero-section {
            position: relative;
            height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        
        .hero-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        
        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            max-width: 800px;
            padding: 0 2rem;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .hero-btns {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-primary-large {
            background: var(--primary-gradient);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-primary-large:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-secondary-large {
            background: transparent;
            color: white;
            border: 2px solid white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-secondary-large:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,255,255,0.2);
        }
        
        .features-section {
            padding: 3rem 2rem;
            background: white;
        }
        
        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #495057;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .feature-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            justify-content: center;
        }
        
        .feature-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            width: 280px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .feature-desc {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .products-section {
            padding: 3rem 2rem;
            background: #f8f9fa;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .products-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #495057;
        }
        
        .points-display {
            background: var(--primary-gradient);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .product-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1rem;
            border-bottom: 2px solid #4361ee;
        }
        
        .product-body {
            padding: 1.5rem;
        }
        
        .product-name {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .product-desc {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: #4361ee;
            margin: 0.5rem 0;
        }
        
        .btn-buy {
            background: var(--primary-gradient);
            color: white;
            width: 100%;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn-buy:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        .btn-require-login {
            background: #6c757d;
            color: white;
            width: 100%;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: 600;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-require-login:hover {
            background: #5a6268;
            transform: scale(1.02);
        }
        
        .why-choose-section {
            padding: 3rem 2rem;
            background: white;
            margin-top: 2rem;
        }
        
        .sidebar-section {
            padding: 3rem 2rem;
            background: white;
        }
        
        .sidebar-cards {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .sidebar-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }
        
        .sidebar-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .sidebar-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .signin-card {
            text-align: center;
        }
        
        .signin-points {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 1rem 0;
        }
        
        .signin-btn {
            background: var(--primary-gradient);
            color: white;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .signin-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.5);
        }
        
        .signed-in {
            color: #28a745;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .announcement-item {
            background: #f8f9fa;
            border-left: 4px solid #4361ee;
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 0 8px 8px 0;
            transition: all 0.2s ease;
        }
        
        .announcement-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .announcement-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        
        .announcement-content {
            font-size: 0.9rem;
            color: #6c757d;
            line-height: 1.4;
        }
        
        .no-announcements {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
        }
        
        .redeem-form .form-control {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
        }
        
        .redeem-form .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .redeem-btn {
            background: var(--primary-gradient);
            border: none;
            padding: 12px;
            border-radius: 25px;
            font-weight: 600;
            margin-top: 10px;
            width: 100%;
        }
        
        .consecutive-badge {
            display: inline-block;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .footer {
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 1.5rem 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .footer a {
            color: #4361ee;
            text-decoration: none;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }
        
        .footer a:hover {
            color: #3f37c9;
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem 1rem;
            }
            
            .nav-menu {
                display: none;
            }
            
            .hero-section {
                height: 400px;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .hero-btns {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .products-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .products-title {
                font-size: 1.5rem;
            }
            
            .feature-cards {
                gap: 1rem;
            }
            
            .feature-card {
                width: 100%;
                max-width: 300px;
            }
            
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .auth-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn-login, .btn-register {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <div class="nav-logo">
                <i class="fas fa-cloud"></i>
                <?php echo htmlspecialchars($website_name); ?>
            </div>
            <div class="nav-menu">
                <a href="downloads.php">下载中心</a>
                <a href="announcements.php">公告中心</a>
                <a href="#">解决方案</a>
                <a href="#">网站资讯</a>
                <a href="#">帮助中心</a>
            </div>
            <div class="auth-buttons">
                <?php if ($is_logged_in): ?>
                    <a href="profile.php" class="btn-login">
                        <i class="fas fa-user me-1"></i>个人中心
                    </a>
                    <?php if ($is_admin): ?>
                        <a href="admin.php" class="btn-register">
                            <i class="fas fa-cog me-1"></i>管理后台
                        </a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-register">
                        <i class="fas fa-sign-out-alt me-1"></i>退出
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt me-1"></i>登录
                    </a>
                    <a href="register.php" class="btn-register">
                        <i class="fas fa-user-plus me-1"></i>注册
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <div class="hero-section">
        <?php if (!empty($heroConfig['video_path']) && file_exists($heroConfig['video_path'])): ?>
            <!-- 视频背景 -->
            <video class="hero-video" autoplay muted loop playsinline>
                <source src="<?php echo htmlspecialchars($heroConfig['video_path']); ?>" type="video/mp4">
                您的浏览器不支持视频标签。
            </video>
        <?php else: ?>
            <!-- 图片背景 -->
            <img src="<?php echo file_exists($heroConfig['image_path']) ? htmlspecialchars($heroConfig['image_path']) : 'assets/default-hero.jpg'; ?>" 
                 alt="英雄图片" class="hero-image">
        <?php endif; ?>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title"><?php echo htmlspecialchars($heroConfig['title']); ?></h1>
            <p class="hero-subtitle"><?php echo htmlspecialchars($heroConfig['subtitle']); ?></p>
            <div class="hero-btns">
                <a href="<?php echo htmlspecialchars($heroConfig['button_link']); ?>" class="btn-primary-large">
                    <?php echo htmlspecialchars($heroConfig['button_text']); ?>
                </a>
                <a href="#features" class="btn-secondary-large">了解更多</a>
            </div>
        </div>
    </div>

    <!-- 特色功能区域 -->
    <div class="features-section" id="features">
        <h2 class="section-title">我们的优势</h2>
        <div class="feature-cards">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="feature-title">在线客服 极速响应</h3>
                <p class="feature-desc">提交工单回复时间1024小时以内，7×24小时专业支持</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-tag"></i>
                </div>
                <h3 class="feature-title">产品特惠 轻松上云</h3>
                <p class="feature-desc">襄阳纯v6 2C2G3M 29.9元/年 续费同价，新用户专享</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <h3 class="feature-title">推广赚佣 轻松赚钱</h3>
                <p class="feature-desc">每笔推荐均可赚钱（现金返佣），邀请好友最高返现50%</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3 class="feature-title">弹性计算 管理简单</h3>
                <p class="feature-desc">云控制台、弹性配置，灵活管理，一键部署应用</p>
            </div>
        </div>
    </div>

    <!-- 为什么选择板块 -->
    <div class="why-choose-section">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h2 style="font-size: 2rem; font-weight: 700; color: #495057; margin-bottom: 0.5rem;">为什么选择<?php echo htmlspecialchars($website_name); ?></h2>
            <p style="font-size: 1.1rem; color: #6c757d; max-width: 600px; margin: 0 auto;">提供极致体验的企业上云服务，拥有安全有效的解决方案，为您云上旅程保驾护航</p>
        </div>
        
        <div class="row g-4" style="max-width: 1200px; margin: 0 auto;">
            <!-- 弹性计算 -->
            <div class="col-md-6">
                <div class="card h-100" style="border: 1px solid #dee2e6; border-radius: 12px; box-shadow: var(--card-shadow);">
                    <div class="card-body d-flex align-items-start" style="gap: 1rem; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: var(--secondary-gradient); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            <i class="fas fa-sync-alt"></i>
                        </div>
                        <div>
                            <h3 style="font-weight: 600; color: #495057; margin-bottom: 0.5rem;">弹性计算</h3>
                            <p style="color: #6c757d; line-height: 1.4; margin-bottom: 0;">在<?php echo htmlspecialchars($website_name); ?>您可以在几分钟之内快速根据业务需求，可弹性创建与释放云服务器，轻松应对业务的快速变化。</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 多样化配置 -->
            <div class="col-md-6">
                <div class="card h-100" style="border: 1px solid #dee2e6; border-radius: 12px; box-shadow: var(--card-shadow);">
                    <div class="card-body d-flex align-items-start" style="gap: 1rem; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: var(--primary-gradient); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <div>
                            <h3 style="font-weight: 600; color: #495057; margin-bottom: 0.5rem;">多样化配置</h3>
                            <p style="color: #6c757d; line-height: 1.4; margin-bottom: 0;">提供多种类型的实例、操作系统和软件包。各实例中的 CPU、内存、硬盘和带宽可以灵活调整。</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 安全的网络 -->
            <div class="col-md-6">
                <div class="card h-100" style="border: 1px solid #dee2e6; border-radius: 12px; box-shadow: var(--card-shadow);">
                    <div class="card-body d-flex align-items-start" style="gap: 1rem; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #4ecdc4 0%, #44a08d 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <h3 style="font-weight: 600; color: #495057; margin-bottom: 0.5rem;">安全的网络</h3>
                            <p style="color: #6c757d; line-height: 1.4; margin-bottom: 0;">通过云控制台，切实保证您云上资源的安全性。您还可以完全掌控您的私有网络环境配置等。</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 管理简单 -->
            <div class="col-md-6">
                <div class="card h-100" style="border: 1px solid #dee2e6; border-radius: 12px; box-shadow: var(--card-shadow);">
                    <div class="card-body d-flex align-items-start" style="gap: 1rem; padding: 1.5rem;">
                        <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); border-radius: 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div>
                            <h3 style="font-weight: 600; color: #495057; margin-bottom: 0.5rem;">管理简单</h3>
                            <p style="color: #6c757d; line-height: 1.4; margin-bottom: 0;">可以使用云控制台、进行重启等重要操作，这样管理实例就像管理您的计算机一样简单方便。</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 产品展示区域 -->
    <div class="products-section" id="products">
        <div class="products-header">
            <h2 class="products-title">云电脑产品</h2>
            <?php if ($is_logged_in): ?>
                <div class="points-display">
                    <i class="fas fa-coins me-1"></i>积分: <?php echo $user_points; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-header">
                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                </div>
                <div class="product-body">
                    <p class="product-desc"><?php echo htmlspecialchars($product['description']); ?></p>
                    <div class="product-price">
                        <i class="fas fa-coins me-1"></i><?php echo $product['price_points']; ?> 积分 / 
                        <i class="fas fa-calendar-day me-1"></i><?php echo $product['duration_days']; ?>天
                    </div>
                    <?php if ($is_logged_in): ?>
                        <?php if ($user_points >= $product['price_points']): ?>
                            <button class="btn-buy" onclick="purchaseProduct(<?php echo $product['id']; ?>, <?php echo $product['price_points']; ?>)">
                                <i class="fas fa-shopping-cart me-1"></i>立即购买
                            </button>
                        <?php else: ?>
                            <button class="btn-require-login" disabled>
                                <i class="fas fa-times me-1"></i>积分不足
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn-require-login" onclick="showLoginRequired()">
                            <i class="fas fa-lock me-1"></i>请登录购买
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 侧边栏：签到、优惠码、公告 -->
    <div class="sidebar-section">
        <h2 class="section-title">快捷功能</h2>
        <div class="sidebar-cards">
            <!-- 签到区域 -->
            <div class="sidebar-card">
                <h3 class="sidebar-title"><i class="fas fa-calendar-check"></i>每日签到</h3>
                <div class="signin-card">
                    <?php if ($is_logged_in): ?>
                        <?php if (!$hasSignedIn): ?>
                            <div class="signin-points">
                                +<?php echo $signInPoints; ?>
                            </div>
                            <p class="text-muted">今日签到可获得积分奖励</p>
                            <?php if ($consecutiveDays >= MAX_CONSECUTIVE_DAYS - 1): ?>
                                <div class="consecutive-badge">
                                    <i class="fas fa-fire me-1"></i>连续签到奖励！
                                </div>
                            <?php endif; ?>
                            <button class="signin-btn" onclick="signIn()">
                                <i class="fas fa-fingerprint me-2"></i>立即签到
                            </button>
                        <?php else: ?>
                            <div class="signed-in">
                                <i class="fas fa-check-circle me-2"></i>今日已签到
                            </div>
                            <p class="text-muted mt-3">
                                连续签到 <strong><?php echo $consecutiveDays; ?></strong> 天
                            </p>
                            <?php if ($consecutiveDays >= MAX_CONSECUTIVE_DAYS): ?>
                                <div class="consecutive-badge">
                                    <i class="fas fa-trophy me-1"></i>签到达人！
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">每日签到可获得积分奖励</p>
                        <button class="signin-btn" onclick="showLoginRequired()">
                            <i class="fas fa-sign-in-alt me-2"></i>登录后签到
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 优惠码区域 -->
            <div class="sidebar-card">
                <h3 class="sidebar-title"><i class="fas fa-percentage"></i>兑换优惠码</h3>
                <div class="card-body">
                    <?php if ($is_logged_in): ?>
                        <form id="redeemForm" class="redeem-form">
                            <div class="input-group">
                                <input type="text" class="form-control" id="discountCode" placeholder="输入优惠码" required>
                            </div>
                            <button type="submit" class="redeem-btn">
                                <i class="fas fa-gift me-1"></i>兑换积分
                            </button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">输入优惠码即可兑换积分</p>
                        <button class="redeem-btn" onclick="showLoginRequired()">
                            <i class="fas fa-sign-in-alt me-1"></i>登录后兑换
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 公告区域 -->
            <div class="sidebar-card">
                <h3 class="sidebar-title"><i class="fas fa-bullhorn"></i>系统公告</h3>
                <div class="card-body">
                    <?php if (empty($announcements)): ?>
                        <div class="no-announcements">
                            <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">暂无公告</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="announcement-title">
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                </div>
                                <div class="announcement-content">
                                    <?php echo htmlspecialchars(substr($announcement['content'], 0, 80)); ?>...
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 底部版权信息 -->
    <div class="footer">
        <a href="terms.php">服务条款</a>
        <a href="privacy.php">隐私政策</a>
        <span>Copyright © 2020-2025 <?php echo htmlspecialchars($website_name); ?></span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 显示需要登录的提示
        function showLoginRequired() {
            showInfoToast('请先登录才能使用此功能', 'info');
        }
        
        // 签到功能
        function signIn() {
            const btn = document.querySelector('.signin-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>签到中...';
            
            fetch('api/signin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast('签到成功！获得 ' + data.points + ' 积分');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showErrorToast('签到失败：' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-fingerprint me-2"></i>立即签到';
                }
            })
            .catch(error => {
                showErrorToast('网络错误，请重试');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-fingerprint me-2"></i>立即签到';
            });
        }

        // 购买产品
        function purchaseProduct(productId, price) {
            if (!confirm('确认购买此产品吗？\n将消耗 ' + price + ' 积分')) {
                return;
            }
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>购买中...';
            
            fetch('api/purchase.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({product_id: productId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast('购买成功！');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showErrorToast('购买失败：' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-shopping-cart me-1"></i>立即购买';
                }
            })
            .catch(error => {
                showErrorToast('网络错误，请重试');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-shopping-cart me-1"></i>立即购买';
            });
        }

        // 兑换优惠码
        document.getElementById('redeemForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const code = document.getElementById('discountCode').value;
            const btn = this.querySelector('.redeem-btn');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>兑换中...';
            
            fetch('api/redeem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({code: code})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessToast('兑换成功！获得 ' + data.points + ' 积分');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showErrorToast('兑换失败：' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-gift me-1"></i>兑换积分';
                }
            })
            .catch(error => {
                showErrorToast('网络错误，请重试');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-gift me-1"></i>兑换积分';
            });
        });

        // Toast 通知函数
        function showSuccessToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-bg-success border-0 position-fixed';
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            setTimeout(() => toast.remove(), 3000);
        }

        function showErrorToast(message) {
            const toast = document.createElement('div');
            toast.className = 'toast align-items-center text-bg-danger border-0 position-fixed';
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            setTimeout(() => toast.remove(), 3000);
        }
        
        function showInfoToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type === 'info' ? 'primary' : 'warning'} border-0 position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            document.body.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
