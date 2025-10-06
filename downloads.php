<?php
require_once 'config.php';
$website_name = defined('WEBSITE_NAME') ? WEBSITE_NAME : '润知云业务系统';

// 获取下载文件列表
$db = getDB();
$stmt = $db->prepare("SELECT * FROM download_files WHERE is_active = 1 ORDER BY created_at DESC");
$stmt->execute();
$downloads = $stmt->fetchAll();

// 获取分类列表
$categories = ['Windows客户端', 'Mac客户端', 'Linux客户端', '文档资料', '工具软件'];
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($website_name); ?> - 下载中心</title>
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
        
        .download-section {
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
        
        .category-filter {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .category-btn {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .category-btn.active, .category-btn:hover {
            background: var(--primary-gradient);
            color: white;
            border-color: #4361ee;
        }
        
        .download-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .download-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .download-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .download-title {
            font-weight: 600;
            color: #495057;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .download-meta {
            display: flex;
            gap: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .download-desc {
            color: #6c757d;
            margin-bottom: 1rem;
            line-height: 1.4;
        }
        
        .download-btn {
            background: var(--primary-gradient);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .download-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        .no-downloads {
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

    <!-- 下载中心 -->
    <div class="download-section">
        <h2 class="section-title"><?php echo htmlspecialchars($website_name); ?>下载中心</h2>
        
        <!-- 分类筛选 -->
        <div class="category-filter">
            <button class="category-btn active" data-category="all">全部</button>
            <?php foreach ($categories as $category): ?>
                <button class="category-btn" data-category="<?php echo htmlspecialchars($category); ?>">
                    <?php echo htmlspecialchars($category); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- 下载列表 -->
        <?php if (empty($downloads)): ?>
            <div class="no-downloads">
                <i class="fas fa-download fa-3x text-muted mb-3"></i>
                <p>暂无下载文件</p>
            </div>
        <?php else: ?>
            <div class="download-list">
                <?php foreach ($downloads as $download): ?>
                <div class="download-card" data-category="<?php echo htmlspecialchars($download['category']); ?>">
                    <div class="download-header">
                        <div>
                            <h3 class="download-title"><?php echo htmlspecialchars($download['title']); ?></h3>
                            <div class="download-meta">
                                <span><i class="fas fa-layer-group me-1"></i><?php echo htmlspecialchars($download['version']); ?></span>
                                <span><i class="fas fa-file-alt me-1"></i><?php echo htmlspecialchars($download['file_size']); ?></span>
                                <span><i class="fas fa-download me-1"></i><?php echo $download['download_count']; ?>次下载</span>
                            </div>
                        </div>
                        <a href="<?php echo htmlspecialchars($download['file_path']); ?>" class="download-btn" download>
                            <i class="fas fa-download"></i>立即下载
                        </a>
                    </div>
                    <p class="download-desc"><?php echo htmlspecialchars($download['description']); ?></p>
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

    <script>
        // 分类筛选功能
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', function() {
                // 移除所有激活状态
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // 添加当前激活状态
                this.classList.add('active');
                const category = this.getAttribute('data-category');
                
                // 显示/隐藏下载项
                document.querySelectorAll('.download-card').forEach(card => {
                    if (category === 'all' || card.getAttribute('data-category') === category) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>