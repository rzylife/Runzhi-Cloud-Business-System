<?php
require_once 'config.php';
$website_name = defined('WEBSITE_NAME') ? WEBSITE_NAME : '润知云业务系统';
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($website_name); ?> - 隐私政策</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
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
            color: #4cc9f0;
        }
        
        .back-link {
            color: #4cc9f0;
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
            color: #4cc9f0;
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
        
        .data-category {
            background: #f8fdff;
            border-left: 4px solid #4cc9f0;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 8px 8px 0;
        }
        
        .data-category h4 {
            color: #4895ef;
            margin-bottom: 0.5rem;
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
            color: #4cc9f0;
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
            <i class="fas fa-cloud" style="color: #4cc9f0; font-size: 1.5rem;"></i>
            <span class="logo-text"><?php echo htmlspecialchars($website_name); ?></span>
        </div>
        <a href="index.php" class="back-link">回到首页</a>
    </div>

    <!-- 主要内容 -->
    <div class="content-container">
        <div class="page-card">
            <div class="page-header">
                <h1>隐私政策</h1>
                <p>保护您的个人信息安全</p>
            </div>
            
            <div class="page-body">
                <div class="section">
                    <h2 class="section-title">1. 引言</h2>
                    <div class="section-content">
                        <p><?php echo htmlspecialchars($website_name); ?>重视您的隐私保护。本隐私政策说明我们如何收集、使用、存储和保护您的个人信息。通过使用我们的服务，您同意我们按照本政策处理您的个人信息。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">2. 我们收集的信息</h2>
                    <div class="section-content">
                        <p>我们收集以下类型的个人信息：</p>
                        
                        <div class="data-category">
                            <h4>账户信息</h4>
                            <ul>
                                <li>用户名</li>
                                <li>电子邮箱地址</li>
                                <li>加密后的密码</li>
                            </ul>
                        </div>
                        
                        <div class="data-category">
                            <h4>使用数据</h4>
                            <ul>
                                <li>登录时间记录</li>
                                <li>积分变动记录</li>
                                <li>产品购买记录</li>
                                <li>签到记录</li>
                            </ul>
                        </div>
                        
                        <div class="data-category">
                            <h4>技术信息</h4>
                            <ul>
                                <li>IP地址</li>
                                <li>浏览器类型</li>
                                <li>设备信息</li>
                                <li>访问时间和页面</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">3. 信息使用目的</h2>
                    <div class="section-content">
                        <p>我们收集的信息用于以下目的：</p>
                        <ul>
                            <li><strong>提供服务：</strong> 创建和管理您的账户，处理产品购买</li>
                            <li><strong>改善服务：</strong> 分析使用模式，优化用户体验</li>
                            <li><strong>安全保障：</strong> 检测和防止欺诈、滥用行为</li>
                            <li><strong>沟通联系：</strong> 发送重要通知和系统公告</li>
                            <li><strong>法律合规：</strong> 遵守适用的法律法规</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">4. 信息共享与披露</h2>
                    <div class="section-content">
                        <p>我们承诺不会向第三方出售您的个人信息。仅在以下情况下可能披露信息：</p>
                        <ul>
                            <li><strong>服务提供商：</strong> 与协助我们提供服务的可信第三方共享必要信息</li>
                            <li><strong>法律要求：</strong> 响应法院命令、传票或其他法律程序</li>
                            <li><strong>业务转让：</strong> 在合并、收购或资产出售情况下</li>
                            <li><strong>用户同意：</strong> 获得您的明确同意后</li>
                        </ul>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">5. 数据安全</h2>
                    <div class="section-content">
                        <p>我们采用多种技术和组织措施保护您的个人信息：</p>
                        <ul>
                            <li>使用HTTPS加密传输数据</li>
                            <li>密码采用bcrypt算法加密存储</li>
                            <li>定期安全审计和漏洞扫描</li>
                            <li>限制员工访问权限</li>
                            <li>数据备份和灾难恢复机制</li>
                        </ul>
                        <p>尽管我们采取了合理措施，但互联网传输和电子存储并非绝对安全。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">6. 您的权利</h2>
                    <div class="section-content">
                        <p>根据适用法律，您享有以下权利：</p>
                        <ul>
                            <li><strong>访问权：</strong> 获取我们持有的您的个人信息副本</li>
                            <li><strong>更正权：</strong> 更正不准确或不完整的个人信息</li>
                            <li><strong>删除权：</strong> 要求删除您的个人信息（在某些情况下）</li>
                            <li><strong>限制处理权：</strong> 限制我们处理您的个人信息</li>
                            <li><strong>数据可携权：</strong> 获取结构化、通用格式的个人信息</li>
                            <li><strong>反对权：</strong> 反对我们基于合法利益处理您的信息</li>
                        </ul>
                        <p>如需行使上述权利，请联系我们的数据保护官。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">7. Cookie和追踪技术</h2>
                    <div class="section-content">
                        <p>我们使用Cookie和类似技术来：</p>
                        <ul>
                            <li>保持您的登录状态</li>
                            <li>记住您的偏好设置</li>
                            <li>分析网站流量和使用情况</li>
                            <li>提供个性化内容和广告</li>
                        </ul>
                        <p>您可以通过浏览器设置管理Cookie，但这可能影响某些功能的正常使用。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">8. 儿童隐私</h2>
                    <div class="section-content">
                        <p>我们的服务不面向13岁以下儿童。我们不会故意收集13岁以下儿童的个人信息。如发现此类情况，请立即联系我们，我们将及时删除相关信息。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">9. 隐私政策更新</h2>
                    <div class="section-content">
                        <p>我们可能不定期更新本隐私政策。重大变更将通过网站公告通知。继续使用服务即表示您同意修改后的政策。</p>
                    </div>
                </div>
                
                <div class="section">
                    <h2 class="section-title">10. 联系我们</h2>
                    <div class="section-content">
                        <p>如果您对本隐私政策有任何疑问，或希望行使您的隐私权利，请联系我们：</p>
                        <p><span class="highlight">数据保护官邮箱：</span> dpo@<?php echo strtolower(str_replace(' ', '', $website_name)); ?>.com</p>
                        <p><span class="highlight">客服邮箱：</span> privacy@<?php echo strtolower(str_replace(' ', '', $website_name)); ?>.com</p>
                        <p><span class="highlight">邮寄地址：</span> [您的公司地址]</p>
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