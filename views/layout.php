<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $systemName; ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.4.0/echarts.min.js"></script>
</head>
<body>
    <div class="container">
        <!-- 侧边栏 -->
        <div class="sidebar">
            <div class="logo">🏪 <?php echo $systemName; ?></div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo ($view === 'dashboard') ? 'active' : ''; ?>">
                        <span class="nav-icon">📊</span>数据概览
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?action=manage&module=legal_persons" class="nav-link <?php echo (isset($module) && $module === 'legal_persons') ? 'active' : ''; ?>">
                        <span class="nav-icon">👤</span>法人管理
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?action=manage&module=licenses" class="nav-link <?php echo (isset($module) && $module === 'licenses') ? 'active' : ''; ?>">
                        <span class="nav-icon">📄</span>营业执照
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?action=manage&module=shops" class="nav-link <?php echo (isset($module) && $module === 'shops') ? 'active' : ''; ?>">
                        <span class="nav-icon">🏪</span>店铺管理
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?action=manage&module=withdrawals" class="nav-link <?php echo (isset($module) && $module === 'withdrawals') ? 'active' : ''; ?>">
                        <span class="nav-icon">💳</span>提现管理
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?action=manage&module=douyin_accounts" class="nav-link <?php echo (isset($module) && $module === 'douyin_accounts') ? 'active' : ''; ?>">
                        <span class="nav-icon">📱</span>抖音号登记
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?action=tools" class="nav-link <?php echo ($view === 'tools') ? 'active' : ''; ?>">
                        <span class="nav-icon">🔧</span>系统工具
                    </a>
                </li>
                <?php if ($currentUser['role'] === 'admin'): ?>
                <li class="nav-item">
                    <a href="index.php?action=manage&module=users" class="nav-link <?php echo (isset($module) && $module === 'users') ? 'active' : ''; ?>">
                        <span class="nav-icon">👥</span>用户管理
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- 主内容区 -->
        <div class="main-content">
            <!-- 头部 -->
            <div class="header">
                <h1 id="page-title">
                    <?php 
                    if ($view === 'dashboard') {
                        echo '数据概览';
                    } elseif ($view === 'manage') {
                        $titles = [
                            'legal_persons' => '法人管理',
                            'licenses' => '营业执照管理',
                            'shops' => '店铺管理',
                            'withdrawals' => '提现管理',
                            'douyin_accounts' => '抖音号登记',
                            'users' => '用户管理'
                        ];
                        echo $titles[$module] ?? '管理';
                    } elseif ($view === 'tools') {
                        echo '系统工具';
                    }
                    ?>
                </h1>
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($currentUser['real_name'] ?? $currentUser['username'], 0, 1)); ?></div>
                    <div style="display: flex; flex-direction: column; align-items: flex-start;">
                        <span><?php echo htmlspecialchars($currentUser['real_name'] ?? $currentUser['username']); ?></span>
                        <small style="opacity: 0.8; font-size: 12px;">
                            <?php echo $currentUser['role'] === 'admin' ? '管理员' : ($currentUser['role'] === 'manager' ? '经理' : '用户'); ?>
                        </small>
                    </div>
                    <div style="margin-left: 15px;">
                        <a href="index.php?action=logout" class="btn-logout">注销</a>
                    </div>
                </div>
            </div>

            <!-- 内容区域 -->
            <div class="content-area">
                <?php 
                // 根据视图加载对应的内容
                if ($view === 'dashboard') {
                    include VIEW_PATH . '/dashboard.php';
                } elseif ($view === 'manage') {
                    include VIEW_PATH . '/manage.php';
                } elseif ($view === 'tools') {
                    include VIEW_PATH . '/tools.php';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- 通知提示 -->
    <div id="notification" class="notification" style="display: none;"></div>
    
    <!-- 通用模态框 -->
    <div id="modal" class="modal" style="display: none;">
        <div class="modal-content" id="modalContent">
            <!-- 动态内容 -->
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // 显示通知
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type;
            notification.style.display = 'block';
            
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // 显示模态框
        function showModal(content) {
            document.getElementById('modalContent').innerHTML = content;
            document.getElementById('modal').style.display = 'flex';
        }

        // 关闭模态框
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        // 点击模态框外部关闭
        document.getElementById('modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // ESC键关闭模态框
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>