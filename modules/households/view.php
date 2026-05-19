<?php
$pageTitle = 'Household Detail';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$hh = $db->prepare('SELECT * FROM households WHERE id=?');
$hh->execute([$id]);
$h = $hh->fetch();
if (!$h) { setFlash('danger','Household not found.'); header('Location: index.php'); exit; }

$incomes    = $db->prepare('SELECT * FROM income_records WHERE household_id=? ORDER BY date_recorded DESC');
$incomes->execute([$id]);
$incRecs    = $incomes->fetchAll();

$exps       = $db->prepare('SELECT * FROM expense_records WHERE household_id=? ORDER BY date_recorded DESC');
$exps->execute([$id]);
$expRecs    = $exps->fetchAll();

$assists    = $db->prepare('SELECT a.*, u.name as staff_name FROM assistance_records a
    LEFT JOIN users u ON a.staff_id=u.id WHERE a.household_id=? ORDER BY a.date_released DESC');
$assists->execute([$id]);
$assRecs    = $assists->fetchAll();

$totalInc  = array_sum(array_column($incRecs, 'amount'));
$totalExp  = array_sum(array_column($expRecs, 'amount'));
$balance   = $totalInc - $totalExp;
?>

<div class="page-header">
    <div>
        <div class="page-header-title">
            <a href="index.php" class="text-decoration-none text-muted me-2"><i class="bi bi-arrow-left"></i></a>
            <?= clean($h['family_name']) ?> Family
        </div>
        <div class="page-header-sub"><?= clean($h['head_name']) ?> &bull; <?= clean($h['barangay']) ?></div>
    </div>
    <div class="d-flex gap-2">
        <?= statusBadge($h['economic_status']) ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle text-primary"></i> Household Info</div>
            <div class="card-body" style="font-size:0.875rem;">
                <table class="table table-borderless mb-0" style="font-size:0.875rem;">
                    <tr><th style="width:40%;color:var(--text-muted);">ID</th><td>#<?= $h['id'] ?></td></tr>
                    <tr><th style="color:var(--text-muted);">Head</th><td><?= clean($h['head_name']) ?></td></tr>
                    <tr><th style="color:var(--text-muted);">Barangay</th><td><?= clean($h['barangay']) ?></td></tr>
                    <tr><th style="color:var(--text-muted);">Address</th><td><?= clean($h['address']) ?></td></tr>
                    <tr><th style="color:var(--text-muted);">Contact</th><td><?= clean($h['contact']) ?></td></tr>
                    <tr><th style="color:var(--text-muted);">Members</th><td><?= $h['members_count'] ?></td></tr>
                    <tr><th style="color:var(--text-muted);">Registered</th><td><?= $h['date_registered'] ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="row g-3">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#dcfce7;color:#057a55;"><i class="bi bi-cash-stack"></i></div>
                    <div class="stat-value text-success"><?= formatMoney($totalInc) ?></div>
                    <div class="stat-label">Total Recorded Income</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fee2e2;color:#c81e1e;"><i class="bi bi-receipt"></i></div>
                    <div class="stat-value text-danger"><?= formatMoney($totalExp) ?></div>
                    <div class="stat-label">Total Recorded Expenses</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e8f0fe;color:var(--primary);"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-value <?= $balance>=0?'text-success':'text-danger' ?>"><?= formatMoney($balance) ?></div>
                    <div class="stat-label">Net Balance</div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fef3c7;color:#c27803;"><i class="bi bi-gift-fill"></i></div>
                    <div class="stat-value"><?= formatMoney(array_sum(array_column($assRecs,'amount'))) ?></div>
                    <div class="stat-label">Total Assistance Received</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="card">
    <div class="card-header p-0" style="border-bottom:0;">
        <ul class="nav nav-tabs px-3 pt-2" id="detailTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#incTab">Income (<?= count($incRecs) ?>)</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#expTab">Expenses (<?= count($expRecs) ?>)</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#assTab">Assistance (<?= count($assRecs) ?>)</a></li>
        </ul>
    </div>
    <div class="tab-content">
        <!-- Income Tab -->
        <div class="tab-pane fade show active" id="incTab">
            <div class="table-wrapper">
                <table class="table mb-0">
                    <thead><tr><th>Type</th><th>Amount</th><th>Date</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php if (empty($incRecs)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No income records</td></tr>
                    <?php else: foreach ($incRecs as $r): ?>
                    <tr>
                        <td><?= clean($r['income_type']) ?></td>
                        <td class="text-money text-success"><?= formatMoney($r['amount']) ?></td>
                        <td><?= $r['date_recorded'] ?></td>
                        <td class="text-muted"><?= clean($r['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Expense Tab -->
        <div class="tab-pane fade" id="expTab">
            <div class="table-wrapper">
                <table class="table mb-0">
                    <thead><tr><th>Category</th><th>Amount</th><th>Date</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php if (empty($expRecs)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No expense records</td></tr>
                    <?php else: foreach ($expRecs as $r): ?>
                    <tr>
                        <td><?= clean($r['expense_category']) ?></td>
                        <td class="text-money text-danger"><?= formatMoney($r['amount']) ?></td>
                        <td><?= $r['date_recorded'] ?></td>
                        <td class="text-muted"><?= clean($r['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Assistance Tab -->
        <div class="tab-pane fade" id="assTab">
            <div class="table-wrapper">
                <table class="table mb-0">
                    <thead><tr><th>Type</th><th>Amount</th><th>Date Released</th><th>Remarks</th><th>Staff</th></tr></thead>
                    <tbody>
                    <?php if (empty($assRecs)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No assistance records</td></tr>
                    <?php else: foreach ($assRecs as $r): ?>
                    <tr>
                        <td><?= clean($r['assistance_type']) ?></td>
                        <td class="text-money"><?= formatMoney($r['amount']) ?></td>
                        <td><?= $r['date_released'] ?></td>
                        <td class="text-muted"><?= clean($r['remarks'] ?? '') ?></td>
                        <td><?= clean($r['staff_name'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>