<?php
/**
 * 简化版数据导入处理
 */

// 错误处理设置
error_reporting(0);
ini_set('display_errors', 0);

// 设置JSON响应头
header('Content-Type: application/json; charset=utf-8');

// 引入必要文件
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
    jsonResponse(false, '请先登录', null, 401);
}

// 统一响应格式
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

// 记录导入日志
function logImport($type, $message) {
    $timestamp = date('Y-m-d H:i:s');
    return ['type' => $type, 'message' => "[$timestamp] $message"];
}

try {
    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, '仅支持POST请求', null, 405);
    }

    // 检查文件上传
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(false, '文件上传失败');
    }

    $file = $_FILES['file'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];

    // 验证文件大小
    if ($fileSize > 10 * 1024 * 1024) {
        jsonResponse(false, '文件大小不能超过10MB');
    }

    // 读取CSV文件内容
    $csvContent = file_get_contents($fileTmpName);
    if ($csvContent === false) {
        jsonResponse(false, '无法读取文件内容');
    }

    // 处理BOM
    if (substr($csvContent, 0, 3) === "\xEF\xBB\xBF") {
        $csvContent = substr($csvContent, 3);
    }

    // 转换编码
    $encoding = mb_detect_encoding($csvContent, ['UTF-8', 'GBK', 'GB2312'], true);
    if ($encoding !== 'UTF-8') {
        $csvContent = mb_convert_encoding($csvContent, 'UTF-8', $encoding);
    }

    // 解析CSV
    $lines = array_filter(array_map('trim', explode("\n", $csvContent)));
    if (count($lines) < 2) {
        jsonResponse(false, 'CSV文件格式错误：至少需要标题行和一行数据');
    }

    // 解析CSV行
    function parseCSVLine($line) {
        $result = [];
        $current = '';
        $inQuotes = false;
        
        for ($i = 0; $i < strlen($line); $i++) {
            $char = $line[$i];
            $nextChar = isset($line[$i + 1]) ? $line[$i + 1] : '';
            
            if ($char === '"') {
                if ($inQuotes && $nextChar === '"') {
                    $current .= '"';
                    $i++;
                } else {
                    $inQuotes = !$inQuotes;
                }
            } elseif ($char === ',' && !$inQuotes) {
                $result[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }
        
        $result[] = trim($current);
        return $result;
    }

    $headers = parseCSVLine($lines[0]);
    $data = [];

    for ($i = 1; $i < count($lines); $i++) {
        $values = parseCSVLine($lines[$i]);
        if (count($values) > 0 && !empty($values[0])) {
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($values[$index]) ? trim($values[$index]) : '';
            }
            $data[] = $row;
        }
    }

    if (empty($data)) {
        jsonResponse(false, 'CSV文件中没有有效数据');
    }

    // 验证必要的列
    $requiredColumns = ['法人姓名', '营业执照名称', '抖音店铺ID', '开店手机号'];
    $missingColumns = array_diff($requiredColumns, $headers);
    
    if (!empty($missingColumns)) {
        jsonResponse(false, '缺少必要列: ' . implode(', ', $missingColumns));
    }

    // 开始导入
    $logs = [];
    $logs[] = logImport('info', '================== 开始简化导入 ==================');
    $logs[] = logImport('info', "总数据: " . count($data) . " 行");

    $successCount = 0;
    $errorCount = 0;
    $model = new Model();
    $pdo = $model->getConnection();

    // 获取现有数据
    $existingLegalPersons = [];
    $existingLicenses = [];
    
    $legalPersons = $model->getList('legal_persons');
    foreach ($legalPersons as $lp) {
        $existingLegalPersons[$lp['name']] = $lp;
    }
    
    $licenses = $model->getList('licenses');
    foreach ($licenses as $license) {
        $existingLicenses[$license['name']] = $license;
    }

    // 处理每行数据
    foreach ($data as $index => $row) {
        $rowNumber = $index + 2;
        
        try {
            // 验证必填字段
            $errors = [];
            foreach ($requiredColumns as $col) {
                if (empty($row[$col])) {
                    $errors[] = "{$col}不能为空";
                }
            }
            
            if (!empty($errors)) {
                $errorCount++;
                $logs[] = logImport('error', "第{$rowNumber}行验证失败: " . implode('; ', $errors));
                continue;
            }

            // 检查店铺ID唯一性
            $existingShop = $model->getList('shops', ['search' => $row['抖音店铺ID']]);
            if (!empty($existingShop)) {
                $errorCount++;
                $logs[] = logImport('error', "第{$rowNumber}行: 抖音店铺ID已存在 - " . $row['抖音店铺ID']);
                continue;
            }

            // 处理法人信息
            $legalPersonName = $row['法人姓名'];
            if (isset($existingLegalPersons[$legalPersonName])) {
                $legalPerson = $existingLegalPersons[$legalPersonName];
                $logs[] = logImport('info', "使用现有法人: {$legalPersonName}");
            } else {
                // 创建新法人
                $legalPersonData = [
                    'name' => $legalPersonName,
                    'id_card' => !empty($row['法人身份证号']) ? $row['法人身份证号'] : '待补充',
                    'phone' => !empty($row['法人联系电话']) ? $row['法人联系电话'] : $row['开店手机号'],
                    'email' => !empty($row['法人邮箱']) ? $row['法人邮箱'] : '',
                    'bank' => !empty($row['法人开户行']) ? $row['法人开户行'] : '待补充',
                    'bank_card' => !empty($row['法人银行卡号']) ? $row['法人银行卡号'] : '待补充'
                ];

                $legalPersonId = $model->create('legal_persons', $legalPersonData);
                $legalPerson = ['id' => $legalPersonId, 'name' => $legalPersonName];
                $existingLegalPersons[$legalPersonName] = $legalPerson;
                $logs[] = logImport('success', "创建新法人: {$legalPersonName}");
            }

            // 处理营业执照
            $licenseName = $row['营业执照名称'];
            if (isset($existingLicenses[$licenseName])) {
                $license = $existingLicenses[$licenseName];
                $logs[] = logImport('info', "使用现有营业执照: {$licenseName}");
            } else {
                // 创建新营业执照
                $licenseData = [
                    'name' => $licenseName,
                    'legal_person_id' => $legalPerson['id'],
                    'shop_limit' => !empty($row['可开店数']) ? intval($row['可开店数']) : 5,
                    'status' => 'active'
                ];

                $licenseId = $model->create('licenses', $licenseData);
                $license = ['id' => $licenseId, 'name' => $licenseName, 'used_shops' => 0, 'shop_limit' => $licenseData['shop_limit']];
                $existingLicenses[$licenseName] = $license;
                $logs[] = logImport('success', "创建新营业执照: {$licenseName}");
            }

            // 检查营业执照是否还能开店
            if ($license['used_shops'] >= $license['shop_limit']) {
                throw new Exception("营业执照 {$license['name']} 已达到开店上限");
            }

            // 创建店铺
            $shopData = [
                'name' => !empty($row['店铺名称']) ? $row['店铺名称'] : '店铺_' . $row['抖音店铺ID'],
                'douyin_id' => $row['抖音店铺ID'],
                'legal_person_id' => $legalPerson['id'],
                'license_id' => $license['id'],
                'phone' => $row['开店手机号'],
                'email' => !empty($row['主账号邮箱']) ? $row['主账号邮箱'] : 'shop_' . time() . rand(1000,9999) . '@example.com',
                'open_date' => !empty($row['开店日期']) ? $row['开店日期'] : date('Y-m-d'),
                'status' => !empty($row['店铺状态']) ? $row['店铺状态'] : 'active',
                'balance' => !empty($row['余额']) ? floatval($row['余额']) : 0,
                'deposit' => !empty($row['保证金']) ? floatval($row['保证金']) : 0,
                'remark' => !empty($row['备注']) ? $row['备注'] : '批量导入'
            ];

            $model->create('shops', $shopData);
            
            $successCount++;
            $logs[] = logImport('success', "第{$rowNumber}行导入成功: {$shopData['name']}");

        } catch (Exception $e) {
            $errorCount++;
            $logs[] = logImport('error', "第{$rowNumber}行导入失败: " . $e->getMessage());
        }
    }

    $logs[] = logImport('info', '================== 导入完成 ==================');
    $logs[] = logImport('info', "成功: {$successCount} | 失败: {$errorCount}");

    $result = [
        'logs' => $logs,
        'stats' => [
            'total' => count($data),
            'success' => $successCount,
            'error' => $errorCount
        ]
    ];

    if ($successCount > 0) {
        jsonResponse(true, "导入完成！成功: {$successCount}个", $result);
    } else {
        jsonResponse(false, '没有成功导入任何数据', $result);
    }

} catch (Exception $e) {
    jsonResponse(false, '系统错误: ' . $e->getMessage(), null, 500);
}
?>