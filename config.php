<?php
// 数据库配置
define('DB_HOST', '154.12.81.246');
define('DB_PORT', '3306');
define('DB_NAME', 'gao142685220');
define('DB_USER', 'gao142685220');
define('DB_PASSWORD', '040311');
define('DB_CHARSET', 'utf8mb4');

// 系统配置
define('SYSTEM_NAME', '抖音店铺登记管理系统');
define('ADMIN_EMAIL', 'admin@example.com');

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 开发模式（生产环境请设为false）
define('DEBUG_MODE', false);

// 如果是调试模式，显示错误
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>