<?php
// 1. Load core configuration and authentication first
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$db = getDB();

// 2. Process form submissions (Save Assistance)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isStaff()) {
    $hhId   = (int)$_POST['household_id'];
    $type   = trim($_POST['assistance_type'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $date   = $_POST['date_released'] ?? date('Y-m-d');
    $rem    = trim($_POST['remarks'] ?? '');

    if ($hhId && $type && $amount > 0) {
        $db->prepare('INSERT INTO assistance_records (household_id,assistance_type,amount,date_released,remarks,staff_id) VALUES (?,?,?,?,?,?)')
           ->execute([$hhId,$type,$amount,$date,$rem,$_SESSION['user_id']]);
        setFlash('success','Assistance record saved.');
    } else {
        setFlash('danger','Please complete all required fields.');
    }
    header('Location: index.php'); 
    exit;
}

// 3. Process deletions
if (isset($_GET['del']) && isAdmin()) {
    $db->prepare('DELETE FROM assistance_records WHERE id=?')->execute([(int)$_GET['del']]);
    setFlash('success','Deleted.');
    header('Location: index.php'); 
    exit;
}

// 4. NOW include the HTML header
$pageTitle = 'Assistance Distribution';
require_once __DIR__ . '/../../includes/header.php';

// 5. Fetch Data with SEARCH, FILTER, and SORT
$search = trim($_GET['search'] ?? '');
$filterType = trim($_GET['filter_type'] ?? '');
$sort = $_GET['sort'] ?? 'date_released';
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

// Map safe column names to prevent SQL injection in ORDER BY
$allowedSorts = [
    'date_released' => 'a.date_released',
    'amount' => 'a.amount',
    'family_name' => 'h.family_name'
];
$orderBy = $allowedSorts[$sort] ?? 'a.date_released';

$sql = "SELECT a.*, h.family_name, h.barangay, u.name as staff_name
        FROM assistance_records a JOIN households h ON a.household_id=h.id
        LEFT JOIN users u ON a.staff_id=u.id
        WHERE 1=1"; 
$params = [];

if ($search) { 
    $sql .= " AND (h.family_name LIKE ? OR a.assistance_type LIKE ?)"; 
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filterType) {
    $sql .= " AND a.assistance_type = ?";
    $params[] = $filterType;
}

$sql .= " ORDER BY $orderBy $order LIMIT 100";
$stmt = $db->prepare($sql); 
$stmt->execute($params); 
$rows = $stmt->fetchAll();

$households = $db->query('SELECT id,family_name,barangay FROM households ORDER BY family_name')->fetchAll();
$assistTypes = ['4Ps Cash Grant','Food Pack','Educational Assistance','Livelihood Starter Kit','Medical Assistance','Housing Assistance','Other'];

$totalGiven  = array_sum(array_column($rows,'amount'));
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-gift-fill me-2 text-warning"></i>Assistance Distribution</div>
        <div class="page-header-sub">Record and monitor government and barangay assistance releases</div>
    </div>
    <?php if (isStaff()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assModal">
        <i class="bi bi-plus-lg me-1"></i>Record Assistance
    </button>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fef3c7;color:#c27803;"><i class="bi bi-gift-fill"></i></div>
            <div class="stat-value"><?= count($rows) ?></div>
            <div class="stat-label">Total Releases</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#dcfce7;color:#057a55;"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-value" style="font-size:1.3rem;"><?= formatMoney($totalGiven) ?></div>
            <div class="stat-label">Total Assistance Given</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e8f0fe;color:var(--primary);"><i class="bi bi-people-fill"></i></div>
            <div class="stat-value"><?= count(array_unique(array_column($rows,'household_id'))) ?></div>
            <div class="stat-label">Unique Households Served</div>
        </div>
    </div>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center w-100">
            <input type="text" name="search" class="form-control" style="max-width:200px;" placeholder="Search…" value="<?= clean($search) ?>">
            
            <select name="filter_type" class="form-select" style="max-width:200px;">
                <option value="">All Assistance Types</option>
                <?php foreach ($assistTypes as $t): ?>
                    <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= $t ?></option>
                <?php endforeach; ?>
            </select>

            <select name="sort" class="form-select" style="max-width:160px;">
                <option value="date_released" <?= $sort === 'date_released' ? 'selected' : '' ?>>Sort by Date</option>
                <option value="amount" <?= $sort === 'amount' ? 'selected' : '' ?>>Sort by Amount</option>
                <option value="family_name" <?= $sort === 'family_name' ? 'selected' : '' ?>>Sort by Household</option>
            </select>

            <select name="order" class="form-select" style="max-width:120px;">
                <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Desc</option>
                <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Asc</option>
            </select>

            <button type="submit" class="btn btn-primary"><i class="bi bi-filter"></i> Apply</button>
            <?php if($search || $filterType || $sort !== 'date_released' || $order !== 'DESC'): ?>
                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-x"></i> Clear</a>
            <?php endif;?>
        </form>
    </div>
</div>

<div class="card" style="border-radius:0 0 var(--radius) var(--radius);">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Household</th><th>Barangay</th><th>Assistance Type</th><th>Amount</th><th>Date Released</th><th>Remarks</th><th>Staff</th><?php if(isAdmin()):?><th>Del</th><?php endif;?></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="empty-state"><i class="bi bi-inbox"></i><p>No records found.</p></td></tr>
            <?php else: foreach ($rows as $i => $r): ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $i+1 ?></td>
                <td><a href="../households/view.php?id=<?= $r['household_id'] ?>" class="text-decoration-none fw-600"><?= clean($r['family_name']) ?></a></td>
                <td><?= clean($r['barangay']) ?></td>
                <td><span class="badge bg-warning text-dark"><?= clean($r['assistance_type']) ?></span></td>
                <td class="text-money fw-600"><?= formatMoney($r['amount']) ?></td>
                <td><?= $r['date_released'] ?></td>
                <td class="text-muted"><?= clean($r['remarks'] ?? '') ?></td>
                <td><?= clean($r['staff_name'] ?? 'N/A') ?></td>
                <?php if(isAdmin()):?><td><a href="?del=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Delete?"><i class="bi bi-trash"></i></a></td><?php endif;?>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (isStaff()): ?>
<div class="modal fade" id="assModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gift me-2"></i>Record Assistance</h5>
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
                        <label class="form-label">Assistance Type <span class="text-danger">*</span></label>
                        <select name="assistance_type" class="form-select" required>
                            <option value="">— Select —</option>
                            <?php foreach ($assistTypes as $t): ?>
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
                            <label class="form-label">Date Released</label>
                            <input type="date" name="date_released" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes…"></textarea>
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