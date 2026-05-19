<?php
$pageTitle = 'Household Members';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// ── Filters ────────────────────────────────────────────────────
$search     = trim($_GET['search'] ?? '');
$household  = trim($_GET['household'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 10;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(m.name LIKE ? OR m.occupation LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%"]);
}
if ($household) {
    $where[]  = 'm.household_id = ?';
    $params[] = $household;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM household_members m $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $perPage);
$offset= ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("SELECT m.*, h.family_name FROM household_members m
    LEFT JOIN households h ON m.household_id = h.id
    $whereSQL ORDER BY h.family_name, m.name ASC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Household list for filter
$households = $db->query('SELECT id, family_name FROM households ORDER BY family_name')->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-person-badge-fill me-2 text-primary"></i>Household Members</div>
        <div class="page-header-sub">Manage members within households</div>
    </div>
    <?php if (isStaff() || isEncoder()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" onclick="resetForm()">
        <i class="bi bi-plus-lg me-1"></i>Add Member
    </button>
    <?php endif; ?>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2 flex-grow-1">
            <input type="text" name="search" class="form-control" style="max-width:200px;"
                   placeholder="Search member / occupation…" value="<?= clean($search) ?>">
            <select name="household" class="form-select" style="max-width:200px;">
                <option value="">All households</option>
                <?php foreach ($households as $id => $name): ?>
                    <option value="<?= $id ?>" <?= $household == $id ? 'selected' : '' ?>>
                        <?= clean($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($search || $household): ?><a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
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
                    <th>Name</th>
                    <th>Household</th>
                    <th>Relationship</th>
                    <th>Age</th>
                    <th>Occupation</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="empty-state"><i class="bi bi-inbox"></i><p>No household members found.</p></td></tr>
            <?php else: foreach ($rows as $i => $row): ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($row['name']) ?></strong></td>
                <td><?= clean($row['family_name']) ?></td>
                <td><?= clean($row['relationship']) ?></td>
                <td><?= $row['age'] ?? '—' ?></td>
                <td><?= clean($row['occupation'] ?? '—') ?></td>
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
                            <a class="page-link" href="index.php?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $household ? '&household='.$household : '' ?>">
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
                    <h5 class="modal-title" id="modalTitle">Add Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="recordId" name="id" value="0">
                    
                    <div class="mb-3">
                        <label for="household_id" class="form-label">Household <span class="text-danger">*</span></label>
                        <select id="household_id" name="household_id" class="form-select" required>
                            <option value="">Select a household...</option>
                            <?php foreach ($households as $id => $name): ?>
                                <option value="<?= $id ?>"><?= clean($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Member Name <span class="text-danger">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="relationship" class="form-label">Relationship <span class="text-danger">*</span></label>
                        <select id="relationship" name="relationship" class="form-select" required>
                            <option value="">Select...</option>
                            <option value="Head">Head</option>
                            <option value="Spouse">Spouse</option>
                            <option value="Son">Son</option>
                            <option value="Daughter">Daughter</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Brother">Brother</option>
                            <option value="Sister">Sister</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" id="age" name="age" class="form-control" min="0" max="120">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact" class="form-label">Contact</label>
                            <input type="tel" id="contact" name="contact" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="occupation" class="form-label">Occupation</label>
                        <input type="text" id="occupation" name="occupation" class="form-control">
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
    document.getElementById('modalTitle').textContent = 'Add Member';
}

function editRecord(id) {
    fetch(`view.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('recordId').value = data.id;
            document.getElementById('household_id').value = data.household_id;
            document.getElementById('name').value = data.name;
            document.getElementById('relationship').value = data.relationship;
            document.getElementById('age').value = data.age || '';
            document.getElementById('contact').value = data.contact || '';
            document.getElementById('occupation').value = data.occupation || '';
            document.getElementById('modalTitle').textContent = 'Edit Member';
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
