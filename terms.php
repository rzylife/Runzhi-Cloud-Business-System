<?php
require_once 'config.php';
$website_name = defined('WEBSITE_NAME') ? WEBSITE_NAME : '润知云业务系统';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($website_name); ?> - 服务条款</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf9 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            border-bottom: 1px solid #e9ecef;
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
            color: #4361ee;
        }
        
        .back-link {
            color: #4361ee;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .content-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-card {
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .page-header {
            background: var(--primary-gradient);
            padding: 2rem;
            color: white;
            text-align: center;
        }
        
        .page-header h1 {
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
        }
        
        .page-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .page-body {
            padding: 2.5rem;
        }
        
        .section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #4361ee;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .section-content {
            color: #495057;
            font-size: 1rem;
        }
        
        .section-content p {
            margin-bottom: 1rem;
        }
        
        .section-content ul {
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .section-content li {
            margin-bottom: 0.5rem;
        }
        
        .highlight {
            background: #e3f2fd;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
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
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 0.8rem 1rem;
            }
            
            .page-header {
                padding: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .page-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- 顶部导航栏 -->
    <div class="header">
        <div class="logo">
            <i class="fas fa-cloud" style="color: #4361ee; font-size: 1.5rem;"></i>
            <span class="logo-text"><?php echo htmlspecialchars($website_name); ?></span>
        </div>
        <a href="index.php" class="back-link">回到首页</a>
    </div>

    <!-- 主要内容 -->
    <div class="content-container">
        <div class="page-card">
            <div class="page-header">
                <h1>服务条款</h1>
                <p>请仔细阅读以下条款</p>
            </div>
            
            <div class="page-body">
                <div class="section">
                    <h2 class="section-title">1. 接受条款</h2>
                    <div class="section-content">
                        <p>欢迎使用<?php echo htmlspecialchars($website_name); ?>服务！通过访问或使用我们的服务，您同意遵守以下服务条款。如果您不同意这些条款，请不要使用我们的服务。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">2. 服务描述</h2>
                    <div class="section-content">
                        <p><?php echo htmlspecialchars($website_name); ?>提供基于云计算的虚拟电脑服务，用户可以通过积分系统购买和使用云电脑资源。服务包括但不限于：</p>
                        <ul>
                            <li>云电脑实例的创建和管理</li>
                            <li>积分获取和消费系统</li>
                            <li>产品购买和续费功能</li>
                            <li>用户账户管理</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">3. 用户账户</h2>
                    <div class="section-content">
                        <p>您需要注册账户才能使用我们的服务。您必须：</p>
                        <ul>
                            <li>提供真实、准确、完整的注册信息</li>
                            <li>维护账户信息的及时更新</li>
                            <li>对账户下的所有活动负责</li>
                            <li>妥善保管账户密码</li>
                        </ul>
                        <p>我们有权在发现虚假信息或违规行为时暂停或终止您的账户。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">4. 积分系统</h2>
                    <div class="section-content">
                        <p>我们的服务采用积分制，用户可以通过以下方式获取积分：</p>
                        <ul>
                            <li>每日签到奖励</li>
                            <li>优惠码兑换</li>
                            <li>管理员发放</li>
                        </ul>
                        <p>积分可用于购买云电脑产品和服务。积分一旦使用，不可退还。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">5. 用户行为</h2>
                    <div class="section-content">
                        <p>您同意在使用服务时遵守以下规定：</p>
                        <ul>
                            <li>不得从事任何违法活动</li>
                            <li>不得侵犯他人知识产权</li>
                            <li>不得传播恶意软件或病毒</li>
                            <li>不得干扰服务的正常运行</li>
                            <li>不得滥用积分系统</li>
                        </ul>
                        <p>违反上述规定可能导致账户被暂停或终止。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">6. 服务变更与终止</h2>
                    <div class="section-content">
                        <p>我们保留随时修改、暂停或终止服务的权利，无需事先通知。我们可能因以下原因终止您的访问权限：</p>
                        <ul>
                            <li>违反服务条款</li>
                            <li>长期不活跃账户</li>
                            <li>技术或安全原因</li>
                        </ul>
                        <p>服务终止后，您的数据可能会被删除，建议定期备份重要数据。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">7. 免责声明</h2>
                    <div class="section-content">
                        <p>服务按"现状"提供，我们不保证：</p>
                        <ul>
                            <li>服务的不间断或无错误运行</li>
                            <li>服务满足您的特定需求</li>
                            <li>通过服务获得的结果完全准确</li>
                        </ul>
                        <p>在法律允许的最大范围内，我们不对任何间接、偶然、特殊或后果性损害负责。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">8. 条款修改</h2>
                    <div class="section-content">
                        <p>我们可能随时更新这些服务条款。重大变更将通过网站公告或邮件通知。继续使用服务即表示您同意修改后的条款。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">9. 联系我们</h2>
                    <div class="section-content">
                        <p>如果您对这些条款有任何疑问，请联系我们的客服团队：</p>
                        <p><span class="highlight">邮箱：</span> support@<?php echo strtolower(str_replace(' ', '', $website_name)); ?>.com</p>
                        <p><span class="highlight">工作时间：</span> 周一至周五 9:00-18:00</p>
                    </div>
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
</body>
</html>