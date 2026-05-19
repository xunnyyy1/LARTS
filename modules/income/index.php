<?php
$pageTitle = 'Income Records';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isStaff()) {
    $hhId   = (int)$_POST['household_id'];
    $type   = trim($_POST['income_type'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $date   = $_POST['date_recorded'] ?? date('Y-m-d');
    $notes  = trim($_POST['notes'] ?? '');

    if ($hhId && $type && $amount > 0) {
        $db->prepare('INSERT INTO income_records (household_id,income_type,amount,date_recorded,notes) VALUES (?,?,?,?,?)')
           ->execute([$hhId,$type,$amount,$date,$notes]);
        // Recalculate household monthly income
        $total = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM income_records WHERE household_id=? AND MONTH(date_recorded)=MONTH(CURDATE()) AND YEAR(date_recorded)=YEAR(CURDATE())');
        $total->execute([$hhId]);
        $newInc = $total->fetchColumn();
        $exp = $db->prepare('SELECT monthly_expenses FROM households WHERE id=?');
        $exp->execute([$hhId]);
        $curExp = (float)$exp->fetchColumn();
        $status = computeStatus($newInc, $curExp);
        $db->prepare('UPDATE households SET monthly_income=?,economic_status=? WHERE id=?')
           ->execute([$newInc,$status,$hhId]);
        setFlash('success','Income record added.');
    } else {
        setFlash('danger','Please fill in all fields.');
    }
    header('Location: index.php');
    exit;
}

// Handle delete
if (isset($_GET['del']) && isAdmin()) {
    $db->prepare('DELETE FROM income_records WHERE id=?')->execute([(int)$_GET['del']]);
    setFlash('success','Record deleted.');
    header('Location: index.php');
    exit;
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT i.*, h.family_name, h.barangay FROM income_records i
        JOIN households h ON i.household_id=h.id";
$params = [];
if ($search) {
    $sql .= " WHERE h.family_name LIKE ? OR i.income_type LIKE ?";
    $params = ["%$search%","%$search%"];
}
$sql .= " ORDER BY i.date_recorded DESC LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$households = $db->query('SELECT id, family_name, barangay FROM households ORDER BY family_name')->fetchAll();

$incomeTypes = ['Salary','Small Business','Remittance','Livelihood Assistance','Pension','Part-time Job','Other'];
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-cash-stack me-2 text-success"></i>Income Records</div>
        <div class="page-header-sub">Track monthly income sources per household</div>
    </div>
    <?php if (isStaff()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#incModal">
        <i class="bi bi-plus-lg me-1"></i>Add Income
    </button>
    <?php endif; ?>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" style="max-width:240px;" placeholder="Search household / type…" value="<?= clean($search) ?>">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            <?php if ($search): ?><a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
        </form>
    </div>
</div>

<div class="card" style="border-radius:0 0 var(--radius) var(--radius);">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead>
                <tr><th>#</th><th>Household</th><th>Barangay</th><th>Income Type</th><th>Amount</th><th>Date</th><th>Notes</th><?php if(isAdmin()):?><th>Actions</th><?php endif;?></tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="empty-state"><i class="bi bi-inbox"></i><p>No records found.</p></td></tr>
            <?php else: foreach ($rows as $i => $r): ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $i+1 ?></td>
                <td><a href="../households/view.php?id=<?= $r['household_id'] ?>" class="text-decoration-none fw-600"><?= clean($r['family_name']) ?></a></td>
                <td><?= clean($r['barangay']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= clean($r['income_type']) ?></span></td>
                <td class="text-money text-success fw-600"><?= formatMoney($r['amount']) ?></td>
                <td><?= $r['date_recorded'] ?></td>
                <td class="text-muted"><?= clean($r['notes'] ?? '') ?></td>
                <?php if(isAdmin()):?>
                <td><a href="?del=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete this income record?"><i class="bi bi-trash"></i></a></td>
                <?php endif;?>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isStaff()): ?>
<div class="modal fade" id="incModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cash-stack me-2"></i>Add Income Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Household <span class="text-danger">*</span></label>
                        <select name="household_id" class="form-select" required>
                            <option value="">— Select Household —</option>
                            <?php foreach ($households as $hh): ?>
                            <option value="<?= $hh['id'] ?>"><?= clean($hh['family_name']) ?> (<?= clean($hh['barangay']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Income Type <span class="text-danger">*</span></label>
                        <select name="income_type" class="form-select" required>
                            <option value="">— Select Type —</option>
                            <?php foreach ($incomeTypes as $t): ?>
                            <option value="<?= $t ?>"><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="date_recorded" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional notes…">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>