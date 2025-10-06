<?php
require_once 'config.php';

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, password, is_admin FROM users WHERE (username = ? OR email = ?) AND is_admin = 1");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = 1;
        
        // 更新最后登录时间
        $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        header('Location: admin.php');
        exit();
    } else {
        $error = "管理员账号或密码错误";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>业务系统登录</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --admin-color: #ff6b6b;
            --admin-secondary: #ee5a24;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --gray: #6c757d;
            --border-color: #dee2e6;
            --shadow: 0 4px 20px rgba(0,0,0,0.05);
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
        
        .header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-text {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--admin-color);
        }
        
        .back-link {
            color: var(--admin-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--admin-secondary);
            text-decoration: underline;
        }
        
        .main-container {
            display: flex;
            flex: 1;
            padding: 2rem;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        
        .left-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .globe-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .globe-image {
            width: 100%;
            max-width: 300px;
            height: auto;
            filter: drop-shadow(0 10px 20px rgba(0,0,0,0.1));
        }
        
        .right-side {
            flex: 1;
            max-width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2rem;
            display: flex;
            flex-direction: column;
        }
        
        .form-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--admin-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--admin-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--gray);
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--admin-color);
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(255, 107, 107, 0.25);
        }
        
        .btn-login {
            background: var(--admin-color);
            color: white;
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            background: var(--admin-secondary);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }
        
        .alert-custom {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border: none;
        }
        
        .additional-links {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .additional-links a {
            color: var(--gray);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .additional-links a:hover {
            color: var(--admin-color);
            text-decoration: underline;
        }
        
        .footer {
            background: white;
            border-top: 1px solid var(--border-color);
            padding: 1rem 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: auto;
        }
        
        .footer a {
            color: var(--admin-color);
            text-decoration: none;
            margin: 0 0.5rem;
            transition: all 0.3s ease;
        }
        
        .footer a:hover {
            color: var(--admin-secondary);
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                padding: 1rem;
            }
            
            .left-side {
                order: 2;
                padding-top: 1rem;
            }
            
            .right-side {
                order: 1;
                max-width: 100%;
            }
            
            .header {
                padding: 0.8rem 1rem;
            }
            
            .logo-text {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- 顶部导航栏 -->
    <div class="header">
        <div class="logo">
            <i class="fas fa-shield-alt" style="color: var(--admin-color); font-size: 1.5rem;"></i>
            <span class="logo-text">业务系统后台登陆</span>
        </div>
        <a href="index.php" class="back-link">回到首页</a>
    </div>

    <!-- 主要内容区域 -->
    <div class="main-container">
        <!-- 左侧：地球图像 -->
        <div class="left-side">
            <div class="globe-container">
                <div class="globe-image">
                    <i class="fas fa-globe-asia fa-8x" style="color: #ff6b6b; opacity: 0.8;"></i>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 100%; height: 100%; display: flex; justify-content: center; align-items: center;">
                        <div style="background: rgba(255,255,255,0.8); padding: 20px; border-radius: 10px; text-align: center;">
                            <i class="fas fa-crown fa-3x" style="color: #ff6b6b; margin-bottom: 10px;"></i>
                            <p style="margin: 0; font-size: 0.9rem; color: #6c757d;">管理员专用</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 右侧：管理员登录表单 -->
        <div class="right-side">
            <div class="form-title">
                <span>管理员登录</span>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="fas fa-exclamation-circle"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">管理员账号</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="请输入管理员账号" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">密码</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="请输入管理员密码" required>
                </div>
                
                <button type="submit" class="btn-login">
                    管理员登录
                </button>
            </form>
            
            <div class="additional-links">
                <a href="login.php">返回普通登录</a>
            </div>
        </div>
    </div>

    <!-- 底部版权信息 -->
    <div class="footer">
        <a href="terms.php">服务条款</a>
        <a href="privacy.php">隐私政策</a>
        <span>Copyright © 2020-2025 星河云</span>
    </div>
</body>
</html>