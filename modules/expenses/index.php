<?php
$pageTitle = 'Expense Records';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isStaff()) {
    $hhId     = (int)$_POST['household_id'];
    $category = trim($_POST['expense_category'] ?? '');
    $amount   = (float)($_POST['amount'] ?? 0);
    $date     = $_POST['date_recorded'] ?? date('Y-m-d');
    $notes    = trim($_POST['notes'] ?? '');

    if ($hhId && $category && $amount > 0) {
        $db->prepare('INSERT INTO expense_records (household_id,expense_category,amount,date_recorded,notes) VALUES (?,?,?,?,?)')
           ->execute([$hhId,$category,$amount,$date,$notes]);
        // Recalculate
        $total = $db->prepare('SELECT COALESCE(SUM(amount),0) FROM expense_records WHERE household_id=? AND MONTH(date_recorded)=MONTH(CURDATE()) AND YEAR(date_recorded)=YEAR(CURDATE())');
        $total->execute([$hhId]);
        $newExp = $total->fetchColumn();
        $inc = $db->prepare('SELECT monthly_income FROM households WHERE id=?');
        $inc->execute([$hhId]);
        $curInc = (float)$inc->fetchColumn();
        $status = computeStatus($curInc, $newExp);
        $db->prepare('UPDATE households SET monthly_expenses=?,economic_status=? WHERE id=?')
           ->execute([$newExp,$status,$hhId]);
        setFlash('success','Expense record added.');
    } else {
        setFlash('danger','Please fill in all required fields.');
    }
    header('Location: index.php'); exit;
}

if (isset($_GET['del']) && isAdmin()) {
    $db->prepare('DELETE FROM expense_records WHERE id=?')->execute([(int)$_GET['del']]);
    setFlash('success','Record deleted.');
    header('Location: index.php'); exit;
}

$search = trim($_GET['search'] ?? '');
$sql = "SELECT e.*, h.family_name, h.barangay FROM expense_records e JOIN households h ON e.household_id=h.id";
$params = [];
if ($search) { $sql .= " WHERE h.family_name LIKE ? OR e.expense_category LIKE ?"; $params=["%$search%","%$search%"]; }
$sql .= " ORDER BY e.date_recorded DESC LIMIT 100";
$stmt = $db->prepare($sql); $stmt->execute($params); $rows = $stmt->fetchAll();
$households = $db->query('SELECT id,family_name,barangay FROM households ORDER BY family_name')->fetchAll();
$expCategories = ['Food','Utilities','Transportation','Education','Medical','Housing','Clothing','Miscellaneous'];
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-receipt me-2 text-danger"></i>Expense Records</div>
        <div class="page-header-sub">Track monthly expenses per household by category</div>
    </div>
    <?php if (isStaff()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#expModal">
        <i class="bi bi-plus-lg me-1"></i>Add Expense
    </button>
    <?php endif; ?>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" style="max-width:240px;" placeholder="Search…" value="<?= clean($search) ?>">
            <button class="btn btn-primary"><i class="bi bi-search"></i></button>
            <?php if($search):?><a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif;?>
        </form>
    </div>
</div>

<div class="card" style="border-radius:0 0 var(--radius) var(--radius);">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Household</th><th>Barangay</th><th>Category</th><th>Amount</th><th>Date</th><th>Notes</th><?php if(isAdmin()):?><th>Del</th><?php endif;?></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="empty-state"><i class="bi bi-inbox"></i><p>No records found.</p></td></tr>
            <?php else: foreach ($rows as $i => $r): ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $i+1 ?></td>
                <td><a href="../households/view.php?id=<?= $r['household_id'] ?>" class="text-decoration-none fw-600"><?= clean($r['family_name']) ?></a></td>
                <td><?= clean($r['barangay']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= clean($r['expense_category']) ?></span></td>
                <td class="text-money text-danger fw-600"><?= formatMoney($r['amount']) ?></td>
                <td><?= $r['date_recorded'] ?></td>
                <td class="text-muted"><?= clean($r['notes'] ?? '') ?></td>
                <?php if(isAdmin()):?><td><a href="?del=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete?"><i class="bi bi-trash"></i></a></td><?php endif;?>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isStaff()): ?>
<div class="modal fade" id="expModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Add Expense Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Household <span class="text-danger">*</span></label>
                        <select name="household_id" class="form-select" required>
                            <option value="">— Select —</option>
                            <?php foreach ($households as $hh): ?>
                            <option value="<?= $hh['id'] ?>"><?= clean($hh['family_name']) ?> (<?= clean($hh['barangay']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Expense Category <span class="text-danger">*</span></label>
                        <select name="expense_category" class="form-select" required>
                            <option value="">— Select —</option>
                            <?php foreach ($expCategories as $c): ?>
                            <option value="<?= $c ?>"><?= $c ?></option>
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
                        <input type="text" name="notes" class="form-control" placeholder="Optional…">
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