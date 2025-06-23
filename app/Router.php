<?php
/**
 * 路由器类 - 处理URL路由分发
 */
class Router {
    private $controller;
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->controller = new Controller();
    }
    
    public function run() {
        // 获取请求参数
        $action = $_GET['action'] ?? 'index';
        $module = $_GET['module'] ?? '';
        
        // 检查是否为登录相关请求
        if ($action === 'login' || $action === 'do_login') {
            $this->handleAuth();
            return;
        }
        
        // 检查是否为登出请求
        if ($action === 'logout') {
            $this->auth->logout();
            header('Location: index.php?action=login');
            exit;
        }
        
        // 检查登录状态
        if (!$this->auth->isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
        
        // 路由到对应的控制器方法
        $this->dispatch($action, $module);
    }
    
    private function handleAuth() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
            // 处理登录
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($this->auth->login($username, $password)) {
                header('Location: index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
                require VIEW_PATH . '/login.php';
                exit;
            }
        } else {
            // 显示登录页面
            require VIEW_PATH . '/login.php';
            exit;
        }
    }
    
    private function dispatch($action, $module) {
        // 根据action分发到对应的控制器方法
        switch ($action) {
            case 'index':
            case 'dashboard':
                $this->controller->dashboard();
                break;
                
            case 'manage':
                $this->controller->manage($module);
                break;
                
            case 'add':
            case 'edit':
            case 'delete':
            case 'save':
                $this->controller->handleAction($action, $module);
                break;
                
            case 'tools':
                $this->controller->tools();
                break;
                
            case 'import':
            case 'export':
            case 'system':
                $this->controller->toolAction($action);
                break;
                
            default:
                $this->controller->dashboard();
                break;
        }
    }
}