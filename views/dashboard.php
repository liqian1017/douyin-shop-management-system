<!-- 数据概览页面 -->
<div id="dashboard-section" class="section">
    <!-- 统计卡片 -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3>法人总数</h3>
            <div class="number"><?php echo $stats['legalPersonCount'] ?? 0; ?></div>
            <div class="change">系统注册法人</div>
        </div>
        <div class="stat-card warning">
            <h3>营业执照总数</h3>
            <div class="number"><?php echo $stats['licenseCount'] ?? 0; ?></div>
            <div class="change">可开店铺管理</div>
        </div>
        <div class="stat-card success">
            <h3>已开店铺</h3>
            <div class="number"><?php echo $stats['shopCount'] ?? 0; ?></div>
            <div class="change">活跃率 <?php echo $stats['shopCount'] > 0 ? round($stats['activeShopCount'] / $stats['shopCount'] * 100, 1) : 0; ?>%</div>
        </div>
        <div class="stat-card info">
            <h3>活跃店铺</h3>
            <div class="number"><?php echo $stats['activeShopCount'] ?? 0; ?></div>
            <div class="change">正常运营中</div>
        </div>
        <div class="stat-card success">
            <h3>总资金</h3>
            <div class="number">¥<?php echo number_format(($stats['totalFunds'] ?? 0) / 10000, 1); ?>万</div>
            <div class="change">余额+保证金</div>
        </div>
        <div class="stat-card danger">
            <h3>待提现金额</h3>
            <div class="number">¥<?php echo number_format(($stats['pendingAmount'] ?? 0) / 10000, 1); ?>万</div>
            <div class="change">需要处理</div>
        </div>
    </div>

    <!-- 图表容器 -->
    <div class="chart-container">
        <div id="fundFlowChart" style="height: 100%;"></div>
    </div>

    <!-- 资金汇总 -->
    <div class="fund-summary">
        <div class="fund-card">
            <h4>可用余额分布</h4>
            <div class="amount">¥<?php echo number_format(($stats['totalBalance'] ?? 0) / 10000, 1); ?>万</div>
        </div>
        <div class="fund-card deposit">
            <h4>保证金分布</h4>
            <div class="amount">¥<?php echo number_format(($stats['totalDeposit'] ?? 0) / 10000, 1); ?>万</div>
        </div>
        <div class="fund-card withdraw">
            <h4>待提现资金</h4>
            <div class="amount">¥<?php echo number_format(($stats['pendingAmount'] ?? 0) / 10000, 1); ?>万</div>
        </div>
    </div>
</div>

<script>
// 初始化图表
document.addEventListener('DOMContentLoaded', function() {
    if (typeof echarts !== 'undefined') {
        const chart = echarts.init(document.getElementById('fundFlowChart'));
        chart.setOption({
            title: { text: '最近7天资金流动趋势', left: 'center' },
            tooltip: { trigger: 'axis' },
            legend: { top: 30, data: ['充值', '提现', '余额变动'] },
            grid: { left: '5%', right: '5%', bottom: '10%', top: '20%', containLabel: true },
            xAxis: { 
                type: 'category', 
                data: <?php 
                    // 生成最近7天的日期
                    $dates = [];
                    for ($i = 6; $i >= 0; $i--) {
                        $dates[] = date('m-d', strtotime("-$i days"));
                    }
                    echo json_encode($dates);
                ?>
            },
            yAxis: { type: 'value', name: '金额(万元)' },
            series: [
                { 
                    name: '充值', 
                    type: 'line', 
                    smooth: true, 
                    data: [23.5, 18.9, 34.2, 42.1, 28.7, 51.3, 39.6], 
                    itemStyle: { color: '#27ae60' } 
                },
                { 
                    name: '提现', 
                    type: 'line', 
                    smooth: true, 
                    data: [15.2, 25.8, 19.3, 31.5, 22.9, 28.4, 35.7], 
                    itemStyle: { color: '#e74c3c' } 
                },
                { 
                    name: '余额变动', 
                    type: 'bar', 
                    data: [8.3, -6.9, 14.9, 10.6, 5.8, 22.9, 3.9], 
                    itemStyle: { 
                        color: function(params) {
                            return params.value >= 0 ? '#3498db' : '#f39c12';
                        }
                    } 
                }
            ]
        });

        // 响应式
        window.addEventListener('resize', function() {
            chart.resize();
        });
    }
});
</script>