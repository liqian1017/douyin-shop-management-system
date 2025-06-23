<?php
/**
 * 统一数据模型类 - 修复搜索功能版本
 */
class Model {
    private $pdo;
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("数据库连接失败: " . $e->getMessage());
        }
    }
    
    /**
     * 生成唯一ID
     */
    public function generateId($prefix) {
        return $prefix . '_' . time() . '_' . rand(1000, 9999);
    }
    
    /**
     * 获取统计数据
     */
    public function getStats() {
        try {
            $stats = [];
            
            // 法人总数
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM legal_persons");
            $stats['legalPersonCount'] = $stmt->fetch()['count'];
            
            // 营业执照总数
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM licenses");
            $stats['licenseCount'] = $stmt->fetch()['count'];
            
            // 店铺统计
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                    SUM(balance) as totalBalance,
                    SUM(deposit) as totalDeposit
                FROM shops
            ");
            $shopStats = $stmt->fetch();
            $stats['shopCount'] = $shopStats['total'];
            $stats['activeShopCount'] = $shopStats['active'];
            $stats['totalBalance'] = floatval($shopStats['totalBalance'] ?? 0);
            $stats['totalDeposit'] = floatval($shopStats['totalDeposit'] ?? 0);
            $stats['totalFunds'] = $stats['totalBalance'] + $stats['totalDeposit'];
            
            // 待提现金额
            $stmt = $this->pdo->query("SELECT SUM(amount) as total FROM withdrawals WHERE status = 'pending'");
            $stats['pendingAmount'] = floatval($stmt->fetch()['total'] ?? 0);
            
            // 抖音账号数
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM douyin_accounts");
            $stats['douyinAccountCount'] = $stmt->fetch()['count'];
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * 修复后的获取列表方法 - 重点修复搜索功能
     */
    public function getList($module, $conditions = []) {
        $tables = [
            'legal_persons' => 'legal_persons',
            'licenses' => 'licenses',
            'shops' => 'shops',
            'withdrawals' => 'withdrawals',
            'douyin_accounts' => 'douyin_accounts',
            'users' => 'users'
        ];
        
        if (!isset($tables[$module])) {
            return [];
        }
        
        $table = $tables[$module];
        $where = "1=1";
        $params = [];
        
        // 修复搜索条件构建 - 这是关键修复点
        if (!empty($conditions['search'])) {
            $search = '%' . $conditions['search'] . '%';
            switch ($module) {
                case 'legal_persons':
                    $where .= " AND (name LIKE ? OR phone LIKE ? OR id_card LIKE ?)";
                    // 使用 array_merge 而不是直接赋值
                    $params = array_merge($params, [$search, $search, $search]);
                    break;
                case 'licenses':
                    $where .= " AND name LIKE ?";
                    $params = array_merge($params, [$search]);
                    break;
                case 'shops':
                    $where .= " AND (name LIKE ? OR douyin_id LIKE ? OR phone LIKE ?)";
                    $params = array_merge($params, [$search, $search, $search]);
                    break;
                case 'withdrawals':
                    $where .= " AND id LIKE ?";
                    $params = array_merge($params, [$search]);
                    break;
                case 'douyin_accounts':
                    $where .= " AND (douyin_id LIKE ? OR name LIKE ? OR real_name LIKE ?)";
                    $params = array_merge($params, [$search, $search, $search]);
                    break;
                case 'users':
                    $where .= " AND (username LIKE ? OR email LIKE ? OR real_name LIKE ?)";
                    $params = array_merge($params, [$search, $search, $search]);
                    break;
            }
        }
        
        // 状态筛选
        if (!empty($conditions['status'])) {
            $where .= " AND status = ?";
            $params[] = $conditions['status'];
        }
        
        // 构建SQL
        $sql = $this->buildListSQL($module, $where);
        
        // 添加错误处理
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // 记录错误日志
            error_log('搜索查询错误: ' . $e->getMessage());
            error_log('SQL: ' . $sql);
            error_log('参数: ' . json_encode($params));
            return [];
        }
    }
    
    /**
     * 构建列表查询SQL
     */
    private function buildListSQL($module, $where) {
        switch ($module) {
            case 'legal_persons':
                return "SELECT * FROM legal_persons WHERE {$where} ORDER BY create_time DESC";
                
            case 'licenses':
                return "SELECT l.*, lp.name as legal_person_name 
                        FROM licenses l 
                        LEFT JOIN legal_persons lp ON l.legal_person_id = lp.id 
                        WHERE {$where} ORDER BY l.create_time DESC";
                        
            case 'shops':
                return "SELECT s.*, lp.name as legal_person_name, l.name as license_name 
                        FROM shops s 
                        LEFT JOIN legal_persons lp ON s.legal_person_id = lp.id 
                        LEFT JOIN licenses l ON s.license_id = l.id 
                        WHERE {$where} ORDER BY s.create_time DESC";
                        
            case 'withdrawals':
                return "SELECT w.*, s.name as shop_name, s.douyin_id, lp.name as legal_person_name 
                        FROM withdrawals w 
                        LEFT JOIN shops s ON w.shop_id = s.id 
                        LEFT JOIN legal_persons lp ON s.legal_person_id = lp.id 
                        WHERE {$where} ORDER BY w.create_time DESC";
                        
            case 'douyin_accounts':
                return "SELECT * FROM douyin_accounts WHERE {$where} ORDER BY create_time DESC";
                
            case 'users':
                return "SELECT id, username, email, real_name, phone, role, status, last_login, create_time 
                        FROM users WHERE {$where} ORDER BY create_time DESC";
                        
            default:
                return "";
        }
    }
    
    /**
     * 根据ID获取记录
     */
    public function getById($module, $id) {
        $tables = [
            'legal_persons' => 'legal_persons',
            'licenses' => 'licenses', 
            'shops' => 'shops',
            'withdrawals' => 'withdrawals',
            'douyin_accounts' => 'douyin_accounts',
            'users' => 'users'
        ];
        
        if (!isset($tables[$module])) {
            return null;
        }
        
        $sql = $this->buildDetailSQL($module);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * 构建详情查询SQL
     */
    private function buildDetailSQL($module) {
        switch ($module) {
            case 'legal_persons':
                return "SELECT * FROM legal_persons WHERE id = ?";
                
            case 'licenses':
                return "SELECT l.*, lp.name as legal_person_name 
                        FROM licenses l 
                        LEFT JOIN legal_persons lp ON l.legal_person_id = lp.id 
                        WHERE l.id = ?";
                        
            case 'shops':
                return "SELECT s.*, lp.name as legal_person_name, l.name as license_name 
                        FROM shops s 
                        LEFT JOIN legal_persons lp ON s.legal_person_id = lp.id 
                        LEFT JOIN licenses l ON s.license_id = l.id 
                        WHERE s.id = ?";
                        
            case 'withdrawals':
                return "SELECT w.*, s.name as shop_name, lp.name as legal_person_name 
                        FROM withdrawals w 
                        LEFT JOIN shops s ON w.shop_id = s.id 
                        LEFT JOIN legal_persons lp ON s.legal_person_id = lp.id 
                        WHERE w.id = ?";
                        
            case 'douyin_accounts':
                return "SELECT * FROM douyin_accounts WHERE id = ?";
                
            case 'users':
                return "SELECT * FROM users WHERE id = ?";
                
            default:
                return "";
        }
    }
    
    /**
     * 创建记录
     */
    public function create($module, $data) {
        try {
            // 生成ID
            $prefixes = [
                'legal_persons' => 'lp',
                'licenses' => 'lic',
                'shops' => 'shop',
                'withdrawals' => 'wd',
                'douyin_accounts' => 'dy',
                'users' => 'user'
            ];
            
            $data['id'] = $this->generateId($prefixes[$module]);
            $data['create_time'] = date('Y-m-d H:i:s');
            
            // 数据验证和预处理
            if ($module === 'shops') {
                // 验证必要字段
                if (empty($data['license_id'])) {
                    throw new Exception('营业执照ID不能为空');
                }
                if (empty($data['legal_person_id'])) {
                    throw new Exception('法人ID不能为空');
                }
                if (empty($data['douyin_id'])) {
                    throw new Exception('抖音店铺ID不能为空');
                }
                
                // 检查抖音店铺ID是否已存在
                $existingShop = $this->pdo->prepare("SELECT id FROM shops WHERE douyin_id = ?");
                $existingShop->execute([$data['douyin_id']]);
                if ($existingShop->fetch()) {
                    throw new Exception('抖音店铺ID已存在');
                }
                
                // 检查营业执照是否还能开店
                $license = $this->getById('licenses', $data['license_id']);
                if (!$license) {
                    throw new Exception('营业执照不存在');
                }
                if ($license['used_shops'] >= $license['shop_limit']) {
                    throw new Exception('该营业执照已达到开店上限');
                }
            }
            
            if ($module === 'users' && isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // 过滤空值字段
            $data = array_filter($data, function($value) {
                return $value !== '' && $value !== null;
            });
            
            // 构建插入SQL
            $table = $module;
            $fields = array_keys($data);
            $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
            
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            
            // 后处理
            if ($module === 'shops') {
                // 更新营业执照使用数量
                $this->updateLicenseUsedCount($data['license_id'], 1);
            }
            
            return $data['id'];
            
        } catch (Exception $e) {
            throw new Exception("创建失败: " . $e->getMessage());
        }
    }
    
    /**
     * 更新记录
     */
    public function update($module, $id, $data) {
        try {
            // 移除不需要更新的字段
            unset($data['id']);
            unset($data['create_time']);
            
            if ($module === 'users' && isset($data['password'])) {
                if (empty($data['password'])) {
                    unset($data['password']);
                } else {
                    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }
            }
            
            // 构建更新SQL
            $table = $module;
            $sets = array_map(function($f) { return $f . ' = :' . $f; }, array_keys($data));
            
            $sql = "UPDATE {$table} SET " . implode(', ', $sets) . " WHERE id = :id";
            
            $data['id'] = $id;
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($data);
            
        } catch (Exception $e) {
            throw new Exception("更新失败: " . $e->getMessage());
        }
    }
    
    /**
     * 删除记录
     */
    public function delete($module, $id) {
        try {
            // 特殊处理店铺删除
            if ($module === 'shops') {
                $shop = $this->getById('shops', $id);
                if ($shop) {
                    // 更新营业执照使用数量
                    $this->updateLicenseUsedCount($shop['license_id'], -1);
                }
            }
            
            $sql = "DELETE FROM {$module} WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
            
        } catch (Exception $e) {
            throw new Exception("删除失败: " . $e->getMessage());
        }
    }
    
    /**
     * 更新营业执照使用数量
     */
    private function updateLicenseUsedCount($licenseId, $increment) {
        $sql = "UPDATE licenses SET used_shops = used_shops + ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$increment, $licenseId]);
    }
    
    /**
     * 获取下拉选项数据
     */
    public function getSelectOptions($type) {
        switch ($type) {
            case 'legal_persons':
                $stmt = $this->pdo->query("SELECT id, name FROM legal_persons ORDER BY name");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            case 'licenses':
                $stmt = $this->pdo->query("
                    SELECT l.id, l.name, l.shop_limit, l.used_shops, lp.name as legal_person_name
                    FROM licenses l
                    LEFT JOIN legal_persons lp ON l.legal_person_id = lp.id
                    WHERE l.status = 'active' AND l.used_shops < l.shop_limit
                    ORDER BY l.name
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            case 'shops':
                $stmt = $this->pdo->query("
                    SELECT s.id, s.name, lp.name as legal_person_name
                    FROM shops s
                    LEFT JOIN legal_persons lp ON s.legal_person_id = lp.id
                    WHERE s.status = 'active'
                    ORDER BY s.name
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            default:
                return [];
        }
    }
    
    /**
     * 获取数据库连接（供其他类使用）
     */
    public function getConnection() {
        return $this->pdo;
    }
}
?>