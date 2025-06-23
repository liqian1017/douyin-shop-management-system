<?php
/**
 * 主控制器 - 处理所有业务逻辑
 */
class Controller {
    private $model;
    private $auth;
    private $currentUser;
    
    public function __construct() {
        $this->model = new Model();
        $this->auth = new Auth();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * 仪表板/首页
     */
    public function dashboard() {
        $stats = $this->model->getStats();
        $this->render('dashboard', ['stats' => $stats]);
    }
    
    /**
     * 通用管理页面
     */
    public function manage($module) {
        // 验证模块是否合法
        $validModules = ['legal_persons', 'licenses', 'shops', 'withdrawals', 'douyin_accounts', 'users'];
        if (!in_array($module, $validModules)) {
            $this->dashboard();
            return;
        }
        
        // 用户管理需要管理员权限
        if ($module === 'users' && !$this->auth->hasPermission(['admin'])) {
            die('权限不足');
        }
        
        // 获取搜索条件
        $conditions = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        // 获取数据列表
        $data = $this->model->getList($module, $conditions);
        
        // 获取下拉选项数据
        $selectOptions = [];
        if (in_array($module, ['licenses', 'shops', 'withdrawals'])) {
            $selectOptions['legal_persons'] = $this->model->getSelectOptions('legal_persons');
        }
        if (in_array($module, ['shops', 'withdrawals'])) {
            $selectOptions['licenses'] = $this->model->getSelectOptions('licenses');
        }
        if ($module === 'withdrawals') {
            $selectOptions['shops'] = $this->model->getSelectOptions('shops');
        }
        
        // 渲染视图
        $this->render('manage', [
            'module' => $module,
            'data' => $data,
            'conditions' => $conditions,
            'selectOptions' => $selectOptions
        ]);
    }
    
    /**
     * 处理增删改查操作
     */
    public function handleAction($action, $module) {
        // 验证模块
        $validModules = ['legal_persons', 'licenses', 'shops', 'withdrawals', 'douyin_accounts', 'users'];
        if (!in_array($module, $validModules)) {
            $this->jsonResponse(false, '无效的模块');
            return;
        }
        
        // 用户管理需要管理员权限
        if ($module === 'users' && !$this->auth->hasPermission(['admin'])) {
            $this->jsonResponse(false, '权限不足');
            return;
        }
        
        switch ($action) {
            case 'save':
                $this->saveRecord($module);
                break;
                
            case 'delete':
                $this->deleteRecord($module);
                break;
                
            case 'edit':
                $this->getRecord($module);
                break;
                
            default:
                $this->jsonResponse(false, '无效的操作');
        }
    }
    
    /**
     * 保存记录（新增或更新）
     */
    private function saveRecord($module) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(false, '无效的请求方法');
            return;
        }
        
        $id = $_POST['id'] ?? '';
        
        // 准备数据
        $data = $this->prepareData($module, $_POST);
        
        try {
            if (empty($id)) {
                // 新增
                $newId = $this->model->create($module, $data);
                $this->jsonResponse(true, '添加成功', ['id' => $newId]);
            } else {
                // 更新
                $this->model->update($module, $id, $data);
                $this->jsonResponse(true, '更新成功');
            }
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * 删除记录
     */
    private function deleteRecord($module) {
        $id = $_POST['id'] ?? $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->jsonResponse(false, '缺少ID参数');
            return;
        }
        
        try {
            $this->model->delete($module, $id);
            $this->jsonResponse(true, '删除成功');
        } catch (Exception $e) {
            $this->jsonResponse(false, $e->getMessage());
        }
    }
    
    /**
     * 获取单条记录
     */
    private function getRecord($module) {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $this->jsonResponse(false, '缺少ID参数');
            return;
        }
        
        $record = $this->model->getById($module, $id);
        if ($record) {
            $this->jsonResponse(true, '获取成功', $record);
        } else {
            $this->jsonResponse(false, '记录不存在');
        }
    }
    
    /**
     * 准备数据
     */
    private function prepareData($module, $post) {
        $data = [];
        
        switch ($module) {
            case 'legal_persons':
                $data = [
                    'name' => trim($post['name'] ?? ''),
                    'id_card' => trim($post['id_card'] ?? ''),
                    'phone' => trim($post['phone'] ?? ''),
                    'email' => trim($post['email'] ?? ''),
                    'bank' => trim($post['bank'] ?? ''),
                    'bank_card' => trim($post['bank_card'] ?? '')
                ];
                break;
                
            case 'licenses':
                $data = [
                    'name' => trim($post['name'] ?? ''),
                    'legal_person_id' => trim($post['legal_person_id'] ?? ''),
                    'shop_limit' => intval($post['shop_limit'] ?? 1),
                    'status' => $post['status'] ?? 'active'
                ];
                break;
                
            case 'shops':
                // 获取营业执照信息来自动设置法人ID
                $licenseId = trim($post['license_id'] ?? '');
                $legalPersonId = '';
                
                if ($licenseId) {
                    $license = $this->model->getById('licenses', $licenseId);
                    if ($license) {
                        $legalPersonId = $license['legal_person_id'];
                    }
                }
                
                $data = [
                    'name' => trim($post['name'] ?? ''),
                    'douyin_id' => trim($post['douyin_id'] ?? ''),
                    'legal_person_id' => $legalPersonId,
                    'license_id' => $licenseId,
                    'phone' => trim($post['phone'] ?? ''),
                    'email' => trim($post['email'] ?? ''),
                    'open_date' => $post['open_date'] ?? date('Y-m-d'),
                    'status' => $post['status'] ?? 'active',
                    'balance' => floatval($post['balance'] ?? 0),
                    'deposit' => floatval($post['deposit'] ?? 0),
                    'remark' => trim($post['remark'] ?? '')
                ];
                break;
                
            case 'withdrawals':
                $data = [
                    'shop_id' => trim($post['shop_id'] ?? ''),
                    'type' => trim($post['type'] ?? ''),
                    'amount' => floatval($post['amount'] ?? 0),
                    'remaining_balance' => floatval($post['remaining_balance'] ?? 0),
                    'status' => $post['status'] ?? 'pending',
                    'remark' => trim($post['remark'] ?? ''),
                    'operator' => $this->currentUser['username'] ?? 'admin'
                ];
                break;
                
            case 'douyin_accounts':
                $data = [
                    'douyin_id' => trim($post['douyin_id'] ?? ''),
                    'name' => trim($post['name'] ?? ''),
                    'real_name' => trim($post['real_name'] ?? ''),
                    'phone' => trim($post['phone'] ?? ''),
                    'uid' => trim($post['uid'] ?? ''),
                    'contact' => trim($post['contact'] ?? ''),
                    'remark' => trim($post['remark'] ?? '')
                ];
                break;
                
            case 'users':
                $data = [
                    'username' => trim($post['username'] ?? ''),
                    'email' => trim($post['email'] ?? ''),
                    'real_name' => trim($post['real_name'] ?? ''),
                    'phone' => trim($post['phone'] ?? ''),
                    'role' => $post['role'] ?? 'user',
                    'status' => $post['status'] ?? 'active'
                ];
                if (!empty($post['password'])) {
                    $data['password'] = $post['password'];
                }
                break;
        }
        
        return $data;
    }
    
    /**
     * 工具页面
     */
    public function tools() {
        $this->render('tools');
    }
    
    /**
     * 工具操作
     */
    public function toolAction($action) {
        switch ($action) {
            case 'import':
                // 数据导入页面
                $this->render('tools', ['tab' => 'import']);
                break;
                
            case 'export':
                // 数据导出页面
                $this->render('tools', ['tab' => 'export']);
                break;
                
            case 'system':
                // 系统工具页面
                $this->render('tools', ['tab' => 'system']);
                break;
                
            default:
                $this->tools();
        }
    }
    
    /**
     * 渲染视图
     */
    private function render($view, $data = []) {
        // 提取数据为变量
        extract($data);
        
        // 添加公共数据
        $currentUser = $this->currentUser;
        $systemName = SYSTEM_NAME;
        
        // 加载视图
        require VIEW_PATH . '/layout.php';
    }
    
    /**
     * JSON响应
     */
    private function jsonResponse($success, $message = '', $data = null) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}