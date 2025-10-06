<?php
require_once 'config.php';
require_once 'auth.php';
$website_name = defined('WEBSITE_NAME') ? WEBSITE_NAME : '润知云业务系统';

$error = '';
$success = '';

if ($_POST) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 输入验证
    if (empty($username)) {
        $error = "请输入用户名";
    } elseif (empty($email)) {
        $error = "请输入邮箱";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "邮箱格式不正确";
    } elseif (empty($password)) {
        $error = "请输入密码";
    } elseif ($password !== $confirm_password) {
        $error = "两次密码输入不一致";
    } elseif (strlen($password) < 6) {
        $error = "密码长度至少6位";
    } elseif (strlen($username) < 3) {
        $error = "用户名至少3个字符";
    } elseif (strlen($username) > 20) {
        $error = "用户名不能超过20个字符";
    } else {
        try {
            if (register($username, $email, $password)) {
                $success = "注册成功！请登录您的账户。";
            } else {
                $error = "用户名或邮箱已被使用";
            }
        } catch (Exception $e) {
            $error = "注册失败，请稍后重试";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($website_name); ?> - 邮箱注册</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            max-width: 500px;
            width: 100%;
            margin: 0 auto;
        }
        
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            background: white;
        }
        
        .register-header {
            background: var(--primary-gradient);
            padding: 2.5rem 2rem;
            text-align: center;
        }
        
        .register-header h1 {
            color: white;
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        .register-body {
            padding: 2.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            border-radius: 15px;
            padding: 14px 20px;
            border: 2px solid #e9ecef;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
            outline: none;
        }
        
        .btn-register {
            background: var(--primary-gradient);
            color: white;
            width: 100%;
            padding: 14px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
            color: white;
            border: none;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f72585 0%, #ee5a24 100%);
            color: white;
            border: none;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .login-link a {
            color: #4361ee;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            color: #3f37c9;
            text-decoration: underline;
        }
        
        @media (max-width: 576px) {
            .register-header {
                padding: 2rem 1.5rem;
            }
            
            .register-header h1 {
                font-size: 1.8rem;
            }
            
            .register-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1><?php echo htmlspecialchars($website_name); ?></h1>
                <p>创建您的专属账户</p>
            </div>
            
            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($error); ?></h6>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-custom">
                        <i class="fas fa-check-circle fa-2x"></i>
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($success); ?></h6>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>用户名
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                               placeholder="3-20个字符" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>邮箱
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="请输入有效邮箱" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>密码
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="至少6位字符" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-key"></i>确认密码
                        </label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="请再次输入密码" required>
                    </div>
                    
                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus me-2"></i>立即注册
                    </button>
                </form>
                
                <div class="login-link">
                    <a href="login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>已有账户？立即登录
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 密码确认实时验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const confirmPasswordGroup = this.closest('.form-group');
            
            if (confirmPassword && password !== confirmPassword) {
                confirmPasswordGroup.style.borderColor = '#f72585';
                confirmPasswordGroup.style.boxShadow = '0 0 0 0.25rem rgba(247, 37, 133, 0.25)';
            } else if (confirmPassword) {
                confirmPasswordGroup.style.borderColor = '#4cc9f0';
                confirmPasswordGroup.style.boxShadow = '0 0 0 0.25rem rgba(76, 201, 240, 0.25)';
            } else {
                confirmPasswordGroup.style.borderColor = '#e9ecef';
                confirmPasswordGroup.style.boxShadow = 'none';
            }
        });
    </script>
</body>
</html>