<?php
/**
 * API统一入口文件
 * 处理AJAX请求
 */

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 引入必要文件
require_once '../config.php';
require_once '../app/Auth.php';
require_once '../app/Model.php';
require_once '../app/Controller.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 创建实例
$auth = new Auth();
$controller = new Controller();

// 检查登录状态（除了登录接口外）
$action = $_GET['action'] ?? '';
if ($action !== 'login' && !$auth->isLoggedIn()) {
    jsonResponse(false, '未登录', null, 401);
}

// 路由处理
try {
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'logout':
            $auth->logout();
            jsonResponse(true, '注销成功');
            break;
            
        case 'get':
            handleGet();
            break;
            
        case 'save':
            handleSave();
            break;
            
        case 'delete':
            handleDelete();
            break;
            
        case 'stats':
            handleStats();
            break;
            
        case 'reset_admin_password':
            handleResetAdminPassword();
            break;
            
        default:
            jsonResponse(false, '未知的API操作', null, 404);
    }
} catch (Exception $e) {
    jsonResponse(false, '服务器错误: ' . $e->getMessage(), null, 500);
}

// JSON响应函数
function jsonResponse($success, $message = '', $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 处理登录
function handleLogin() {
    global $auth;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, '无效的请求方法');
    }
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        jsonResponse(false, '用户名和密码不能为空');
    }
    
    if ($auth->login($username, $password)) {
        jsonResponse(true, '登录成功', [
            'user' => $auth->getCurrentUser()
        ]);
    } else {
        jsonResponse(false, '用户名或密码错误');
    }
}

// 处理获取数据
function handleGet() {
    global $controller;
    
    $module = $_GET['module'] ?? '';
    $id = $_GET['id'] ?? '';
    
    if (empty($module) || empty($id)) {
        jsonResponse(false, '缺少必要参数');
    }
    
    // 直接调用Controller的getRecord方法
    $controller->handleAction('edit', $module);
}

// 处理保存数据
function handleSave() {
    global $controller;
    
    $module = $_GET['module'] ?? '';
    
    if (empty($module)) {
        jsonResponse(false, '缺少模块参数');
    }
    
    // 直接调用Controller的saveRecord方法
    $controller->handleAction('save', $module);
}

// 处理删除数据
function handleDelete() {
    global $controller;
    
    $module = $_GET['module'] ?? '';
    
    if (empty($module)) {
        jsonResponse(false, '缺少模块参数');
    }
    
    // 直接调用Controller的deleteRecord方法
    $controller->handleAction('delete', $module);
}

// 处理统计数据
function handleStats() {
    $model = new Model();
    $stats = $model->getStats();
    jsonResponse(true, '获取统计数据成功', $stats);
}

// 处理重置管理员密码
function handleResetAdminPassword() {
    global $auth;
    
    // 检查是否是管理员
    if (!$auth->hasPermission(['admin'])) {
        jsonResponse(false, '权限不足');
    }
    
    try {
        $model = new Model();
        $pdo = $model->getConnection();
        
        $newPassword = 'admin123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
        $result = $stmt->execute([$hashedPassword]);
        
        if ($result && $stmt->rowCount() > 0) {
            jsonResponse(true, '管理员密码已重置为: admin123');
        } else {
            jsonResponse(false, '重置失败：管理员账号不存在');
        }
    } catch (Exception $e) {
        jsonResponse(false, '操作失败: ' . $e->getMessage());
    }
}
?>