<?php
/**
 * 数据导出处理文件
 */

require_once '../config.php';
require_once '../app/Auth.php';
require_once '../app/Model.php';

// 启动会话
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查登录
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    die('请先登录');
}

// 设置时区和内存限制
date_default_timezone_set('Asia/Shanghai');
ini_set('memory_limit', '512M');
set_time_limit(300);

// 获取参数
$type = $_GET['type'] ?? 'shops';
$encoding = $_GET['encoding'] ?? 'utf8';

// 创建模型实例
$model = new Model();

// 导出配置
$exportConfigs = [
    'shops' => [
        'name' => '店铺数据',
        'filename' => '店铺数据导出',
        'headers' => [
            'ID', '店铺名称', '抖音店铺ID', '法人姓名', '营业执照名称', 
            '开店手机号', '主账号邮箱', '开店日期', '店铺状态', 
            '余额(元)', '保证金(元)', '备注', '创建时间'
        ],
        'fields' => [
            'id', 'name', 'douyin_id', 'legal_person_name', 'license_name',
            'phone', 'email', 'open_date', 'status', 'balance', 'deposit', 'remark', 'create_time'
        ]
    ],
    
    'withdrawals' => [
        'name' => '提现记录',
        'filename' => '提现记录导出',
        'headers' => [
            'ID', '提现时间', '店铺名称', '抖音店铺ID', '法人姓名', 
            '资金类型', '提现金额(元)', '剩余金额(元)', '提现状态', 
            '操作人', '备注'
        ],
        'fields' => [
            'id', 'create_time', 'shop_name', 'douyin_id', 'legal_person_name',
            'type', 'amount', 'remaining_balance', 'status', 'operator', 'remark'
        ]
    ],
    
    'legal_persons' => [
        'name' => '法人信息',
        'filename' => '法人信息导出',
        'headers' => [
            'ID', '法人姓名', '身份证号', '联系电话', '邮箱地址', 
            '开户银行', '银行卡号', '创建时间'
        ],
        'fields' => [
            'id', 'name', 'id_card', 'phone', 'email', 'bank', 'bank_card', 'create_time'
        ]
    ],
    
    'licenses' => [
        'name' => '营业执照',
        'filename' => '营业执照导出',
        'headers' => [
            'ID', '营业执照名称', '法人姓名', '可开店数', '已开店数', 
            '剩余开店数', '执照状态', '创建时间'
        ],
        'fields' => [
            'id', 'name', 'legal_person_name', 'shop_limit', 'used_shops', 'remaining_shops', 'status', 'create_time'
        ]
    ],
    
    'douyin_accounts' => [
        'name' => '抖音号登记',
        'filename' => '抖音号登记导出',
        'headers' => [
            'ID', '抖音号ID', '抖音号名称', '实名认证人', '绑定手机号', 
            'UID', '联系人', '备注', '创建时间'
        ],
        'fields' => [
            'id', 'douyin_id', 'name', 'real_name', 'phone', 'uid', 'contact', 'remark', 'create_time'
        ]
    ],
    
    'funds_summary' => [
        'name' => '资金汇总',
        'filename' => '资金汇总导出',
        'headers' => [
            '店铺名称', '抖音店铺ID', '法人姓名', '营业执照', 
            '余额(元)', '保证金(元)', '总资金(元)', '店铺状态', 
            '开店日期'
        ],
        'fields' => [
            'name', 'douyin_id', 'legal_person_name', 'license_name',
            'balance', 'deposit', 'total_funds', 'status', 'open_date'
        ]
    ]
];

// 检查导出类型
if (!isset($exportConfigs[$type])) {
    die('无效的导出类型');
}

$config = $exportConfigs[$type];

try {
    // 获取数据
    $data = [];
    if ($type === 'funds_summary') {
        // 特殊处理资金汇总
        $shops = $model->getList('shops', ['status' => 'active']);
        foreach ($shops as $shop) {
            $shop['total_funds'] = floatval($shop['balance']) + floatval($shop['deposit']);
            $data[] = $shop;
        }
    } else {
        $data = $model->getList($type);
    }
    
    // 处理特殊字段
    if ($type === 'licenses') {
        foreach ($data as &$item) {
            $item['remaining_shops'] = $item['shop_limit'] - $item['used_shops'];
        }
    }
    
    // 生成文件名
    $timestamp = date('Y-m-d_H-i-s');
    $filename = $config['filename'] . '_' . $timestamp . '.csv';
    
    // 设置响应头
    header('Content-Type: text/csv; charset=' . ($encoding === 'gbk' ? 'GBK' : 'UTF-8'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    // 创建输出流
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM
    if ($encoding === 'utf8') {
        fwrite($output, "\xEF\xBB\xBF");
    }
    
    // 输出标题行
    $headers = $config['headers'];
    if ($encoding === 'gbk') {
        $headers = array_map(function($header) {
            return iconv('UTF-8', 'GBK//IGNORE', $header);
        }, $headers);
    }
    fputcsv($output, $headers);
    
    // 输出数据行
    foreach ($data as $row) {
        $csvRow = [];
        
        foreach ($config['fields'] as $field) {
            $value = $row[$field] ?? '';
            
            // 格式化数据
            if (in_array($field, ['balance', 'deposit', 'amount', 'remaining_balance', 'total_funds'])) {
                $value = number_format(floatval($value), 2, '.', '');
            } elseif ($field === 'status') {
                $statusTexts = [
                    'active' => '活跃',
                    'inactive' => '停用',
                    'reviewing' => '审核中',
                    'pending' => '待提现',
                    'completed' => '已完成',
                    'transfer' => '法人转出'
                ];
                $value = $statusTexts[$value] ?? $value;
            }
            
            // 编码转换
            if ($encoding === 'gbk' && is_string($value)) {
                $value = iconv('UTF-8', 'GBK//IGNORE', $value);
            }
            
            $csvRow[] = $value;
        }
        
        fputcsv($output, $csvRow);
    }
    
    fclose($output);
    exit;
    
} catch (Exception $e) {
    error_log('CSV导出错误: ' . $e->getMessage());
    http_response_code(500);
    die('导出过程中发生错误');
}
?>