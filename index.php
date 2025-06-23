<?php
/**
 * 抖音店铺登记管理系统 - MVC入口文件
 * 所有请求统一从这里进入
 */

// 定义根目录常量
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH . '/app');
define('VIEW_PATH', ROOT_PATH . '/views');

// 加载配置文件
require_once 'config.php';

// 检查是否已安装
if (!file_exists('install.lock') && !isset($_GET['install'])) {
    header('Location: install.php');
    exit;
}

// 加载核心文件
require_once APP_PATH . '/Auth.php';
require_once APP_PATH . '/Model.php';
require_once APP_PATH . '/Controller.php';
require_once APP_PATH . '/Router.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 创建路由实例并运行
$router = new Router();
$router->run();
?>