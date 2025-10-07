<?php
require_once 'config.php';
checkLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// 获取用户基本信息
$stmt = $db->prepare("SELECT username, email, points, created_at, last_login FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// 获取已购商品
$stmt = $db->prepare("
    SELECT p.name, p.description, p.duration_days, o.points_used, o.created_at as purchase_date
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.user_id = ? AND o.status = 'completed'
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$purchased_products = $stmt->fetchAll();

// 获取签到记录
$stmt = $db->prepare("
    SELECT sign_date, points_awarded, created_at
    FROM sign_ins
    WHERE user_id = ?
    ORDER BY sign_date DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$sign_in_records = $stmt->fetchAll();

// 获取优惠码兑换记录
$stmt = $db->prepare("
    SELECT code, points_value, used_at
    FROM discount_codes
    WHERE used_by = ? AND is_used = 1
    ORDER BY used_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$discount_records = $stmt->fetchAll();

// 获取积分变动日志
$stmt = $db->prepare("
    SELECT points_change, reason, created_at
    FROM points_log
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$points_log = $stmt->fetchAll();

// 检查今日是否已签到
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT id FROM sign_ins WHERE user_id = ? AND sign_date = ?");
$stmt->execute([$user_id, $today]);
$hasSignedIn = $stmt->fetch();

// 检查连续签到天数
$stmt = $db->prepare("
    SELECT COUNT(*) as consecutive_days 
    FROM sign_ins 
    WHERE user_id = ? 
    AND sign_date >= DATE_SUB(?, INTERVAL 6 DAY)
    AND sign_date <= ?
    ORDER BY sign_date DESC
");
$stmt->execute([$user_id, $today, $today]);
$consecutiveDays = $stmt->fetch()['consecutive_days'];
$signInPoints = SIGN_IN_POINTS + ($consecutiveDays >= MAX_CONSECUTIVE_DAYS ? CONSECUTIVE_BONUS : 0);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - <?php echo htmlspecialchars(WEBSITE_NAME ?? '润知云业务系统'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --secondary-gradient: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --hover-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 0.8rem 2rem;
        }
        
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.2rem;
            color: #4361ee;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            text-align: center;
        }
        
        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
        }
        
        .user-info h2 {
            font-size: 1.8rem;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .user-email {
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .points-display {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.2rem;
            display: inline-block;
            margin: 1rem 0;
        }
        
        .profile-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
        }
        
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .signin-card {
            text-align: center;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1.5rem;
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
            padding: 10px 24px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            transition: all 0.3s ease;
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
        
        .product-item, .record-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .product-item:last-child, .record-item:last-child {
            border-bottom: none;
        }
        
        .product-name {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .product-desc {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .product-meta {
            display: flex;
            justify-content: space-between;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .record-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .points-positive {
            color: #28a745;
            font-weight: 600;
        }
        
        .points-negative {
            color: #dc3545;
            font-weight: 600;
        }
        
        .no-records {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .footer {
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 1.5rem 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem;
            }
            
            .user-info h2 {
                font-size: 1.5rem;
            }
            
            .profile-container {
                margin: 1rem auto;
                padding: 0 0.5rem;
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
                <?php echo htmlspecialchars(WEBSITE_NAME ?? '润知云业务系统'); ?>
            </div>
            <div class="auth-buttons">
                <a href="index.php" class="btn btn-outline-primary">返回首页</a>
                <a href="logout.php" class="btn btn-outline-danger">退出</a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        <!-- 用户信息卡片 -->
        <div class="profile-header">
            <div class="avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <div class="user-email">
                    <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($user['email']); ?>
                </div>
                <div class="user-meta">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-2"></i>注册时间: <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                    </small>
                    <?php if ($user['last_login']): ?>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-clock me-2"></i>最后登录: <?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="points-display">
                <i class="fas fa-coins me-2"></i>当前积分: <?php echo $user['points']; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- 已购商品 -->
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-shopping-cart me-2"></i>已购商品
                    </h3>
                    <?php if (empty($purchased_products)): ?>
                        <div class="no-records">
                            <i class="fas fa-box-open fa-2x text-muted mb-2"></i>
                            <p>暂无已购商品</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($purchased_products as $product): ?>
                        <div class="product-item">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-desc"><?php echo htmlspecialchars($product['description']); ?></div>
                            <div class="product-meta">
                                <span><i class="fas fa-calendar-day me-1"></i><?php echo $product['duration_days']; ?>天</span>
                                <span><i class="fas fa-coins me-1"></i><?php echo $product['points_used']; ?>积分</span>
                                <span><i class="fas fa-calendar-check me-1"></i><?php echo date('Y-m-d', strtotime($product['purchase_date'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- 积分日志 -->
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-history me-2"></i>积分变动记录
                    </h3>
                    <?php if (empty($points_log)): ?>
                        <div class="no-records">
                            <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                            <p>暂无积分记录</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($points_log as $log): ?>
                        <div class="record-item">
                            <div class="record-meta">
                                <span>
                                    <?php if ($log['points_change'] > 0): ?>
                                        <span class="points-positive">+<?php echo $log['points_change']; ?></span>
                                    <?php else: ?>
                                        <span class="points-negative"><?php echo $log['points_change']; ?></span>
                                    <?php endif; ?>
                                </span>
                                <span><?php echo htmlspecialchars($log['reason']); ?></span>
                                <span><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- 每日签到 -->
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar-check me-2"></i>每日签到
                    </h3>
                    <div class="signin-card">
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
                    </div>
                </div>

                <!-- 签到记录 -->
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-list me-2"></i>签到记录
                    </h3>
                    <?php if (empty($sign_in_records)): ?>
                        <div class="no-records">
                            <i class="fas fa-calendar fa-2x text-muted mb-2"></i>
                            <p>暂无签到记录</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($sign_in_records as $record): ?>
                        <div class="record-item">
                            <div class="record-meta">
                                <span><i class="fas fa-coins me-1"></i>+<?php echo $record['points_awarded']; ?></span>
                                <span><?php echo date('Y-m-d', strtotime($record['sign_date'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- 优惠码记录 -->
                <div class="profile-section">
                    <h3 class="section-title">
                        <i class="fas fa-percentage me-2"></i>优惠码记录
                    </h3>
                    <?php if (empty($discount_records)): ?>
                        <div class="no-records">
                            <i class="fas fa-gift fa-2x text-muted mb-2"></i>
                            <p>暂无优惠码记录</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($discount_records as $record): ?>
                        <div class="record-item">
                            <div class="record-meta">
                                <span><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($record['code']); ?></span>
                                <span><i class="fas fa-coins me-1"></i>+<?php echo $record['points_value']; ?></span>
                                <span><?php echo date('Y-m-d', strtotime($record['used_at'])); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 底部 -->
    <div class="footer">
        <a href="terms.php">服务条款</a>
        <a href="privacy.php">隐私政策</a>
        <span>Copyright © 2020-2025 <?php echo htmlspecialchars(WEBSITE_NAME ?? '润知云业务系统'); ?></span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>