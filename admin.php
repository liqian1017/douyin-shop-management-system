<?php
/**
 * 完全修复版综合管理工具
 * 解决所有跳转和会话问题
 */

// 开启会话
session_start();
date_default_timezone_set('Asia/Shanghai');
set_time_limit(300);
ini_set('memory_limit', '512M');

// 管理密钥
$admin_key = 'admin2024';

// 数据库配置
$db_configs = [
    'host' => '154.12.81.246',
    'port' => '3306', 
    'dbname' => 'gao142685220',
    'username' => 'gao142685220',
    'password' => 'xs040311'
];

// 初始化消息
$message = '';
$error = '';
$current_tab = 'database';

// 处理退出登录
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_verified']);
    session_destroy();
    session_start();
    $message = '已安全退出';
}

// 会话验证
$is_authenticated = false;

// 检查是否已登录
if (isset($_SESSION['admin_verified']) && $_SESSION['admin_verified'] === true) {
    $is_authenticated = true;
}

// 处理登录
if (!$is_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_key'])) {
    if ($_POST['admin_key'] === $admin_key) {
        $_SESSION['admin_verified'] = true;
        $is_authenticated = true;
        $message = '登录成功！';
    } else {
        $error = '管理密钥错误';
    }
}

// 处理AJAX请求
if ($is_authenticated && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $result = handleAjaxRequest();
    echo json_encode($result);
    exit;
}

// 处理表单提交
if ($is_authenticated && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $current_tab = $_POST['current_tab'] ?? 'database';
    
    switch ($action) {
        case 'install_db':
            $result = installDatabase();
            break;
        case 'add_user':
            $result = addUser();
            $current_tab = 'users';
            break;
        case 'delete_user':
            $result = deleteUser();
            $current_tab = 'users';
            break;
        case 'generate_test_data':
            $result = generateTestData();
            break;
        case 'reset_config':
            $result = resetConfig();
            break;
        case 'backup_db':
            $result = backupDatabase();
            $current_tab = 'backup';
            break;
        case 'restore_db':
            $result = restoreDatabase();
            $current_tab = 'backup';
            break;
        case 'execute_sql':
            $result = executeSql();
            $current_tab = 'sql';
            break;
        case 'file_edit':
            $result = editFile();
            $current_tab = 'files';
            break;
        case 'file_upload':
            $result = uploadFile();
            $current_tab = 'files';
            break;
        case 'clear_logs':
            $result = clearLogs();
            $current_tab = 'tools';
            break;
        default:
            $result = ['success' => false, 'message' => '未知操作'];
    }
    
    if (isset($result)) {
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// 处理GET请求的操作
if ($is_authenticated && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'test_system') {
        $test_results = testSystem();
        $current_tab = 'test';
    }
}

// AJAX处理函数
function handleAjaxRequest() {
    $type = $_GET['type'] ?? '';
    
    switch ($type) {
        case 'db_stats':
            return getDbStats();
        case 'system_info':
            return getSystemInfo();
        case 'file_content':
            return getFileContent($_GET['file'] ?? '');
        case 'log_content':
            return getLogContent($_GET['file'] ?? '');
        default:
            return ['success' => false, 'message' => '未知请求类型'];
    }
}

// 安装数据库
function installDatabase() {
    global $db_configs;
    
    try {
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_configs['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$db_configs['dbname']}`");
        
        // 创建用户表
        $pdo->exec("DROP TABLE IF EXISTS `users`");
        $pdo->exec("
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
                UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 创建法人表
        $pdo->exec("DROP TABLE IF EXISTS `legal_persons`");
        $pdo->exec("
            CREATE TABLE `legal_persons` (
                `id` varchar(50) NOT NULL,
                `name` varchar(100) NOT NULL,
                `id_card` varchar(18) NOT NULL,
                `phone` varchar(20) NOT NULL,
                `email` varchar(100) DEFAULT NULL,
                `bank` varchar(100) NOT NULL,
                `bank_card` varchar(30) NOT NULL,
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 创建营业执照表
        $pdo->exec("DROP TABLE IF EXISTS `licenses`");
        $pdo->exec("
            CREATE TABLE `licenses` (
                `id` varchar(50) NOT NULL,
                `name` varchar(200) NOT NULL,
                `legal_person_id` varchar(50) NOT NULL,
                `shop_limit` int NOT NULL DEFAULT '1',
                `used_shops` int NOT NULL DEFAULT '0',
                `status` enum('active','inactive') DEFAULT 'active',
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `legal_person_id` (`legal_person_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 创建店铺表
        $pdo->exec("DROP TABLE IF EXISTS `shops`");
        $pdo->exec("
            CREATE TABLE `shops` (
                `id` varchar(50) NOT NULL,
                `name` varchar(100) NOT NULL,
                `douyin_id` varchar(100) NOT NULL,
                `legal_person_id` varchar(50) NOT NULL,
                `license_id` varchar(50) NOT NULL,
                `phone` varchar(20) NOT NULL,
                `email` varchar(100) NOT NULL,
                `open_date` date NOT NULL,
                `status` enum('active','inactive','reviewing') DEFAULT 'active',
                `balance` decimal(10,2) DEFAULT '0.00',
                `deposit` decimal(10,2) DEFAULT '0.00',
                `remark` text,
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `douyin_id` (`douyin_id`),
                KEY `legal_person_id` (`legal_person_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 创建提现记录表
        $pdo->exec("DROP TABLE IF EXISTS `withdrawals`");
        $pdo->exec("
            CREATE TABLE `withdrawals` (
                `id` varchar(50) NOT NULL,
                `shop_id` varchar(50) NOT NULL,
                `type` enum('余额','保证金') NOT NULL,
                `amount` decimal(10,2) NOT NULL,
                `remaining_balance` decimal(10,2) DEFAULT '0.00',
                `status` enum('pending','completed','transfer') DEFAULT 'pending',
                `operator` varchar(50) DEFAULT NULL,
                `remark` text,
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `shop_id` (`shop_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // 创建抖音账号登记表
        $pdo->exec("DROP TABLE IF EXISTS `douyin_accounts`");
        $pdo->exec("
            CREATE TABLE `douyin_accounts` (
                `id` varchar(50) NOT NULL,
                `douyin_id` varchar(100) NOT NULL,
                `name` varchar(100) NOT NULL,
                `real_name` varchar(50) NOT NULL,
                `phone` varchar(20) NOT NULL,
                `uid` varchar(50) NOT NULL,
                `contact` varchar(100) DEFAULT NULL,
                `remark` text,
                `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `douyin_id` (`douyin_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return ['success' => true, 'message' => '数据库安装成功！所有表已创建。'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '数据库安装失败：' . $e->getMessage()];
    }
}

// 添加用户
function addUser() {
    global $db_configs;
    
    try {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $real_name = trim($_POST['real_name'] ?? '');
        $role = $_POST['role'] ?? 'user';
        
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => '用户名和密码不能为空'];
        }
        
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};dbname={$db_configs['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查用户名是否存在
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => '用户名已存在'];
        }
        
        // 添加用户
        $user_id = 'user_' . time() . '_' . rand(1000, 9999);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, password, real_name, role, status) 
            VALUES (?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$user_id, $username, $hashed_password, $real_name, $role]);
        
        return ['success' => true, 'message' => "用户 {$username} 添加成功！"];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '添加用户失败：' . $e->getMessage()];
    }
}

// 删除用户
function deleteUser() {
    global $db_configs;
    
    $username = $_POST['username'] ?? '';
    if (empty($username) || $username === 'admin') {
        return ['success' => false, 'message' => '无效的用户名或不能删除admin用户'];
    }
    
    try {
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};dbname={$db_configs['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => "用户 {$username} 删除成功"];
        } else {
            return ['success' => false, 'message' => '用户不存在'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '删除用户失败：' . $e->getMessage()];
    }
}

// 生成测试数据
function generateTestData() {
    global $db_configs;
    
    try {
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};dbname={$db_configs['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 生成管理员用户
        $admin_id = 'user_' . time() . '_admin';
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO users (id, username, password, real_name, role, status) 
            VALUES (?, 'admin', ?, '系统管理员', 'admin', 'active')
        ");
        $stmt->execute([$admin_id, password_hash('admin123', PASSWORD_DEFAULT)]);
        
        // 生成法人数据
        for ($i = 1; $i <= 3; $i++) {
            $id = 'lp_' . time() . '_' . $i;
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO legal_persons (id, name, id_card, phone, email, bank, bank_card) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                "测试法人{$i}",
                '11010119800101' . str_pad($i, 4, '0', STR_PAD_LEFT),
                '1380013800' . $i,
                "legal{$i}@test.com",
                "测试银行{$i}",
                '6222081234567890' . $i
            ]);
        }
        
        return ['success' => true, 'message' => '测试数据生成成功！已创建管理员用户(admin/admin123)和3个法人数据'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '生成测试数据失败：' . $e->getMessage()];
    }
}

// 重置配置
function resetConfig() {
    global $db_configs;
    
    try {
        $config = "<?php\n";
        $config .= "// 数据库配置\n";
        $config .= "define('DB_HOST', '{$db_configs['host']}');\n";
        $config .= "define('DB_PORT', '{$db_configs['port']}');\n";
        $config .= "define('DB_NAME', '{$db_configs['dbname']}');\n";
        $config .= "define('DB_USER', '{$db_configs['username']}');\n";
        $config .= "define('DB_PASSWORD', '{$db_configs['password']}');\n";
        $config .= "define('DB_CHARSET', 'utf8mb4');\n\n";
        $config .= "// 系统配置\n";
        $config .= "define('SYSTEM_NAME', '抖音店铺登记管理系统');\n";
        $config .= "define('ADMIN_EMAIL', 'admin@example.com');\n\n";
        $config .= "date_default_timezone_set('Asia/Shanghai');\n";
        $config .= "define('DEBUG_MODE', false);\n\n";
        $config .= "if (DEBUG_MODE) {\n";
        $config .= "    error_reporting(E_ALL);\n";
        $config .= "    ini_set('display_errors', 1);\n";
        $config .= "} else {\n";
        $config .= "    error_reporting(0);\n";
        $config .= "    ini_set('display_errors', 0);\n";
        $config .= "}\n?>";
        
        file_put_contents('config.php', $config);
        file_put_contents('install.lock', date('Y-m-d H:i:s') . ' - 通过管理工具安装');
        
        return ['success' => true, 'message' => 'config.php和install.lock已重新生成'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => '重置配置失败：' . $e->getMessage()];
    }
}

// 其他函数简化版本
function backupDatabase() {
    return ['success' => true, 'message' => '备份功能开发中...'];
}

function restoreDatabase() {
    return ['success' => true, 'message' => '恢复功能开发中...'];
}

function executeSql() {
    $sql = trim($_POST['sql'] ?? '');
    if (empty($sql)) {
        return ['success' => false, 'message' => 'SQL语句不能为空'];
    }
    
    global $db_configs;
    try {
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};dbname={$db_configs['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (stripos($sql, 'SELECT') === 0) {
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $output = "查询结果：找到 " . count($results) . " 条记录\n\n";
            foreach (array_slice($results, 0, 10) as $row) {
                $output .= json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
            }
            return ['success' => true, 'message' => $output];
        } else {
            $affected = $pdo->exec($sql);
            return ['success' => true, 'message' => "SQL执行成功！影响 {$affected} 行"];
        }
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'SQL执行失败：' . $e->getMessage()];
    }
}

function editFile() {
    return ['success' => true, 'message' => '文件编辑功能开发中...'];
}

function uploadFile() {
    return ['success' => true, 'message' => '文件上传功能开发中...'];
}

function clearLogs() {
    return ['success' => true, 'message' => '日志清理功能开发中...'];
}

// AJAX获取函数
function getDbStats() {
    global $db_configs;
    try {
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};dbname={$db_configs['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stats = [];
        $tables = ['users', 'legal_persons', 'licenses', 'shops', 'withdrawals', 'douyin_accounts'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $stats[$table] = $stmt->fetchColumn();
            } catch (Exception $e) {
                $stats[$table] = 0;
            }
        }
        
        return ['success' => true, 'data' => $stats];
    } catch (Exception $e) {
        return ['success' => false, 'message' => '获取统计失败：' . $e->getMessage()];
    }
}

function getSystemInfo() {
    return [
        'success' => true,
        'data' => [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'current_time' => date('Y-m-d H:i:s'),
            'disk_free_space' => round(disk_free_space('.') / 1024 / 1024 / 1024, 2) . ' GB'
        ]
    ];
}

function getFileContent($file) {
    $allowed = ['config.php', '.htaccess', 'index.php'];
    if (!in_array($file, $allowed) || !file_exists($file)) {
        return ['success' => false, 'message' => '文件不存在或不允许访问'];
    }
    
    return [
        'success' => true,
        'data' => [
            'content' => file_get_contents($file),
            'size' => filesize($file)
        ]
    ];
}

function getLogContent($file) {
    return ['success' => false, 'message' => '日志查看功能开发中...'];
}

function testSystem() {
    global $db_configs;
    $tests = [];
    
    // PHP版本检测
    $tests[] = [
        'name' => 'PHP版本',
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'message' => 'PHP ' . PHP_VERSION
    ];
    
    // 扩展检测
    $extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
    foreach ($extensions as $ext) {
        $tests[] = [
            'name' => "扩展: {$ext}",
            'status' => extension_loaded($ext),
            'message' => extension_loaded($ext) ? '已安装' : '未安装'
        ];
    }
    
    // 数据库连接
    try {
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $tests[] = ['name' => '数据库连接', 'status' => true, 'message' => '连接成功'];
        
        try {
            $pdo->exec("USE `{$db_configs['dbname']}`");
            $tests[] = ['name' => '目标数据库', 'status' => true, 'message' => '数据库存在'];
        } catch (Exception $e) {
            $tests[] = ['name' => '目标数据库', 'status' => false, 'message' => '数据库不存在'];
        }
    } catch (Exception $e) {
        $tests[] = ['name' => '数据库连接', 'status' => false, 'message' => '连接失败'];
    }
    
    // 文件检测
    $files = ['index.php', 'config.php', '.htaccess'];
    foreach ($files as $file) {
        $tests[] = [
            'name' => "文件: {$file}",
            'status' => file_exists($file),
            'message' => file_exists($file) ? '存在' : '不存在'
        ];
    }
    
    return $tests;
}

function getUserList() {
    global $db_configs;
    try {
        $dsn = "mysql:host={$db_configs['host']};port={$db_configs['port']};dbname={$db_configs['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_configs['username'], $db_configs['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT username, real_name, role, status, create_time FROM users ORDER BY create_time DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统管理工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px; margin: 0 auto; background: white;
            border-radius: 15px; overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white; padding: 30px; text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .content { padding: 30px; }
        .tab-nav {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 5px; margin-bottom: 30px; background: #f8f9fa; border-radius: 8px; padding: 5px;
        }
        .tab-btn {
            padding: 12px 10px; border: none; background: transparent;
            border-radius: 6px; cursor: pointer; transition: all 0.3s; font-size: 13px;
        }
        .tab-btn.active { background: #667eea; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-control {
            width: 100%; padding: 12px; border: 2px solid #e9ecef;
            border-radius: 8px; font-size: 14px; transition: border-color 0.3s;
        }
        .form-control:focus { outline: none; border-color: #667eea; }
        .btn {
            padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer;
            font-weight: 600; transition: all 0.3s; text-decoration: none;
            display: inline-block; text-align: center;
        }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn-warning { background: #ed8936; color: white; }
        .btn-danger { background: #f56565; color: white; }
        .btn-info { background: #4299e1; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .alert {
            padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid;
        }
        .alert-success { background: #d4edda; color: #155724; border-left-color: #28a745; }
        .alert-error { background: #f8d7da; color: #721c24; border-left-color: #dc3545; }
        .card {
            background: white; border-radius: 8px; padding: 20px;
            margin-bottom: 20px; border: 1px solid #e9ecef;
        }
        .card h4 { margin-bottom: 15px; color: #2c3e50; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px; margin-bottom: 20px;
        }
        .stat-card {
            background: #f8f9fa; padding: 20px; border-radius: 8px;
            text-align: center; border-left: 4px solid #667eea;
        }
        .stat-number { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .stat-label { font-size: 12px; color: #6c757d; margin-top: 5px; }
        .config-info {
            background: #e8f4f8; padding: 20px; border-radius: 8px;
            margin-bottom: 20px; font-family: monospace; font-size: 13px;
        }
        .user-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
        }
        .user-table th, .user-table td {
            padding: 12px; text-align: left; border-bottom: 1px solid #e9ecef;
        }
        .user-table th { background: #f8f9fa; font-weight: 600; }
        .textarea {
            width: 100%; height: 200px; padding: 12px; border: 2px solid #e9ecef;
            border-radius: 8px; font-family: monospace; font-size: 13px; resize: vertical;
        }
        .test-result {
            background: #f8f9fa; border-radius: 8px; padding: 20px;
            margin-bottom: 20px; border: 1px solid #e9ecef;
        }
        .test-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px solid #e9ecef;
        }
        .test-item:last-child { border-bottom: none; }
        .test-pass { color: #28a745; }
        .test-fail { color: #dc3545; }
        .auth-form {
            max-width: 400px; margin: 50px auto; background: white;
            padding: 40px; border-radius: 15px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .status-indicator {
            position: fixed; top: 20px; right: 20px; background: #28a745;
            color: white; padding: 8px 15px; border-radius: 20px; font-size: 12px; z-index: 1000;
        }
        @media (max-width: 768px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .tab-nav { grid-template-columns: repeat(4, 1fr); }
        }
    </style>
</head>
<body>

<?php if (!$is_authenticated): ?>
    <!-- 登录界面 -->
    <div class="auth-form">
        <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">🔐 系统管理工具</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">管理密钥</label>
                <input type="password" class="form-control" name="admin_key" required autofocus>
                <small style="color: #666; margin-top: 5px; display: block;">
                    默认密钥：admin2024
                </small>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">验证进入</button>
        </form>
    </div>

<?php else: ?>
    <!-- 登录状态指示器 -->
    <div class="status-indicator">✅ 已验证登录</div>
    
    <!-- 主界面 -->
    <div class="container">
        <div class="header">
            <h1>🚀 系统管理工具</h1>
            <p>数据库管理 · 用户管理 · 系统检测 · 实用工具</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- 标签导航 -->
            <div class="tab-nav">
                <button class="tab-btn <?php echo $current_tab === 'database' ? 'active' : ''; ?>" onclick="switchTab('database')">📊 数据库</button>
                <button class="tab-btn <?php echo $current_tab === 'users' ? 'active' : ''; ?>" onclick="switchTab('users')">👥 用户</button>
                <button class="tab-btn <?php echo $current_tab === 'files' ? 'active' : ''; ?>" onclick="switchTab('files')">📁 文件</button>
                <button class="tab-btn <?php echo $current_tab === 'backup' ? 'active' : ''; ?>" onclick="switchTab('backup')">💾 备份</button>
                <button class="tab-btn <?php echo $current_tab === 'sql' ? 'active' : ''; ?>" onclick="switchTab('sql')">⚡ SQL</button>
                <button class="tab-btn <?php echo $current_tab === 'monitor' ? 'active' : ''; ?>" onclick="switchTab('monitor')">📈 监控</button>
                <button class="tab-btn <?php echo $current_tab === 'test' ? 'active' : ''; ?>" onclick="switchTab('test')">🔍 检测</button>
                <button class="tab-btn <?php echo $current_tab === 'tools' ? 'active' : ''; ?>" onclick="switchTab('tools')">🛠️ 工具</button>
            </div>
            
            <!-- 数据库管理 -->
            <div id="database" class="tab-content <?php echo $current_tab === 'database' ? 'active' : ''; ?>">
                <h3>📊 数据库管理</h3>
                
                <div class="stats-grid" id="dbStats">
                    <!-- 数据库统计将在这里动态加载 -->
                </div>
                
                <div class="config-info">
                    <strong>数据库配置信息：</strong><br>
                    主机地址：<?php echo htmlspecialchars($db_configs['host'] . ':' . $db_configs['port']); ?><br>
                    数据库名：<?php echo htmlspecialchars($db_configs['dbname']); ?><br>
                    用户名：<?php echo htmlspecialchars($db_configs['username']); ?>
                </div>
                
                <div class="grid-3">
                    <div class="card">
                        <h4>🔧 安装数据库</h4>
                        <p style="color: #666; margin-bottom: 15px;">创建所有必要的数据表</p>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="install_db">
                            <input type="hidden" name="current_tab" value="database">
                            <button type="submit" class="btn btn-warning" 
                                    onclick="return confirm('确定要安装数据库吗？这会清除现有数据！')">
                                🚀 安装数据库
                            </button>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h4>🔄 重置配置</h4>
                        <p style="color: #666; margin-bottom: 15px;">重新生成配置文件</p>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="reset_config">
                            <input type="hidden" name="current_tab" value="database">
                            <button type="submit" class="btn btn-primary">📝 重置配置</button>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h4>🎲 测试数据</h4>
                        <p style="color: #666; margin-bottom: 15px;">生成示例数据</p>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="generate_test_data">
                            <input type="hidden" name="current_tab" value="database">
                            <button type="submit" class="btn btn-info">🎯 生成数据</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- 用户管理 -->
            <div id="users" class="tab-content <?php echo $current_tab === 'users' ? 'active' : ''; ?>">
                <h3>👥 用户管理</h3>
                
                <div class="grid-2">
                    <div class="card">
                        <h4>➕ 添加用户</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_user">
                            <input type="hidden" name="current_tab" value="users">
                            <div class="form-group">
                                <label class="form-label">用户名 *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">密码 *</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">真实姓名</label>
                                <input type="text" class="form-control" name="real_name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">用户角色</label>
                                <select class="form-control" name="role">
                                    <option value="admin">管理员</option>
                                    <option value="manager">经理</option>
                                    <option value="user" selected>普通用户</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success">➕ 添加用户</button>
                        </form>
                    </div>
                    
                    <div class="card">
                        <h4>📋 用户列表</h4>
                        <?php $users = getUserList(); ?>
                        <?php if (empty($users)): ?>
                            <p style="color: #666;">暂无用户数据，请先安装数据库</p>
                        <?php else: ?>
                            <table class="user-table">
                                <thead>
                                    <tr><th>用户名</th><th>角色</th><th>状态</th><th>操作</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            <?php if ($user['real_name']): ?>
                                            <br><small><?php echo htmlspecialchars($user['real_name']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $roles = ['admin' => '管理员', 'manager' => '经理', 'user' => '用户'];
                                            echo $roles[$user['role']] ?? $user['role'];
                                            ?>
                                        </td>
                                        <td style="color: <?php echo $user['status'] === 'active' ? '#28a745' : '#dc3545'; ?>">
                                            <?php echo $user['status'] === 'active' ? '正常' : '停用'; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['username'] !== 'admin'): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                                                <input type="hidden" name="current_tab" value="users">
                                                <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 12px;"
                                                        onclick="return confirm('确定要删除用户 <?php echo htmlspecialchars($user['username']); ?> 吗？')">
                                                    删除
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- 文件管理 -->
            <div id="files" class="tab-content <?php echo $current_tab === 'files' ? 'active' : ''; ?>">
                <h3>📁 文件管理</h3>
                <div class="card">
                    <h4>📄 文件状态检查</h4>
                    <div class="config-info">
                        <?php
                        $check_files = ['config.php', '.htaccess', 'install.lock', 'index.php', 'app/Router.php', 'assets/style.css'];
                        foreach ($check_files as $file) {
                            $exists = file_exists($file);
                            echo ($exists ? '✅' : '❌') . " {$file}";
                            if ($exists) {
                                echo ' (' . number_format(filesize($file)) . ' bytes)';
                            }
                            echo "<br>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- 备份恢复 -->
            <div id="backup" class="tab-content <?php echo $current_tab === 'backup' ? 'active' : ''; ?>">
                <h3>💾 数据备份与恢复</h3>
                <div class="card">
                    <p style="color: #666; margin-bottom: 15px;">备份功能开发中...</p>
                </div>
            </div>
            
            <!-- SQL执行器 -->
            <div id="sql" class="tab-content <?php echo $current_tab === 'sql' ? 'active' : ''; ?>">
                <h3>⚡ SQL执行器</h3>
                <div class="card">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="execute_sql">
                        <input type="hidden" name="current_tab" value="sql">
                        <div class="form-group">
                            <label class="form-label">SQL语句</label>
                            <textarea class="textarea" name="sql" placeholder="输入SQL语句，例如：SELECT * FROM users LIMIT 10"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">⚡ 执行SQL</button>
                    </form>
                </div>
            </div>
            
            <!-- 系统监控 -->
            <div id="monitor" class="tab-content <?php echo $current_tab === 'monitor' ? 'active' : ''; ?>">
                <h3>📈 系统监控</h3>
                <div class="card">
                    <h4>💻 系统信息</h4>
                    <div id="systemInfo" class="config-info">正在加载系统信息...</div>
                    <button class="btn btn-primary" onclick="loadSystemInfo()">🔄 刷新信息</button>
                </div>
            </div>
            
            <!-- 系统检测 -->
            <div id="test" class="tab-content <?php echo $current_tab === 'test' ? 'active' : ''; ?>">
                <h3>🔍 系统检测</h3>
                
                <?php if (isset($test_results)): ?>
                    <div class="test-result">
                        <h4>📊 检测结果</h4>
                        <?php foreach ($test_results as $test): ?>
                            <div class="test-item">
                                <span><?php echo htmlspecialchars($test['name']); ?></span>
                                <span class="<?php echo $test['status'] ? 'test-pass' : 'test-fail'; ?>">
                                    <?php echo htmlspecialchars($test['message']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <a href="?action=test_system" class="btn btn-success">🔍 开始系统检测</a>
                    <div style="margin-top: 20px;">
                        <h4>🚀 快速测试链接</h4>
                        <div class="btn-group">
                            <a href="index.php" target="_blank" class="btn btn-info">🏠 访问首页</a>
                            <a href="index.php?action=login" target="_blank" class="btn btn-info">🔐 登录页面</a>
                            <a href="assets/style.css" target="_blank" class="btn btn-info">🎨 样式文件</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 实用工具 -->
            <div id="tools" class="tab-content <?php echo $current_tab === 'tools' ? 'active' : ''; ?>">
                <h3>🛠️ 实用工具</h3>
                
                <div class="card">
                    <h4>🚪 安全退出</h4>
                    <p style="color: #666; margin-bottom: 15px;">退出管理工具并清除登录状态</p>
                    <a href="?logout=1" class="btn btn-danger" onclick="return confirm('确定要退出管理工具吗？')">
                        🚪 安全退出
                    </a>
                </div>
                
                <div class="card">
                    <h4>🔧 系统维护</h4>
                    <div class="btn-group">
                        <button class="btn btn-warning" onclick="alert('功能开发中...')">🧹 清理缓存</button>
                        <button class="btn btn-info" onclick="alert('功能开发中...')">🔄 检查更新</button>
                        <button class="btn btn-secondary" onclick="alert('功能开发中...')">⚡ 重启服务</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 切换标签页
        function switchTab(tabName) {
            // 隐藏所有标签页
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // 显示选中的标签页
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // 根据标签页加载相应数据
            if (tabName === 'database') {
                loadDbStats();
            } else if (tabName === 'monitor') {
                loadSystemInfo();
            }
        }
        
        // 加载数据库统计
        function loadDbStats() {
            fetch('?ajax=1&type=db_stats')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const stats = data.data;
                        const statsHtml = Object.entries(stats).map(([table, count]) => `
                            <div class="stat-card">
                                <div class="stat-number">${count}</div>
                                <div class="stat-label">${getTableName(table)}</div>
                            </div>
                        `).join('');
                        
                        document.getElementById('dbStats').innerHTML = statsHtml;
                    }
                })
                .catch(error => {
                    console.error('加载统计失败:', error);
                    document.getElementById('dbStats').innerHTML = '<p style="color: #666;">加载统计数据失败</p>';
                });
        }
        
        // 加载系统信息
        function loadSystemInfo() {
            fetch('?ajax=1&type=system_info')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const info = data.data;
                        document.getElementById('systemInfo').innerHTML = `
                            <strong>系统环境信息：</strong><br>
                            PHP版本：${info.php_version}<br>
                            服务器软件：${info.server_software}<br>
                            内存限制：${info.memory_limit}<br>
                            上传限制：${info.upload_max_filesize}<br>
                            可用磁盘空间：${info.disk_free_space}<br>
                            当前时间：${info.current_time}
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('systemInfo').innerHTML = '<p style="color: #666;">加载系统信息失败</p>';
                });
        }
        
        // 获取表格中文名
        function getTableName(table) {
            const names = {
                'users': '用户',
                'legal_persons': '法人',
                'licenses': '营业执照',
                'shops': '店铺',
                'withdrawals': '提现记录',
                'douyin_accounts': '抖音账号'
            };
            return names[table] || table;
        }
        
        // 页面加载完成后自动加载数据
        document.addEventListener('DOMContentLoaded', function() {
            // 自动加载当前标签页的数据
            const activeTab = document.querySelector('.tab-btn.active');
            if (activeTab) {
                const tabName = activeTab.textContent.includes('数据库') ? 'database' : 
                               activeTab.textContent.includes('监控') ? 'monitor' : '';
                if (tabName === 'database') {
                    loadDbStats();
                } else if (tabName === 'monitor') {
                    loadSystemInfo();
                }
            }
        });
    </script>

<?php endif; ?>

</body>
</html>