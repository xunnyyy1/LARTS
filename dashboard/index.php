<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/config.php';
$dashCssVer = @filemtime(__DIR__ . '/../assets/css/dashboard.css') ?: time();
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . '/assets/css/dashboard.css?v=' . $dashCssVer . '">';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

$totalHH      = $db->query('SELECT COUNT(*) FROM households')->fetchColumn();
$stableHH     = $db->query("SELECT COUNT(*) FROM households WHERE economic_status='stable'")->fetchColumn();
$atRiskHH     = $db->query("SELECT COUNT(*) FROM households WHERE economic_status='at_risk'")->fetchColumn();
$vulnerableHH = $db->query("SELECT COUNT(*) FROM households WHERE economic_status='vulnerable'")->fetchColumn();
$totalAssist  = $db->query('SELECT COALESCE(SUM(amount),0) FROM assistance_records')->fetchColumn();
$assistCount  = $db->query('SELECT COUNT(*) FROM assistance_records')->fetchColumn();
$totalIncome  = $db->query('SELECT COALESCE(SUM(amount),0) FROM income_records')->fetchColumn();
$totalExpense = $db->query('SELECT COALESCE(SUM(amount),0) FROM expense_records')->fetchColumn();

$brgys  = $db->query('SELECT barangay, COUNT(*) as total FROM households GROUP BY barangay ORDER BY total DESC')->fetchAll();
$trend  = $db->query("SELECT DATE_FORMAT(date_released,'%b %Y') as month,
    SUM(amount) as total FROM assistance_records
    GROUP BY YEAR(date_released), MONTH(date_released)
    ORDER BY date_released ASC LIMIT 6")->fetchAll();
$recent = $db->query('SELECT * FROM households ORDER BY date_registered DESC LIMIT 6')->fetchAll();
$alerts = $db->query("SELECT COUNT(*) FROM households WHERE economic_status='vulnerable'")->fetchColumn();

$firstName = explode(' ', trim($user['name']))[0];
$stablePct = $totalHH > 0 ? round(($stableHH / $totalHH) * 100) : 0;
?>

<div class="dashboard-hero">
    <div class="dashboard-hero-text">
        <span class="dashboard-hero-badge"><i class="bi bi-grid-1x2-fill"></i> Overview</span>
        <h1 class="dashboard-hero-title">Welcome back, <?= clean($firstName) ?></h1>
        <p class="dashboard-hero-sub">Livelihood assistance &amp; household tracking at a glance</p>
        <div class="dashboard-hero-actions">
            <a href="<?= BASE_URL ?>/modules/households/index.php"><i class="bi bi-people-fill"></i> Households</a>
            <a href="<?= BASE_URL ?>/modules/assistance/index.php"><i class="bi bi-gift-fill"></i> Assistance</a>
            <a href="<?= BASE_URL ?>/modules/reports/index.php"><i class="bi bi-file-earmark-bar-graph"></i> Reports</a>
        </div>
    </div>
    <div class="dashboard-clock-card">
        <div class="dashboard-clock-label"><i class="bi bi-clock"></i> Local Time</div>
        <div class="dashboard-clock-time" data-live-time><?= date('H:i:s') ?></div>
        <div class="dashboard-clock-date" data-live-date><?= date('l, M d, Y') ?></div>
    </div>
</div>

<div class="section-heading mb-3">
    <h2 class="section-title">Household Overview</h2>
    <span class="section-meta"><?= number_format($totalHH) ?> registered &middot; <?= $stablePct ?>% stable</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-card--primary">
            <div class="stat-card-inner">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-value"><?= number_format($totalHH) ?></div>
                    <div class="stat-label">Total Households</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-card--success">
            <div class="stat-card-inner">
                <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-value"><?= number_format($stableHH) ?></div>
                    <div class="stat-label">Financially Stable</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-card--warning">
            <div class="stat-card-inner">
                <div class="stat-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-value"><?= number_format($atRiskHH) ?></div>
                    <div class="stat-label">At Risk</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card stat-card--danger">
            <div class="stat-card-inner">
                <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-value"><?= number_format($vulnerableHH) ?></div>
                    <div class="stat-label">Vulnerable</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section-heading mb-3">
    <h2 class="section-title">Financial Summary</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card stat-card--primary stat-card--money">
            <div class="stat-card-inner">
                <div class="stat-icon"><i class="bi bi-gift-fill"></i></div>
                <div class="stat-body">
                    <div class="stat-value stat-value--money"><?= formatMoney($totalAssist) ?></div>
                    <div class="stat-label">Total Assistance Released</div>
                    <div class="stat-delta"><i class="bi bi-receipt"></i> <?= $assistCount ?> transactions</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-card--success stat-card--money">
            <div class="stat-card-inner">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-body">
                    <div class="stat-value stat-value--money"><?= formatMoney($totalIncome) ?></div>
                    <div class="stat-label">Total Recorded Income</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-card--danger stat-card--money">
            <div class="stat-card-inner">
                <div class="stat-icon"><i class="bi bi-receipt"></i></div>
                <div class="stat-body">
                    <div class="stat-value stat-value--money"><?= formatMoney($totalExpense) ?></div>
                    <div class="stat-label">Total Recorded Expenses</div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($alerts > 0): ?>
<div class="alert-banner mb-4">
    <div class="alert-banner-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
    <div class="alert-banner-text">
        <strong><?= $alerts ?> household(s)</strong> are financially vulnerable and may need immediate assistance review.
    </div>
    <a href="<?= BASE_URL ?>/modules/households/index.php?status=vulnerable" class="alert-banner-action">
        View now <i class="bi bi-arrow-right"></i>
    </a>
</div>
<?php endif; ?>

<div class="section-heading mb-3">
    <h2 class="section-title">Analytics</h2>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card chart-card h-100">
            <div class="card-header">
                <span class="card-header-icon"><i class="bi bi-pie-chart-fill"></i></span>
                Economic Status
            </div>
            <div class="card-body chart-card-body">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card chart-card h-100">
            <div class="card-header">
                <span class="card-header-icon"><i class="bi bi-bar-chart-fill"></i></span>
                Households by Barangay
            </div>
            <div class="card-body chart-card-body">
                <canvas id="brgyChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <span class="card-header-icon"><i class="bi bi-graph-up-arrow"></i></span>
                Monthly Assistance Trend
            </div>
            <div class="card-body chart-card-body chart-card-body--wide">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card data-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><span class="card-header-icon"><i class="bi bi-clock-history"></i></span> Recently Registered Households</span>
        <a href="<?= BASE_URL ?>/modules/households/index.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Family Name</th>
                    <th>Head</th>
                    <th>Barangay</th>
                    <th>Monthly Income</th>
                    <th>Monthly Expenses</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent as $hh): ?>
            <tr>
                <td><strong><?= clean($hh['family_name']) ?></strong></td>
                <td><?= clean($hh['head_name']) ?></td>
                <td><?= clean($hh['barangay']) ?></td>
                <td class="text-money"><?= formatMoney($hh['monthly_income']) ?></td>
                <td class="text-money"><?= formatMoney($hh['monthly_expenses']) ?></td>
                <td><?= statusBadge($hh['economic_status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$brgyLabels  = json_encode(array_column($brgys, 'barangay'));
$brgyData    = json_encode(array_column($brgys, 'total'));
$trendLabels = json_encode(array_column($trend, 'month'));
$trendData   = json_encode(array_column($trend, 'total'));
$extraScript = <<<JS
<script>
Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
Chart.defaults.color = '#6b7280';

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Stable','At Risk','Vulnerable'],
        datasets: [{
            data: [$stableHH, $atRiskHH, $vulnerableHH],
            backgroundColor: ['#10b981','#f59e0b','#ef4444'],
            borderWidth: 3,
            borderColor: '#fff',
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 16, usePointStyle: true, font: { size: 12, weight: '600' } }
            }
        }
    }
});

new Chart(document.getElementById('brgyChart'), {
    type: 'bar',
    data: {
        labels: $brgyLabels,
        datasets: [{
            label: 'Households',
            data: $brgyData,
            backgroundColor: 'rgba(26,86,219,0.85)',
            hoverBackgroundColor: '#1a56db',
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.04)' } },
            x: { grid: { display: false } }
        }
    }
});

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: $trendLabels,
        datasets: [{
            label: 'Assistance (₱)',
            data: $trendData,
            borderColor: '#1a56db',
            backgroundColor: 'rgba(26,86,219,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: '#1a56db',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
            x: { grid: { display: false } }
        }
    }
});
(function tickClock(){
    var p=function(n){return String(n).padStart(2,'0');};
    var now=new Date();
    var t=p(now.getHours())+':'+p(now.getMinutes())+':'+p(now.getSeconds());
    var days=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var d=days[now.getDay()]+', '+months[now.getMonth()]+' '+now.getDate()+', '+now.getFullYear();
    document.querySelectorAll('[data-live-time]').forEach(function(el){el.textContent=t;});
    document.querySelectorAll('[data-live-date]').forEach(function(el){el.textContent=d;});
    var lt=document.getElementById('liveClockTime'),ld=document.getElementById('liveClockDate');
    if(lt)lt.textContent=t; if(ld)ld.textContent=d;
})();
setInterval(function(){
    var p=function(n){return String(n).padStart(2,'0');};
    var now=new Date();
    var t=p(now.getHours())+':'+p(now.getMinutes())+':'+p(now.getSeconds());
    var days=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    var months=['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    var d=days[now.getDay()]+', '+months[now.getMonth()]+' '+now.getDate()+', '+now.getFullYear();
    document.querySelectorAll('[data-live-time]').forEach(function(el){el.textContent=t;});
    document.querySelectorAll('[data-live-date]').forEach(function(el){el.textContent=d;});
},1000);
</script>
JS;
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
