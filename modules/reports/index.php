<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

$from     = $_GET['from'] ?? date('Y-01-01');
$to       = $_GET['to']   ?? date('Y-m-d');
$report   = $_GET['report'] ?? 'master';
$barangay = $_GET['barangay'] ?? '';

$brgys = $db->query('SELECT DISTINCT barangay FROM households ORDER BY barangay')->fetchAll(PDO::FETCH_COLUMN);

// ── Queries based on report type ──────────────────────────────
if ($report === 'master') {
    $sql  = "SELECT h.* FROM households h WHERE 1=1";
    $prms = [];
    if ($barangay) { $sql .= " AND h.barangay=?"; $prms[]=$barangay; }
    $sql .= " ORDER BY h.family_name";
    $stmt = $db->prepare($sql); $stmt->execute($prms);
    $rows = $stmt->fetchAll();
    $reportTitle = 'Household Master List';

} elseif ($report === 'financial') {
    $sql  = "SELECT h.*, (h.monthly_income - h.monthly_expenses) as balance FROM households h WHERE 1=1";
    $prms = [];
    if ($barangay) { $sql .= " AND h.barangay=?"; $prms[]=$barangay; }
    $sql .= " ORDER BY h.economic_status, h.family_name";
    $stmt = $db->prepare($sql); $stmt->execute($prms);
    $rows = $stmt->fetchAll();
    $reportTitle = 'Financial Status Report';

} elseif ($report === 'assistance') {
    $sql  = "SELECT a.*, h.family_name, h.barangay, u.name as staff_name
             FROM assistance_records a JOIN households h ON a.household_id=h.id
             LEFT JOIN users u ON a.staff_id=u.id
             WHERE a.date_released BETWEEN ? AND ?";
    $prms = [$from, $to];
    if ($barangay) { $sql .= " AND h.barangay=?"; $prms[]=$barangay; }
    $sql .= " ORDER BY a.date_released DESC";
    $stmt = $db->prepare($sql); $stmt->execute($prms);
    $rows = $stmt->fetchAll();
    $reportTitle = 'Assistance Distribution Report';

} else { // barangay summary
    $sql  = "SELECT h.barangay,
                COUNT(*) as total,
                SUM(CASE WHEN economic_status='stable' THEN 1 ELSE 0 END) as stable,
                SUM(CASE WHEN economic_status='at_risk' THEN 1 ELSE 0 END) as at_risk,
                SUM(CASE WHEN economic_status='vulnerable' THEN 1 ELSE 0 END) as vulnerable,
                AVG(h.monthly_income) as avg_income,
                AVG(h.monthly_expenses) as avg_expenses
             FROM households h GROUP BY h.barangay ORDER BY h.barangay";
    $stmt = $db->prepare($sql); $stmt->execute([]);
    $rows = $stmt->fetchAll();
    $reportTitle = 'Barangay Summary Report';
}
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-file-earmark-bar-graph-fill me-2 text-primary"></i>Reports</div>
        <div class="page-header-sub">Generate printable reports and summaries</div>
    </div>
    <button type="button" class="btn btn-outline-secondary no-print" data-print>
        <i class="bi bi-printer me-1"></i>Print Report
    </button>
</div>

<!-- Filters -->
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Report Type</label>
                <select name="report" class="form-select">
                    <option value="master"     <?= $report==='master'?'selected':'' ?>>Household Master List</option>
                    <option value="financial"  <?= $report==='financial'?'selected':'' ?>>Financial Status</option>
                    <option value="assistance" <?= $report==='assistance'?'selected':'' ?>>Assistance Distribution</option>
                    <option value="barangay"   <?= $report==='barangay'?'selected':'' ?>>Barangay Summary</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Barangay</label>
                <select name="barangay" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($brgys as $b): ?>
                    <option value="<?= clean($b) ?>" <?= $barangay===$b?'selected':'' ?>><?= clean($b) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Date From</label>
                <input type="date" name="from" class="form-control" value="<?= $from ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Date To</label>
                <input type="date" name="to" class="form-control" value="<?= $to ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Generate Report</button>
            </div>
            <div class="col-12">
                <div class="alert alert-info mb-0">
                    Select report filters and click <strong>Generate Report</strong>. After the report loads, use <strong>Print Report</strong> to print the generated report with the LARTS logo.
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Print Header (shown only on print) -->
<div class="d-none d-print-block mb-4 text-center report-print-header">
    <img src="<?= BASE_URL ?>/assets/images/larts-logo.svg" alt="LARTS Logo" class="report-print-logo">
    <div>
        <h4 style="font-weight:800;">DAVAO DEL NORTE STATE COLLEGE</h4>
        <h5>Livelihood Assistance &amp; Resource Tracking System</h5>
        <h6><?= $reportTitle ?></h6>
        <p style="font-size:0.85rem;">Generated: <?= date('F d, Y') ?><?= $barangay ? ' | Barangay: '.$barangay : '' ?></p>
    </div>
    <hr>
</div>

<!-- Report Content -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i><?= $reportTitle ?></span>
        <span class="badge bg-primary"><?= count($rows) ?> records</span>
    </div>
    <div class="table-wrapper">

    <?php if ($report === 'master'): ?>
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Family Name</th><th>Head</th><th>Barangay</th><th>Members</th><th>Contact</th><th>Monthly Income</th><th>Monthly Expenses</th><th>Status</th><th>Registered</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?= clean($r['family_name']) ?></strong></td>
                <td><?= clean($r['head_name']) ?></td>
                <td><?= clean($r['barangay']) ?></td>
                <td><?= $r['members_count'] ?></td>
                <td><?= clean($r['contact']) ?></td>
                <td class="text-money"><?= formatMoney($r['monthly_income']) ?></td>
                <td class="text-money"><?= formatMoney($r['monthly_expenses']) ?></td>
                <td><?= statusBadge($r['economic_status']) ?></td>
                <td><?= $r['date_registered'] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php elseif ($report === 'financial'): ?>
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Family Name</th><th>Barangay</th><th>Monthly Income</th><th>Monthly Expenses</th><th>Balance</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): $bal = $r['balance']; ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?= clean($r['family_name']) ?></strong></td>
                <td><?= clean($r['barangay']) ?></td>
                <td class="text-money text-success"><?= formatMoney($r['monthly_income']) ?></td>
                <td class="text-money text-danger"><?= formatMoney($r['monthly_expenses']) ?></td>
                <td class="text-money <?= $bal>=0?'text-success':'text-danger' ?> fw-bold"><?= formatMoney($bal) ?></td>
                <td><?= statusBadge($r['economic_status']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot style="background:var(--surface-2);">
                <tr>
                    <td colspan="3" class="fw-bold">TOTALS</td>
                    <td class="text-money text-success fw-bold"><?= formatMoney(array_sum(array_column($rows,'monthly_income'))) ?></td>
                    <td class="text-money text-danger fw-bold"><?= formatMoney(array_sum(array_column($rows,'monthly_expenses'))) ?></td>
                    <td class="text-money fw-bold"><?= formatMoney(array_sum(array_column($rows,'balance'))) ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

    <?php elseif ($report === 'assistance'): ?>
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Household</th><th>Barangay</th><th>Type</th><th>Amount</th><th>Date</th><th>Remarks</th><th>Staff</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= clean($r['family_name']) ?></td>
                <td><?= clean($r['barangay']) ?></td>
                <td><?= clean($r['assistance_type']) ?></td>
                <td class="text-money fw-bold"><?= formatMoney($r['amount']) ?></td>
                <td><?= $r['date_released'] ?></td>
                <td><?= clean($r['remarks'] ?? '') ?></td>
                <td><?= clean($r['staff_name'] ?? 'N/A') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot style="background:var(--surface-2);">
                <tr><td colspan="4" class="fw-bold">TOTAL ASSISTANCE</td>
                <td class="text-money fw-bold"><?= formatMoney(array_sum(array_column($rows,'amount'))) ?></td>
                <td colspan="3"></td></tr>
            </tfoot>
        </table>

    <?php else: // barangay summary ?>
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Barangay</th><th>Total HH</th><th>Stable</th><th>At Risk</th><th>Vulnerable</th><th>Avg Income</th><th>Avg Expenses</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $i => $r): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?= clean($r['barangay']) ?></strong></td>
                <td><?= $r['total'] ?></td>
                <td class="text-success"><?= $r['stable'] ?></td>
                <td class="text-warning"><?= $r['at_risk'] ?></td>
                <td class="text-danger"><?= $r['vulnerable'] ?></td>
                <td class="text-money"><?= formatMoney($r['avg_income']) ?></td>
                <td class="text-money"><?= formatMoney($r['avg_expenses']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>