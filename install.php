<?php
/**
 * 抖音店铺管理系统 - 安装程序
 * MVC精简版 v1.0
 */

// 防止重复安装
if (file_exists('install.lock')) {
    die('系统已安装，如需重新安装请删除 install.lock 文件');
}

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 检查PHP版本
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('PHP版本过低，最低要求PHP 7.4.0，当前版本：' . PHP_VERSION);
}

// 检查必要扩展
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    die('缺少必要的PHP扩展：' . implode(', ', $missing_extensions));
}

// 处理安装请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // 验证表单数据
        $db_host = trim($_POST['db_host'] ?? '');
        $db_port = trim($_POST['db_port'] ?? '3306');
        $db_name = trim($_POST['db_name'] ?? '');
        $db_user = trim($_POST['db_user'] ?? '');
        $db_password = $_POST['db_password'] ?? '';
        $admin_username = trim($_POST['admin_username'] ?? 'admin');
        $admin_password = trim($_POST['admin_password'] ?? '');
        
        // 验证必填字段
        if (empty($db_host) || empty($db_name) || empty($db_user) || empty($admin_password)) {
            throw new Exception('请填写所有必填字段');
        }
        
        // 测试数据库连接
        $dsn = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查数据库是否存在，如果不存在则创建
        $stmt = $pdo->prepare("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $stmt->execute();
        
        // 切换到目标数据库
        $pdo->exec("USE `{$db_name}`");
        
        // 创建数据表
        createTables($pdo);
        
        // 插入初始数据
        insertInitialData($pdo, $admin_username, $admin_password);
        
        // 生成配置文件
        generateConfigFile($db_host, $db_port, $db_name, $db_user, $db_password);
        
        // 创建安装锁定文件
        file_put_contents('install.lock', date('Y-m-d H:i:s') . ' - 系统安装完成');
        
        $success = true;
        $message = '系统安装成功！';
        
    } catch (Exception $e) {
        $error = '安装失败：' . $e->getMessage();
    }
}

// 创建数据表
function createTables($pdo) {
    $tables = [
        // 用户表
        'users' => "
            CREATE TABLE `users` (
                `id` varchar(50) NOT NULL,
                `username` varchar(50) NOT NULL,
                `password` varchar(255) NOT NULL,
                `email` varchar(100) DEFAULT NULL,
                `real_name` varchar(50) DEFAULT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `role` enum('admin','manager','user') DEFAULT 'user',
                `status` enum('active','suspended','pending') DEFAULT 'active',
                `last_login` datetime DEFAULT NULL,
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表'
        ",
        
        // 法人表
        'legal_persons' => "
            CREATE TABLE `legal_persons` (
                `id` varchar(50) NOT NULL,
                `name` varchar(100) NOT NULL COMMENT '法人姓名',
                `id_card` varchar(18) NOT NULL COMMENT '身份证号',
                `phone` varchar(20) NOT NULL COMMENT '联系电话',
                `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
                `bank` varchar(100) NOT NULL COMMENT '开户行',
                `bank_card` varchar(30) NOT NULL COMMENT '银行卡号',
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `id_card` (`id_card`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='法人信息表'
        ",
        
        // 营业执照表
        'licenses' => "
            CREATE TABLE `licenses` (
                `id` varchar(50) NOT NULL,
                `name` varchar(200) NOT NULL COMMENT '营业执照名称',
                `legal_person_id` varchar(50) NOT NULL COMMENT '法人ID',
                `shop_limit` int NOT NULL DEFAULT '1' COMMENT '可开店数',
                `used_shops` int NOT NULL DEFAULT '0' COMMENT '已开店数',
                `status` enum('active','inactive') DEFAULT 'active' COMMENT '状态',
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `legal_person_id` (`legal_person_id`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='营业执照表'
        ",
        
        // 店铺表
        'shops' => "
            CREATE TABLE `shops` (
                `id` varchar(50) NOT NULL,
                `name` varchar(100) NOT NULL COMMENT '店铺名称',
                `douyin_id` varchar(100) NOT NULL COMMENT '抖音店铺ID',
                `legal_person_id` varchar(50) NOT NULL COMMENT '法人ID',
                `license_id` varchar(50) NOT NULL COMMENT '营业执照ID',
                `phone` varchar(20) NOT NULL COMMENT '开店手机号',
                `email` varchar(100) NOT NULL COMMENT '主账号邮箱',
                `open_date` date NOT NULL COMMENT '开店日期',
                `status` enum('active','inactive','reviewing') DEFAULT 'active' COMMENT '店铺状态',
                `balance` decimal(10,2) DEFAULT '0.00' COMMENT '余额',
                `deposit` decimal(10,2) DEFAULT '0.00' COMMENT '保证金',
                `remark` text COMMENT '备注',
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `douyin_id` (`douyin_id`),
                KEY `legal_person_id` (`legal_person_id`),
                KEY `license_id` (`license_id`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='店铺表'
        ",
        
        // 提现记录表
        'withdrawals' => "
            CREATE TABLE `withdrawals` (
                `id` varchar(50) NOT NULL,
                `shop_id` varchar(50) NOT NULL COMMENT '店铺ID',
                `type` enum('余额','保证金') NOT NULL COMMENT '资金类型',
                `amount` decimal(10,2) NOT NULL COMMENT '提现金额',
                `remaining_balance` decimal(10,2) DEFAULT '0.00' COMMENT '剩余金额',
                `status` enum('pending','completed','transfer') DEFAULT 'pending' COMMENT '状态',
                `operator` varchar(50) DEFAULT NULL COMMENT '操作人',
                `remark` text COMMENT '备注',
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `shop_id` (`shop_id`),
                KEY `status` (`status`),
                KEY `create_time` (`create_time`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='提现记录表'
        ",
        
        // 抖音账号登记表
        'douyin_accounts' => "
            CREATE TABLE `douyin_accounts` (
                `id` varchar(50) NOT NULL,
                `douyin_id` varchar(100) NOT NULL COMMENT '抖音号ID',
                `name` varchar(100) NOT NULL COMMENT '抖音号名称',
                `real_name` varchar(50) NOT NULL COMMENT '实名认证人',
                `phone` varchar(20) NOT NULL COMMENT '绑定手机号',
                `uid` varchar(50) NOT NULL COMMENT 'UID',
                `contact` varchar(100) DEFAULT NULL COMMENT '联系人',
                `remark` text COMMENT '备注',
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `douyin_id` (`douyin_id`),
                KEY `real_name` (`real_name`),
                KEY `phone` (`phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='抖音账号登记表'
        "
    ];
    
    foreach ($tables as $tableName => $sql) {
        $pdo->exec("DROP TABLE IF EXISTS `{$tableName}`");
        $pdo->exec($sql);
    }
}

// 插入初始数据
function insertInitialData($pdo, $admin_username, $admin_password) {
    // 创建管理员账户
    $admin_id = 'user_' . time() . '_' . rand(1000, 9999);
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, password, email, real_name, role, status) 
        VALUES (?, ?, ?, ?, ?, 'admin', 'active')
    ");
    $stmt->execute([
        $admin_id,
        $admin_username,
        $hashed_password,
        'admin@example.com',
        '系统管理员'
    ]);
    
    // 创建示例经理账户
    $manager_id = 'user_' . time() . '_' . rand(1000, 9999);
    $stmt = $pdo->prepare("
        INSERT INTO users (id, username, password, email, real_name, role, status) 
        VALUES (?, 'manager', ?, 'manager@example.com', '系统经理', 'manager', 'active')
    ");
    $stmt->execute([
        $manager_id,
        password_hash('admin123', PASSWORD_DEFAULT)
    ]);
}

// 生成配置文件
function generateConfigFile($host, $port, $dbname, $user, $password) {
    $config = "<?php\n";
    $config .= "// 数据库配置\n";
    $config .= "define('DB_HOST', '{$host}');\n";
    $config .= "define('DB_PORT', '{$port}');\n";
    $config .= "define('DB_NAME', '{$dbname}');\n";
    $config .= "define('DB_USER', '{$user}');\n";
    $config .= "define('DB_PASSWORD', '{$password}');\n";
    $config .= "define('DB_CHARSET', 'utf8mb4');\n\n";
    $config .= "// 系统配置\n";
    $config .= "define('SYSTEM_NAME', '抖音店铺登记管理系统');\n";
    $config .= "define('ADMIN_EMAIL', 'admin@example.com');\n\n";
    $config .= "// 时区设置\n";
    $config .= "date_default_timezone_set('Asia/Shanghai');\n\n";
    $config .= "// 开发模式（生产环境请设为false）\n";
    $config .= "define('DEBUG_MODE', false);\n\n";
    $config .= "// 如果是调试模式，显示错误\n";
    $config .= "if (DEBUG_MODE) {\n";
    $config .= "    error_reporting(E_ALL);\n";
    $config .= "    ini_set('display_errors', 1);\n";
    $config .= "} else {\n";
    $config .= "    error_reporting(0);\n";
    $config .= "    ini_set('display_errors', 0);\n";
    $config .= "}\n";
    $config .= "?>";
    
    file_put_contents('config.php', $config);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 抖音店铺管理系统</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        .install-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 600px;
            backdrop-filter: blur(10px);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        .logo h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .logo p {
            color: #7f8c8d;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .requirements {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .requirements h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }
        .req-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .req-ok { color: #27ae60; }
        .req-error { color: #e74c3c; }
        .success-info {
            background: #d4edda;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .success-info h3 {
            color: #155724;
            margin-bottom: 15px;
        }
        .login-info {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .login-info strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="logo">
            <h1>🏪 抖音店铺管理系统</h1>
            <p>MVC精简版 v1.0 - 系统安装向导</p>
        </div>

        <?php if (isset($success) && $success): ?>
            <!-- 安装成功页面 -->
            <div class="success-info">
                <h3>🎉 系统安装成功！</h3>
                <p style="margin-bottom: 20px;">恭喜您，抖音店铺管理系统已成功安装。</p>
                <a href="index.php" class="btn">立即访问系统</a>
            </div>
            
            <div class="login-info">
                <strong>登录信息：</strong><br>
                管理员账号：<?php echo htmlspecialchars($admin_username); ?><br>
                管理员密码：<?php echo htmlspecialchars($admin_password); ?><br>
                <br>
                <strong>演示账号：</strong><br>
                经理账号：manager / admin123
            </div>

        <?php else: ?>
            <!-- 系统要求检查 -->
            <div class="requirements">
                <h3>📋 系统环境检查</h3>
                <div class="req-item">
                    <span>PHP版本 (>= 7.4.0)</span>
                    <span class="<?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'req-ok' : 'req-error'; ?>">
                        <?php echo PHP_VERSION; ?> 
                        <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? '✓' : '✗'; ?>
                    </span>
                </div>
                <?php foreach ($required_extensions as $ext): ?>
                <div class="req-item">
                    <span><?php echo $ext; ?> 扩展</span>
                    <span class="<?php echo extension_loaded($ext) ? 'req-ok' : 'req-error'; ?>">
                        <?php echo extension_loaded($ext) ? '✓ 已安装' : '✗ 未安装'; ?>
                    </span>
                </div>
                <?php endforeach; ?>
                <div class="req-item">
                    <span>config.php 可写</span>
                    <span class="<?php echo is_writable('.') ? 'req-ok' : 'req-error'; ?>">
                        <?php echo is_writable('.') ? '✓ 可写' : '✗ 不可写'; ?>
                    </span>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- 安装表单 -->
            <form method="POST">
                <h3 style="margin-bottom: 20px; color: #2c3e50;">🔧 数据库配置</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">数据库主机 *</label>
                        <input type="text" class="form-control" name="db_host" 
                               value="<?php echo htmlspecialchars($_POST['db_host'] ?? '154.12.81.246'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">端口</label>
                        <input type="text" class="form-control" name="db_port" 
                               value="<?php echo htmlspecialchars($_POST['db_port'] ?? '3306'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">数据库名 *</label>
                    <input type="text" class="form-control" name="db_name" 
                           value="<?php echo htmlspecialchars($_POST['db_name'] ?? 'gao142685220'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">数据库用户名 *</label>
                    <input type="text" class="form-control" name="db_user" 
                           value="<?php echo htmlspecialchars($_POST['db_user'] ?? 'gao142685220'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">数据库密码 *</label>
                    <input type="password" class="form-control" name="db_password" 
                           value="<?php echo htmlspecialchars($_POST['db_password'] ?? ''); ?>" required>
                </div>

                <h3 style="margin: 30px 0 20px; color: #2c3e50;">👤 管理员账号</h3>
                
                <div class="form-group">
                    <label class="form-label">管理员用户名</label>
                    <input type="text" class="form-control" name="admin_username" 
                           value="<?php echo htmlspecialchars($_POST['admin_username'] ?? 'admin'); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">管理员密码 *</label>
                    <input type="password" class="form-control" name="admin_password" 
                           placeholder="请输入安全的密码" required>
                </div>

                <button type="submit" name="install" class="btn">🚀 开始安装</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>