<!-- 系统工具页面 -->
<div class="section">
    <!-- 标签导航 -->
    <div class="tab-nav">
        <button class="tab-btn <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'import') ? 'active' : ''; ?>" 
                onclick="switchTab('import')">📥 数据导入</button>
        <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'export') ? 'active' : ''; ?>" 
                onclick="switchTab('export')">📤 数据导出</button>
        <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'system') ? 'active' : ''; ?>" 
                onclick="switchTab('system')">🔧 系统工具</button>
    </div>

    <!-- 数据导入标签页 -->
    <div id="import-tab" class="tab-content <?php echo (!isset($_GET['tab']) || $_GET['tab'] === 'import') ? 'active' : ''; ?>">
        <div class="import-section">
            <h3>📥 简化版数据导入</h3>
            
            <!-- 导入说明 -->
            <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="color: #2c3e50; margin-bottom: 15px;">🎯 导入规则说明</h4>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; line-height: 1.8; color: #555;">
                    <div>
                        <h5 style="color: #e74c3c; margin-bottom: 10px;">✅ 必填字段（4个）</h5>
                        <ul style="margin-left: 20px;">
                            <li><strong>法人姓名</strong> - 可重复使用现有</li>
                            <li><strong>营业执照名称</strong> - 可重复使用现有</li>
                            <li><strong>抖音店铺ID</strong> - 必须唯一</li>
                            <li><strong>开店手机号</strong> - 可重复</li>
                        </ul>
                    </div>
                    <div>
                        <h5 style="color: #27ae60; margin-bottom: 10px;">📋 智能处理</h5>
                        <ul style="margin-left: 20px;">
                            <li>自动创建或重用法人信息</li>
                            <li>自动创建或重用营业执照</li>
                            <li>自动填充默认值</li>
                            <li>详细错误提示</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- 下载模板 -->
            <div style="margin-bottom: 20px;">
                <button class="btn btn-info" onclick="downloadTemplate('simple_shops')">📋 下载导入模板</button>
                <button class="btn btn-secondary" onclick="downloadTemplate('simple_guide')">📖 下载导入说明</button>
            </div>
            
            <!-- 文件上传区域 -->
            <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('fileInput').click()">
                <div style="font-size: 48px; color: #3498db; margin-bottom: 15px;">📁</div>
                <h4 style="color: #2c3e50; margin-bottom: 10px;">点击选择CSV文件或拖拽到此处</h4>
                <p style="color: #7f8c8d; font-size: 14px;">
                    只需4个必填字段：法人姓名、营业执照名称、抖音店铺ID、开店手机号<br>
                    支持 .csv 格式，文件大小不超过 10MB
                </p>
                <input type="file" id="fileInput" class="file-input" accept=".csv" onchange="handleFileSelect(event)">
            </div>
            
            <!-- 数据预览 -->
            <div id="importPreview" class="import-preview hidden">
                <h4 style="color: #2c3e50; margin-bottom: 15px;">📊 数据预览 (前5行)</h4>
                <div id="importPreviewTable" style="max-height: 300px; overflow: auto; border: 1px solid #ddd; border-radius: 8px;"></div>
                <div style="margin-top: 15px;">
                    <button class="btn btn-success" id="startImportBtn" onclick="startImport()" disabled>🚀 开始导入</button>
                    <button class="btn btn-secondary" onclick="resetImport()">🔄 重新选择</button>
                </div>
            </div>
            
            <!-- 导入日志 -->
            <div id="importLog" class="hidden" style="margin-top: 20px;">
                <h4 style="color: #2c3e50; margin-bottom: 15px;">📝 导入日志</h4>
                <div id="importLogContent" class="import-log"></div>
            </div>
        </div>
    </div>

    <!-- 数据导出标签页 -->
    <div id="export-tab" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'export') ? 'active' : ''; ?>">
        <h3>📤 数据导出</h3>
        
        <!-- 导出选项 -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <div class="fund-card">
                <h3>🏪 店铺数据导出</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    导出所有店铺信息，包含法人、营业执照、资金等完整数据
                </p>
                <button class="btn btn-primary" style="width: 100%;" onclick="exportData('shops')">
                    📤 导出店铺数据
                </button>
            </div>
            
            <div class="fund-card">
                <h3>💰 提现记录导出</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    导出提现流水和财务记录
                </p>
                <button class="btn btn-primary" style="width: 100%;" onclick="exportData('withdrawals')">
                    📤 导出提现记录
                </button>
            </div>
            
            <div class="fund-card">
                <h3>👤 法人信息导出</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    导出法人基础信息和银行账户信息
                </p>
                <button class="btn btn-primary" style="width: 100%;" onclick="exportData('legal_persons')">
                    📤 导出法人数据
                </button>
            </div>
            
            <div class="fund-card">
                <h3>📄 营业执照导出</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    导出营业执照信息和开店限额统计
                </p>
                <button class="btn btn-primary" style="width: 100%;" onclick="exportData('licenses')">
                    📤 导出执照数据
                </button>
            </div>
            
            <div class="fund-card">
                <h3>📱 抖音号登记导出</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    导出抖音号登记信息
                </p>
                <button class="btn btn-primary" style="width: 100%;" onclick="exportData('douyin_accounts')">
                    📤 导出抖音号数据
                </button>
            </div>
            
            <div class="fund-card" style="border-left-color: #f39c12;">
                <h3>💎 资金汇总导出</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    导出各店铺资金汇总统计
                </p>
                <button class="btn btn-warning" style="width: 100%; color: white;" onclick="exportData('funds_summary')">
                    📤 导出资金汇总
                </button>
            </div>
        </div>
    </div>

    <!-- 系统工具标签页 -->
    <div id="system-tab" class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] === 'system') ? 'active' : ''; ?>">
        <h3>🔧 系统工具</h3>
        
        <?php if ($currentUser['role'] === 'admin'): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <!-- 快速重置密码 -->
            <div class="fund-card">
                <h3>🔑 重置管理员密码</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    将管理员(admin)密码重置为默认密码：admin123
                </p>
                <button class="btn btn-danger" style="width: 100%;" onclick="resetAdminPassword()">
                    🔄 重置管理员密码
                </button>
            </div>
            
            <!-- 数据库备份 -->
            <div class="fund-card">
                <h3>💾 数据库备份</h3>
                <p style="color: #7f8c8d; margin-bottom: 15px;">
                    导出完整的数据库备份文件
                </p>
                <button class="btn btn-success" style="width: 100%;" onclick="backupDatabase()">
                    📦 备份数据库
                </button>
            </div>
            
            <!-- 系统信息 -->
            <div class="fund-card">
                <h3>ℹ️ 系统信息</h3>
                <div style="font-size: 14px; line-height: 1.8;">
                    <p><strong>PHP版本：</strong> <?php echo PHP_VERSION; ?></p>
                    <p><strong>系统时间：</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p><strong>时区：</strong> <?php echo date_default_timezone_get(); ?></p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            只有管理员才能使用系统工具
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// 切换标签
function switchTab(tab) {
    // 隐藏所有标签页
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // 显示选中的标签页
    document.getElementById(tab + '-tab').classList.add('active');
    event.target.classList.add('active');
    
    // 更新URL参数
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);
}

// 下载模板
function downloadTemplate(type) {
    window.open(`api/template.php?type=${type}`, '_blank');
    showNotification('正在下载模板...', 'info');
}

// 处理文件选择
function handleFileSelect(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    if (!file.name.toLowerCase().endsWith('.csv')) {
        showNotification('请选择CSV文件', 'error');
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        showNotification('文件大小不能超过10MB', 'error');
        return;
    }
    
    // 读取并预览文件
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const content = e.target.result;
            previewCSV(content);
        } catch (error) {
            showNotification('文件读取失败', 'error');
        }
    };
    reader.readAsText(file, 'UTF-8');
}

// 预览CSV
function previewCSV(content) {
    const lines = content.split('\n').filter(line => line.trim());
    if (lines.length < 2) {
        showNotification('CSV文件格式错误', 'error');
        return;
    }
    
    // 解析CSV
    const headers = parseCSVLine(lines[0]);
    const data = [];
    for (let i = 1; i < Math.min(6, lines.length); i++) {
        data.push(parseCSVLine(lines[i]));
    }
    
    // 显示预览
    let tableHtml = '<table class="table"><thead><tr>';
    headers.forEach(header => {
        tableHtml += `<th>${header}</th>`;
    });
    tableHtml += '</tr></thead><tbody>';
    
    data.forEach(row => {
        tableHtml += '<tr>';
        headers.forEach((header, index) => {
            tableHtml += `<td>${row[index] || ''}</td>`;
        });
        tableHtml += '</tr>';
    });
    tableHtml += '</tbody></table>';
    
    document.getElementById('importPreviewTable').innerHTML = tableHtml;
    document.getElementById('importPreview').classList.remove('hidden');
    document.getElementById('startImportBtn').disabled = false;
    
    // 保存文件内容供导入使用
    window.importFileContent = content;
}

// 解析CSV行
function parseCSVLine(line) {
    const result = [];
    let current = '';
    let inQuotes = false;
    
    for (let i = 0; i < line.length; i++) {
        const char = line[i];
        const nextChar = line[i + 1];
        
        if (char === '"') {
            if (inQuotes && nextChar === '"') {
                current += '"';
                i++;
            } else {
                inQuotes = !inQuotes;
            }
        } else if (char === ',' && !inQuotes) {
            result.push(current.trim());
            current = '';
        } else {
            current += char;
        }
    }
    
    result.push(current.trim());
    return result;
}

// 开始导入
function startImport() {
    if (!window.importFileContent) {
        showNotification('没有可导入的数据', 'error');
        return;
    }
    
    document.getElementById('importLog').classList.remove('hidden');
    document.getElementById('importLogContent').innerHTML = '';
    
    const formData = new FormData();
    const blob = new Blob([window.importFileContent], { type: 'text/csv' });
    formData.append('file', blob, 'import.csv');
    
    addImportLog('info', '开始导入...');
    
    fetch('api/import.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('导入成功！', 'success');
            result.data.logs.forEach(log => {
                addImportLog(log.type, log.message);
            });
        } else {
            showNotification('导入失败: ' + result.message, 'error');
            if (result.data && result.data.logs) {
                result.data.logs.forEach(log => {
                    addImportLog(log.type, log.message);
                });
            }
        }
    })
    .catch(error => {
        showNotification('导入失败: ' + error.message, 'error');
        addImportLog('error', '导入失败: ' + error.message);
    });
}

// 添加导入日志
function addImportLog(type, message) {
    const logDiv = document.getElementById('importLogContent');
    const entry = document.createElement('div');
    entry.className = type;
    entry.textContent = message;
    logDiv.appendChild(entry);
    logDiv.scrollTop = logDiv.scrollHeight;
}

// 重置导入
function resetImport() {
    document.getElementById('fileInput').value = '';
    document.getElementById('importPreview').classList.add('hidden');
    document.getElementById('importLog').classList.add('hidden');
    document.getElementById('startImportBtn').disabled = true;
    window.importFileContent = null;
}

// 导出数据
function exportData(type) {
    const url = `api/export.php?type=${type}`;
    window.open(url, '_blank');
    showNotification(`正在导出${type}数据...`, 'info');
}

// 重置管理员密码
function resetAdminPassword() {
    if (!confirm('确定要重置管理员密码为 admin123 吗？')) return;
    
    fetch('api/index.php?action=reset_admin_password', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('管理员密码已重置为 admin123', 'success');
        } else {
            showNotification(result.message, 'error');
        }
    });
}

// 备份数据库
function backupDatabase() {
    showNotification('数据库备份功能开发中...', 'info');
}

// 文件拖拽功能
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('fileUploadArea');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect({target: {files}});
            }
        });
    }
});
</script>