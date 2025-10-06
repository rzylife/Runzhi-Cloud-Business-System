<?php
require_once 'config.php';
$website_name = defined('WEBSITE_NAME') ? WEBSITE_NAME : '润知云业务系统';

// 获取所有公告（不分页）
$db = getDB();
$stmt = $db->prepare("SELECT * FROM announcements WHERE is_active = 1 ORDER BY created_at DESC");
$stmt->execute();
$announcements = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($website_name); ?> - 公告中心</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .announcements-section {
            padding: 3rem 2rem;
            background: white;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: #495057;
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .announcement-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .announcement-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .announcement-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 0;
        }
        
        .announcement-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .announcement-content {
            color: #495057;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .no-announcements {
            text-align: center;
            padding: 3rem;
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
            <div class="auth-buttons">
                <a href="index.php" class="btn btn-outline-primary">返回首页</a>
            </div>
        </div>
    </nav>

    <!-- 公告中心 -->
    <div class="announcements-section">
        <h2 class="section-title"><?php echo htmlspecialchars($website_name); ?>公告中心</h2>
        
        <?php if (empty($announcements)): ?>
            <div class="no-announcements">
                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                <p>暂无公告</p>
            </div>
        <?php else: ?>
            <div class="announcements-list">
                <?php foreach ($announcements as $announcement): ?>
                <div class="announcement-card">
                    <div class="announcement-header">
                        <h3 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                        <div class="announcement-date">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?>
                        </div>
                    </div>
                    <div class="announcement-content">
                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- 底部 -->
    <div class="footer">
        <a href="terms.php">服务条款</a>
        <a href="privacy.php">隐私政策</a>
        <span>Copyright © 2020-2025 <?php echo htmlspecialchars($website_name); ?></span>
    </div>
</body>
</html>