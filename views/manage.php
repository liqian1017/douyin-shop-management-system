<!-- 通用管理页面 -->
<div class="section">
    <div class="section-header">
        <h2 class="section-title">
            <?php 
            $titles = [
                'legal_persons' => '法人管理',
                'licenses' => '营业执照管理',
                'shops' => '店铺管理',
                'withdrawals' => '提现管理',
                'douyin_accounts' => '抖音号登记',
                'users' => '用户管理'
            ];
            echo $titles[$module] ?? '管理';
            ?>
        </h2>
        <button class="btn btn-primary" onclick="showAddForm('<?php echo $module; ?>')">
            + 添加<?php echo trim($titles[$module] ?? '', '管理'); ?>
        </button>
    </div>

    <!-- 搜索栏 -->
    <div class="search-bar">
        <form method="GET" action="index.php">
            <input type="hidden" name="action" value="manage">
            <input type="hidden" name="module" value="<?php echo $module; ?>">
            <input type="text" class="search-input" name="search" 
                   placeholder="搜索..." 
                   value="<?php echo htmlspecialchars($conditions['search'] ?? ''); ?>">
            
            <?php if (in_array($module, ['shops', 'licenses', 'withdrawals'])): ?>
            <select class="form-control" name="status" style="width: 150px;">
                <option value="">全部状态</option>
                <option value="active" <?php echo ($conditions['status'] ?? '') === 'active' ? 'selected' : ''; ?>>活跃</option>
                <option value="inactive" <?php echo ($conditions['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>停用</option>
                <?php if ($module === 'shops'): ?>
                <option value="reviewing" <?php echo ($conditions['status'] ?? '') === 'reviewing' ? 'selected' : ''; ?>>审核中</option>
                <?php endif; ?>
                <?php if ($module === 'withdrawals'): ?>
                <option value="pending" <?php echo ($conditions['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>待提现</option>
                <option value="completed" <?php echo ($conditions['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>已完成</option>
                <?php endif; ?>
            </select>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">搜索</button>
            <a href="index.php?action=manage&module=<?php echo $module; ?>" class="btn btn-secondary">清空</a>
        </form>
    </div>

    <!-- 数据表格 -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <?php
                    // 表格头部
                    switch($module) {
                        case 'legal_persons':
                            echo '<th>法人姓名</th><th>身份证号</th><th>联系电话</th><th>邮箱</th><th>开户行</th><th>银行卡号</th><th>创建时间</th><th>操作</th>';
                            break;
                        case 'licenses':
                            echo '<th>执照名称</th><th>法人姓名</th><th>可开店数</th><th>已开店数</th><th>状态</th><th>创建时间</th><th>操作</th>';
                            break;
                        case 'shops':
                            echo '<th>店铺名称</th><th>抖音店铺ID</th><th>法人姓名</th><th>营业执照</th><th>开店手机号</th><th>主账号邮箱</th><th>开店日期</th><th>余额</th><th>保证金</th><th>状态</th><th>操作</th>';
                            break;
                        case 'withdrawals':
                            echo '<th>提现时间</th><th>店铺名称</th><th>法人姓名</th><th>资金类型</th><th>提现金额</th><th>剩余金额</th><th>状态</th><th>备注</th><th>操作</th>';
                            break;
                        case 'douyin_accounts':
                            echo '<th>抖音号ID</th><th>抖音号名称</th><th>实名人</th><th>绑定手机号</th><th>UID</th><th>联系人</th><th>备注</th><th>创建时间</th><th>操作</th>';
                            break;
                        case 'users':
                            echo '<th>用户名</th><th>真实姓名</th><th>邮箱</th><th>联系电话</th><th>角色</th><th>状态</th><th>最后登录</th><th>创建时间</th><th>操作</th>';
                            break;
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <?php
                    // 表格行数据
                    switch($module) {
                        case 'legal_persons':
                            echo "<td>{$row['name']}</td>";
                            echo "<td>{$row['id_card']}</td>";
                            echo "<td>{$row['phone']}</td>";
                            echo "<td>" . ($row['email'] ?: '-') . "</td>";
                            echo "<td>{$row['bank']}</td>";
                            echo "<td>{$row['bank_card']}</td>";
                            echo "<td>{$row['create_time']}</td>";
                            break;
                            
                        case 'licenses':
                            $statusBadge = $row['status'] === 'active' ? 'status-active' : 'status-inactive';
                            $statusText = $row['status'] === 'active' ? '活跃' : '停用';
                            echo "<td>{$row['name']}</td>";
                            echo "<td>" . ($row['legal_person_name'] ?? '未知') . "</td>";
                            echo "<td>{$row['shop_limit']}</td>";
                            echo "<td>{$row['used_shops']}</td>";
                            echo "<td><span class='status-badge {$statusBadge}'>{$statusText}</span></td>";
                            echo "<td>{$row['create_time']}</td>";
                            break;
                            
                        case 'shops':
                            $statusBadge = "status-{$row['status']}";
                            $statusTexts = ['active' => '活跃', 'inactive' => '停用', 'reviewing' => '审核中'];
                            $statusText = $statusTexts[$row['status']] ?? $row['status'];
                            
                            echo "<td>{$row['name']}</td>";
                            echo "<td>{$row['douyin_id']}</td>";
                            echo "<td>" . ($row['legal_person_name'] ?? '未知') . "</td>";
                            echo "<td>" . ($row['license_name'] ?? '未知') . "</td>";
                            echo "<td>{$row['phone']}</td>";
                            echo "<td>{$row['email']}</td>";
                            echo "<td>{$row['open_date']}</td>";
                            echo "<td style='color: #27ae60; font-weight: bold;'>¥" . number_format($row['balance'], 2) . "</td>";
                            echo "<td style='color: #f39c12; font-weight: bold;'>¥" . number_format($row['deposit'], 2) . "</td>";
                            echo "<td><span class='status-badge {$statusBadge}'>{$statusText}</span></td>";
                            break;
                            
                        case 'withdrawals':
                            $statusBadges = ['pending' => 'status-reviewing', 'completed' => 'status-active', 'transfer' => 'status-inactive'];
                            $statusTexts = ['pending' => '待提现', 'completed' => '已完成', 'transfer' => '法人转出'];
                            $statusBadge = $statusBadges[$row['status']] ?? '';
                            $statusText = $statusTexts[$row['status']] ?? $row['status'];
                            
                            echo "<td>{$row['create_time']}</td>";
                            echo "<td>" . ($row['shop_name'] ?? '未知店铺') . "</td>";
                            echo "<td>" . ($row['legal_person_name'] ?? '未知法人') . "</td>";
                            echo "<td>{$row['type']}</td>";
                            echo "<td style='color: #e74c3c; font-weight: bold;'>¥" . number_format($row['amount'], 2) . "</td>";
                            echo "<td>¥" . number_format($row['remaining_balance'], 2) . "</td>";
                            echo "<td><span class='status-badge {$statusBadge}'>{$statusText}</span></td>";
                            echo "<td>" . ($row['remark'] ?: '-') . "</td>";
                            break;
                            
                        case 'douyin_accounts':
                            echo "<td>{$row['douyin_id']}</td>";
                            echo "<td>{$row['name']}</td>";
                            echo "<td>{$row['real_name']}</td>";
                            echo "<td>{$row['phone']}</td>";
                            echo "<td>{$row['uid']}</td>";
                            echo "<td>" . ($row['contact'] ?: '-') . "</td>";
                            echo "<td>" . ($row['remark'] ?: '-') . "</td>";
                            echo "<td>{$row['create_time']}</td>";
                            break;
                            
                        case 'users':
                            $roleBadges = ['admin' => 'status-active', 'manager' => 'status-reviewing', 'user' => 'status-inactive'];
                            $roleTexts = ['admin' => '管理员', 'manager' => '经理', 'user' => '普通用户'];
                            $roleBadge = $roleBadges[$row['role']] ?? '';
                            $roleText = $roleTexts[$row['role']] ?? $row['role'];
                            
                            $statusBadge = "status-{$row['status']}";
                            $statusTexts = ['active' => '正常', 'suspended' => '暂停', 'pending' => '待审核'];
                            $statusText = $statusTexts[$row['status']] ?? $row['status'];
                            
                            echo "<td><strong>{$row['username']}</strong></td>";
                            echo "<td>" . ($row['real_name'] ?: '-') . "</td>";
                            echo "<td>{$row['email']}</td>";
                            echo "<td>" . ($row['phone'] ?: '-') . "</td>";
                            echo "<td><span class='status-badge {$roleBadge}'>{$roleText}</span></td>";
                            echo "<td><span class='status-badge {$statusBadge}'>{$statusText}</span></td>";
                            echo "<td>" . ($row['last_login'] ?: '从未登录') . "</td>";
                            echo "<td>{$row['create_time']}</td>";
                            break;
                    }
                    
                    // 操作按钮
                    echo "<td>
                            <button class='btn btn-warning' style='padding: 6px 12px; font-size: 12px;' 
                                    onclick='editRecord(\"{$module}\", \"{$row['id']}\")'>编辑</button>
                            <button class='btn btn-danger' style='padding: 6px 12px; font-size: 12px;' 
                                    onclick='deleteRecord(\"{$module}\", \"{$row['id']}\")'>删除</button>
                          </td>";
                    ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 将选项数据转为JavaScript变量 -->
<script>
// 将PHP数据转为JavaScript
window.selectOptions = <?php echo json_encode($selectOptions ?? [], JSON_UNESCAPED_UNICODE); ?>;
window.currentModule = '<?php echo $module; ?>';

// 显示添加表单
function showAddForm(module) {
    const formHtml = getFormHtml(module, {});
    showModal(formHtml);
    
    // 如果是提现管理，初始化搜索选择器
    if (module === 'withdrawals') {
        setTimeout(() => {
            initShopSelector();
        }, 100);
    }
}

// 显示编辑表单
function editRecord(module, id) {
    fetch(`index.php?action=edit&module=${module}&id=${id}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const formHtml = getFormHtml(module, result.data);
                showModal(formHtml);
                
                // 如果是提现管理，初始化搜索选择器
                if (module === 'withdrawals') {
                    setTimeout(() => {
                        initShopSelector();
                    }, 100);
                }
            } else {
                showNotification(result.message, 'error');
            }
        })
        .catch(error => {
            showNotification('获取数据失败: ' + error.message, 'error');
        });
}

// 删除记录
function deleteRecord(module, id) {
    if (!confirm('确定要删除吗？')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch(`index.php?action=delete&module=${module}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('删除成功', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'error');
        }
    })
    .catch(error => {
        showNotification('删除失败: ' + error.message, 'error');
    });
}

// 保存表单
function saveForm(module) {
    const form = document.getElementById('dataForm');
    const formData = new FormData(form);
    
    fetch(`index.php?action=save&module=${module}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showNotification('保存成功', 'success');
            closeModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(result.message, 'error');
        }
    })
    .catch(error => {
        showNotification('保存失败: ' + error.message, 'error');
    });
}

// 生成下拉选项HTML
function generateSelectOptions(optionType, selectedValue = '') {
    const options = window.selectOptions[optionType] || [];
    let html = '<option value="">请选择</option>';
    
    options.forEach(option => {
        const selected = selectedValue === option.id ? 'selected' : '';
        if (optionType === 'licenses') {
            html += `<option value="${option.id}" ${selected}>
                ${option.name} (${option.legal_person_name}) - 可开店数: ${option.shop_limit - option.used_shops}
            </option>`;
        } else if (optionType === 'shops') {
            html += `<option value="${option.id}" ${selected}>
                ${option.name} (${option.legal_person_name})
            </option>`;
        } else {
            html += `<option value="${option.id}" ${selected}>${option.name}</option>`;
        }
    });
    
    return html;
}

// 获取表单HTML
function getFormHtml(module, data = {}) {
    const isEdit = data.id ? true : false;
    const title = isEdit ? '编辑' : '添加';
    
    let fieldsHtml = '';
    
    // 根据模块生成不同的表单字段
    switch(module) {
        case 'legal_persons':
            fieldsHtml = `
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">法人姓名 *</label>
                        <input type="text" class="form-control" name="name" value="${data.name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">身份证号 *</label>
                        <input type="text" class="form-control" name="id_card" value="${data.id_card || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">联系电话 *</label>
                        <input type="tel" class="form-control" name="phone" value="${data.phone || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">邮箱</label>
                        <input type="email" class="form-control" name="email" value="${data.email || ''}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">开户行 *</label>
                        <input type="text" class="form-control" name="bank" value="${data.bank || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">银行卡号 *</label>
                        <input type="text" class="form-control" name="bank_card" value="${data.bank_card || ''}" required>
                    </div>
                </div>
            `;
            break;
            
        case 'licenses':
            fieldsHtml = `
                <div class="form-group">
                    <label class="form-label">选择法人 *</label>
                    <select class="form-control" name="legal_person_id" required>
                        ${generateSelectOptions('legal_persons', data.legal_person_id)}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">执照名称 *</label>
                    <input type="text" class="form-control" name="name" value="${data.name || ''}" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">可开店数 *</label>
                        <select class="form-control" name="shop_limit" required>
                            <option value="">请选择</option>
                            <option value="1" ${data.shop_limit == 1 ? 'selected' : ''}>1个</option>
                            <option value="2" ${data.shop_limit == 2 ? 'selected' : ''}>2个</option>
                            <option value="3" ${data.shop_limit == 3 ? 'selected' : ''}>3个</option>
                            <option value="5" ${data.shop_limit == 5 ? 'selected' : ''}>5个</option>
                            <option value="10" ${data.shop_limit == 10 ? 'selected' : ''}>10个</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">执照状态</label>
                        <select class="form-control" name="status">
                            <option value="active" ${data.status === 'active' ? 'selected' : ''}>活跃</option>
                            <option value="inactive" ${data.status === 'inactive' ? 'selected' : ''}>停用</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'shops':
            fieldsHtml = `
                <div class="form-group">
                    <label class="form-label">选择营业执照 *</label>
                    <select class="form-control" name="license_id" required onchange="updateLegalPerson(this)">
                        ${generateSelectOptions('licenses', data.license_id)}
                    </select>
                </div>
                <input type="hidden" name="legal_person_id" id="legal_person_id" value="${data.legal_person_id || ''}">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">店铺名称 *</label>
                        <input type="text" class="form-control" name="name" value="${data.name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">抖音店铺ID *</label>
                        <input type="text" class="form-control" name="douyin_id" value="${data.douyin_id || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">开店手机号 *</label>
                        <input type="tel" class="form-control" name="phone" value="${data.phone || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">主账号邮箱 *</label>
                        <input type="email" class="form-control" name="email" value="${data.email || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">开店日期 *</label>
                        <input type="date" class="form-control" name="open_date" value="${data.open_date || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">店铺状态</label>
                        <select class="form-control" name="status">
                            <option value="active" ${data.status === 'active' ? 'selected' : ''}>活跃</option>
                            <option value="inactive" ${data.status === 'inactive' ? 'selected' : ''}>停用</option>
                            <option value="reviewing" ${data.status === 'reviewing' ? 'selected' : ''}>审核中</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">可用余额 (元)</label>
                        <input type="number" class="form-control" name="balance" step="0.01" value="${data.balance || '0'}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">保证金 (元)</label>
                        <input type="number" class="form-control" name="deposit" step="0.01" value="${data.deposit || '0'}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">备注说明</label>
                    <textarea class="form-control" name="remark" rows="3">${data.remark || ''}</textarea>
                </div>
            `;
            break;
            
        case 'withdrawals': 
     fieldsHtml = ` 
         <div class="form-row"> 
             <div class="form-group"> 
                 <label class="form-label">选择店铺 *</label> 
                 
                 <!-- 可搜索的下拉选择器 --> 
                 <div class="searchable-select" id="shopSelector"> 
                     <input type="hidden" name="shop_id" id="shopIdInput" value="${data.shop_id || ''}" required> 
                     <input type="text" 
                            class="search-input" 
                            id="shopSearchInput" 
                            placeholder="搜索店铺名称或抖音ID..." 
                            autocomplete="off" 
                            readonly> 
                     <span class="dropdown-arrow">▼</span> 
                     <div class="options-container" id="optionsContainer"></div> 
                 </div> 
             </div> 
             <div class="form-group"> 
                 <label class="form-label">资金类型 *</label> 
                 <select class="form-control" name="type" required> 
                     <option value="">请选择类型</option> 
                     <option value="余额" ${data.type === '余额' ? 'selected' : ''}>余额</option> 
                     <option value="保证金" ${data.type === '保证金' ? 'selected' : ''}>保证金</option> 
                 </select> 
             </div> 
         </div> 
         <div class="form-row"> 
             <div class="form-group"> 
                 <label class="form-label">提现金额 *</label> 
                 <input type="number" class="form-control" name="amount" step="0.01" value="${data.amount || ''}" required> 
             </div> 
             <div class="form-group"> 
                 <label class="form-label">提现状态</label> 
                 <select class="form-control" name="status"> 
                     <option value="pending" ${data.status === 'pending' ? 'selected' : ''}>待提现</option> 
                     <option value="completed" ${data.status === 'completed' ? 'selected' : ''}>已完成</option> 
                     <option value="transfer" ${data.status === 'transfer' ? 'selected' : ''}>法人转出</option> 
                 </select> 
             </div> 
         </div> 
         <div class="form-group"> 
             <label class="form-label">备注说明</label> 
             <textarea class="form-control" name="remark" rows="3">${data.remark || ''}</textarea> 
         </div> 
     `; 
     break;
            
        case 'douyin_accounts':
            fieldsHtml = `
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">抖音号ID *</label>
                        <input type="text" class="form-control" name="douyin_id" value="${data.douyin_id || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">抖音号名称 *</label>
                        <input type="text" class="form-control" name="name" value="${data.name || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">实名人 *</label>
                        <input type="text" class="form-control" name="real_name" value="${data.real_name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">绑定手机号 *</label>
                        <input type="tel" class="form-control" name="phone" value="${data.phone || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">UID *</label>
                        <input type="text" class="form-control" name="uid" value="${data.uid || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">联系人</label>
                        <input type="text" class="form-control" name="contact" value="${data.contact || ''}">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">备注</label>
                    <textarea class="form-control" name="remark" rows="3">${data.remark || ''}</textarea>
                </div>
            `;
            break;
            
        case 'users':
            fieldsHtml = `
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">用户名 *</label>
                        <input type="text" class="form-control" name="username" value="${data.username || ''}" required ${isEdit ? 'readonly' : ''}>
                    </div>
                    <div class="form-group">
                        <label class="form-label">真实姓名 *</label>
                        <input type="text" class="form-control" name="real_name" value="${data.real_name || ''}" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">邮箱 *</label>
                        <input type="email" class="form-control" name="email" value="${data.email || ''}" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">联系电话</label>
                        <input type="tel" class="form-control" name="phone" value="${data.phone || ''}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">用户角色 *</label>
                        <select class="form-control" name="role" required>
                            <option value="">请选择角色</option>
                            <option value="admin" ${data.role === 'admin' ? 'selected' : ''}>管理员</option>
                            <option value="manager" ${data.role === 'manager' ? 'selected' : ''}>经理</option>
                            <option value="user" ${data.role === 'user' ? 'selected' : ''}>普通用户</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">账号状态</label>
                        <select class="form-control" name="status">
                            <option value="active" ${data.status === 'active' ? 'selected' : ''}>正常</option>
                            <option value="suspended" ${data.status === 'suspended' ? 'selected' : ''}>暂停</option>
                            <option value="pending" ${data.status === 'pending' ? 'selected' : ''}>待审核</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">登录密码 ${isEdit ? '(留空不修改)' : '*'}</label>
                    <input type="password" class="form-control" name="password" ${isEdit ? '' : 'required'}>
                    <small style="color: #7f8c8d;">密码长度至少6位</small>
                </div>
            `;
            break;
    }
    
    return `
        <h2 style="margin-bottom: 30px; color: #2c3e50;">${title}</h2>
        <form id="dataForm">
            <input type="hidden" name="id" value="${data.id || ''}">
            ${fieldsHtml}
            <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">取消</button>
                <button type="button" class="btn btn-primary" onclick="saveForm('${module}')">保存</button>
            </div>
        </form>
    `;
}

// 更新法人信息（店铺表单中使用）
function updateLegalPerson(select) {
    // 这里可以根据需要添加逻辑
    const selectedOption = select.options[select.selectedIndex];
    // 可以获取法人信息并填充到隐藏字段中
}
// 可搜索选择器类
class SearchableSelect {
    constructor(containerId, options, placeholder = '请选择...') {
        this.container = document.getElementById(containerId);
        if (!this.container) return;
        
        this.options = options;
        this.placeholder = placeholder;
        this.selectedValue = '';
        this.selectedText = '';
        this.isOpen = false;
        
        this.searchInput = this.container.querySelector('.search-input');
        this.hiddenInput = this.container.querySelector('input[type="hidden"]');
        this.optionsContainer = this.container.querySelector('.options-container');
        
        this.init();
    }
    
    init() {
        this.renderOptions();
        this.bindEvents();
        
        // 如果有预设值，设置它
        if (this.hiddenInput.value) {
            this.setValue(this.hiddenInput.value);
        } else {
            this.searchInput.value = this.placeholder;
        }
    }
    
    renderOptions() {
        this.optionsContainer.innerHTML = '';
        
        if (this.options.length === 0) {
            this.optionsContainer.innerHTML = '<div class="no-results">暂无店铺数据</div>';
            return;
        }
        
        this.options.forEach(option => {
            const optionEl = document.createElement('div');
            optionEl.className = 'option-item';
            optionEl.dataset.value = option.id;
            optionEl.innerHTML = `
                <div class="shop-name">${option.name}</div>
                <div class="shop-info">抖音ID: ${option.douyin_id} | 法人: ${option.legal_person_name}</div>
            `;
            
            optionEl.addEventListener('click', () => {
                this.selectOption(option.id, `${option.name} (${option.legal_person_name})`);
            });
            
            this.optionsContainer.appendChild(optionEl);
        });
    }
    
    bindEvents() {
        this.searchInput.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });
        
        this.searchInput.addEventListener('input', () => {
            this.filterOptions();
        });
        
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value === this.placeholder) {
                this.searchInput.value = '';
            }
            this.searchInput.removeAttribute('readonly');
            this.open();
        });
        
        this.searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                if (!this.selectedValue && this.searchInput.value === '') {
                    this.searchInput.value = this.placeholder;
                    this.searchInput.setAttribute('readonly', true);
                }
                this.close();
            }, 200);
        });
        
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                this.close();
            }
        });
        
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.selectFirstVisible();
            } else if (e.key === 'Escape') {
                this.close();
            }
        });
    }
    
    filterOptions() {
        const searchTerm = this.searchInput.value.toLowerCase();
        const optionItems = this.optionsContainer.querySelectorAll('.option-item');
        let visibleCount = 0;
        
        optionItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });
        
        let noResults = this.optionsContainer.querySelector('.no-results');
        if (visibleCount === 0 && searchTerm !== '') {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.className = 'no-results';
                this.optionsContainer.appendChild(noResults);
            }
            noResults.textContent = '未找到匹配的店铺';
            noResults.style.display = 'block';
        } else if (noResults) {
            noResults.style.display = 'none';
        }
    }
    
    selectOption(value, text) {
        this.selectedValue = value;
        this.selectedText = text;
        this.searchInput.value = text;
        this.hiddenInput.value = value;
        
        this.optionsContainer.querySelectorAll('.option-item').forEach(item => {
            item.classList.remove('selected');
            if (item.dataset.value === value) {
                item.classList.add('selected');
            }
        });
        
        this.close();
        this.searchInput.setAttribute('readonly', true);
        this.hiddenInput.dispatchEvent(new Event('change'));
    }
    
    selectFirstVisible() {
        const firstVisible = this.optionsContainer.querySelector('.option-item:not(.hidden)');
        if (firstVisible) {
            firstVisible.click();
        }
    }
    
    open() {
        this.isOpen = true;
        this.container.classList.add('open');
    }
    
    close() {
        this.isOpen = false;
        this.container.classList.remove('open');
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    setValue(value) {
        const option = this.options.find(opt => opt.id === value);
        if (option) {
            this.selectOption(option.id, `${option.name} (${option.legal_person_name})`);
        }
    }
}

// 初始化搜索选择器 - 在弹框打开时调用
function initShopSelector() {
    const shopOptions = window.selectOptions && window.selectOptions.shops ? window.selectOptions.shops : [];
    if (document.getElementById('shopSelector')) {
        window.shopSelector = new SearchableSelect('shopSelector', shopOptions, '请选择店铺...');
    }
}
</script>