<?php
$pageTitle = 'Households';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// ── Filters ────────────────────────────────────────────────────
$search  = trim($_GET['search'] ?? '');
$barangay= trim($_GET['barangay'] ?? '');
$status  = trim($_GET['status'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(h.family_name LIKE ? OR h.head_name LIKE ? OR h.contact LIKE ?)';
    $params   = array_merge($params, ["%$search%","%$search%","%$search%"]);
}
if ($barangay) {
    $where[]  = 'h.barangay = ?';
    $params[] = $barangay;
}
if ($status) {
    $where[]  = 'h.economic_status = ?';
    $params[] = $status;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM households h $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $perPage);
$offset= ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("SELECT h.*, u.name as created_by_name
    FROM households h LEFT JOIN users u ON h.created_by = u.id
    $whereSQL ORDER BY h.date_registered DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Barangay list for filter
$brgys = $db->query('SELECT DISTINCT barangay FROM households ORDER BY barangay')->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-people-fill me-2 text-primary"></i>Households</div>
        <div class="page-header-sub">Manage and monitor registered household records</div>
    </div>
    <?php if (isEncoder()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#hhModal">
        <i class="bi bi-plus-lg me-1"></i>Add Household
    </button>
    <?php endif; ?>
</div>

<!-- Toolbar -->
<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2 flex-wrap flex-grow-1">
            <input type="text" name="search" class="form-control" style="max-width:220px;"
                   placeholder="Search name / contact…" value="<?= clean($search) ?>">
            <select name="barangay" class="form-select" style="max-width:180px;">
                <option value="">All Barangays</option>
                <?php foreach ($brgys as $b): ?>
                <option value="<?= clean($b) ?>" <?= $barangay===$b?'selected':'' ?>><?= clean($b) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select" style="max-width:160px;">
                <option value="">All Status</option>
                <option value="stable"     <?= $status==='stable'?'selected':'' ?>>Stable</option>
                <option value="at_risk"    <?= $status==='at_risk'?'selected':'' ?>>At Risk</option>
                <option value="vulnerable" <?= $status==='vulnerable'?'selected':'' ?>>Vulnerable</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($search||$barangay||$status): ?>
            <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i> Clear</a>
            <?php endif; ?>
        </form>
        <span class="text-muted" style="font-size:0.8rem;white-space:nowrap;"><?= $total ?> records</span>
    </div>
</div>

<!-- Table -->
<div class="card" style="border-radius:0 0 var(--radius) var(--radius);">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th><th>Family Name</th><th>Head of Household</th>
                    <th>Barangay</th><th>Members</th>
                    <th>Monthly Income</th><th>Monthly Expenses</th>
                    <th>Balance</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="10" class="empty-state"><i class="bi bi-inbox"></i><p>No records found.</p></td></tr>
            <?php else: ?>
            <?php foreach ($rows as $i => $r):
                $bal = $r['monthly_income'] - $r['monthly_expenses'];
            ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($r['family_name']) ?></strong></td>
                <td><?= clean($r['head_name']) ?></td>
                <td><span class="badge bg-light text-dark border"><?= clean($r['barangay']) ?></span></td>
                <td><?= $r['members_count'] ?></td>
                <td class="text-money"><?= formatMoney($r['monthly_income']) ?></td>
                <td class="text-money"><?= formatMoney($r['monthly_expenses']) ?></td>
                <td class="text-money <?= $bal>=0?'text-success':'text-danger' ?>"><?= formatMoney($bal) ?></td>
                <td><?= statusBadge($r['economic_status']) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                            <i class="bi bi-eye"></i>
                        </a>
                        <?php if (isEncoder()): ?>
                        <button class="btn btn-sm btn-outline-secondary edit-btn"
                            data-id="<?= $r['id'] ?>"
                            data-family="<?= clean($r['family_name']) ?>"
                            data-head="<?= clean($r['head_name']) ?>"
                            data-barangay="<?= clean($r['barangay']) ?>"
                            data-address="<?= clean($r['address']) ?>"
                            data-contact="<?= clean($r['contact']) ?>"
                            data-members="<?= $r['members_count'] ?>"
                            data-income="<?= $r['monthly_income'] ?>"
                            data-expenses="<?= $r['monthly_expenses'] ?>"
                            data-date="<?= $r['date_registered'] ?>"
                            title="Edit"><i class="bi bi-pencil"></i></button>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                        <a href="delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger"
                           data-confirm="Delete this household record?" title="Delete">
                            <i class="bi bi-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="card-footer d-flex justify-content-between align-items-center" style="background:none;padding:12px 20px;">
        <span class="text-muted" style="font-size:0.8rem;">Page <?= $page ?> of <?= $pages ?></span>
        <nav><ul class="pagination mb-0 pagination-sm">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
            <li class="page-item <?= $p===$page?'active':'' ?>">
                <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&barangay=<?= urlencode($barangay) ?>&status=<?= urlencode($status) ?>">
                    <?= $p ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- ── ADD/EDIT MODAL ─────────────────────────────────────────── -->
<?php if (isEncoder()): ?>
<div class="modal fade" id="hhModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hhModalTitle"><i class="bi bi-house me-2"></i>Add Household</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="save.php">
                <input type="hidden" name="id" id="form_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Family Name <span class="text-danger">*</span></label>
                            <input type="text" name="family_name" id="form_family" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Head of Household <span class="text-danger">*</span></label>
                            <input type="text" name="head_name" id="form_head" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barangay <span class="text-danger">*</span></label>
                            <input type="text" name="barangay" id="form_barangay" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact" id="form_contact" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" id="form_address" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Members Count</label>
                            <input type="number" name="members_count" id="form_members" class="form-control" min="1" value="1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monthly Income (₱)</label>
                            <input type="number" name="monthly_income" id="calc_income" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monthly Expenses (₱)</label>
                            <input type="number" name="monthly_expenses" id="calc_expenses" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date Registered</label>
                            <input type="date" name="date_registered" id="form_date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Computed Balance</label>
                            <div class="form-control bg-light d-flex justify-content-between align-items-center">
                                <span id="calc_balance" class="fw-bold">₱ 0.00</span>
                                <span id="calc_status" class="badge bg-secondary">—</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Household</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Populate edit modal
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('hhModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Household';
        document.getElementById('form_id').value       = btn.dataset.id;
        document.getElementById('form_family').value   = btn.dataset.family;
        document.getElementById('form_head').value     = btn.dataset.head;
        document.getElementById('form_barangay').value = btn.dataset.barangay;
        document.getElementById('form_address').value  = btn.dataset.address;
        document.getElementById('form_contact').value  = btn.dataset.contact;
        document.getElementById('form_members').value  = btn.dataset.members;
        document.getElementById('calc_income').value   = btn.dataset.income;
        document.getElementById('calc_expenses').value = btn.dataset.expenses;
        document.getElementById('form_date').value     = btn.dataset.date;
        const modal = new bootstrap.Modal(document.getElementById('hhModal'));
        modal.show();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>