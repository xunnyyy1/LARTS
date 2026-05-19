<?php
$pageTitle = 'Assistance Types';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// ── Filters ────────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(a.name LIKE ? OR a.description LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%"]);
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM assistance_types a $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $perPage);
$offset= ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("SELECT a.* FROM assistance_types a $whereSQL ORDER BY a.name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-tags-fill me-2 text-primary"></i>Assistance Types</div>
        <div class="page-header-sub">Manage assistance program types and their limits</div>
    </div>
    <?php if (isAdmin() || isStaff()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" onclick="resetForm()">
        <i class="bi bi-plus-lg me-1"></i>Add Type
    </button>
    <?php endif; ?>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" class="form-control" style="max-width:240px;"
                   placeholder="Search type / description…" value="<?= clean($search) ?>">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($search): ?><a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
        </form>
        <span class="text-muted" style="font-size:0.8rem;white-space:nowrap;"><?= $total ?> records</span>
    </div>
</div>

<div class="card" style="border-radius:0 0 var(--radius) var(--radius);">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type Name</th>
                    <th>Description</th>
                    <th>Maximum Amount</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="empty-state"><i class="bi bi-inbox"></i><p>No assistance types found.</p></td></tr>
            <?php else: foreach ($rows as $i => $row): ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($row['name']) ?></strong></td>
                <td><?= clean(substr($row['description'] ?? '', 0, 40)) ?></td>
                <td class="text-money text-success"><?= formatMoney($row['max_amount']) ?></td>
                <td class="text-muted"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" 
                            data-bs-target="#formModal" onclick="editRecord(<?= $row['id'] ?>)" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(<?= $row['id'] ?>, '<?= clean($row['name']) ?>')" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($pages > 1): ?>
    <div style="padding:12px 16px;border-top:1px solid var(--border);">
        <nav>
            <ul class="pagination justify-content-center mb-0">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="index.php?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
</div>

<!-- ── Add/Edit Modal ────────────────────────────────────────– -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="recordForm" method="POST" action="save.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add Assistance Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="recordId" name="id" value="0">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Type Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="max_amount" class="form-label">Maximum Amount (₱)</label>
                        <input type="number" id="max_amount" name="max_amount" class="form-control" 
                            step="0.01" min="0" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function resetForm() {
    document.getElementById('recordForm').reset();
    document.getElementById('recordId').value = '0';
    document.getElementById('max_amount').value = '0';
    document.getElementById('modalTitle').textContent = 'Add Assistance Type';
}

function editRecord(id) {
    fetch(`view.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('recordId').value = data.id;
            document.getElementById('name').value = data.name;
            document.getElementById('description').value = data.description || '';
            document.getElementById('max_amount').value = data.max_amount || '0';
            document.getElementById('modalTitle').textContent = 'Edit Assistance Type';
        });
}

function deleteRecord(id, name) {
    if (confirm(`Delete "${name}"? This action cannot be undone.`)) {
        fetch('delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        }).then(() => window.location.reload());
    }
}
</script>
JS;
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
