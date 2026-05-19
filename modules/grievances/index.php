<?php
$pageTitle = 'Grievances';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// ── Filters ────────────────────────────────────────────────────
$search     = trim($_GET['search'] ?? '');
$status     = trim($_GET['status'] ?? '');
$priority   = trim($_GET['priority'] ?? '');
$household  = trim($_GET['household'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 10;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(g.subject LIKE ? OR g.description LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%"]);
}
if ($status) {
    $where[]  = 'g.status = ?';
    $params[] = $status;
}
if ($priority) {
    $where[]  = 'g.priority = ?';
    $params[] = $priority;
}
if ($household) {
    $where[]  = 'g.household_id = ?';
    $params[] = $household;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM grievances g $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $perPage);
$offset= ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("SELECT g.*, h.family_name FROM grievances g
    LEFT JOIN households h ON g.household_id = h.id
    $whereSQL ORDER BY g.date_filed DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Lists for filters
$households = $db->query('SELECT id, family_name FROM households ORDER BY family_name')->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-exclamation-circle-fill me-2 text-danger"></i>Grievances</div>
        <div class="page-header-sub">File and manage complaints from households</div>
    </div>
    <?php if (isStaff() || isEncoder()): ?>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" onclick="resetForm()">
        <i class="bi bi-plus-lg me-1"></i>File Grievance
    </button>
    <?php endif; ?>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2 flex-grow-1 flex-wrap">
            <input type="text" name="search" class="form-control" style="max-width:180px;"
                   placeholder="Search subject / description…" value="<?= clean($search) ?>">
            <select name="status" class="form-select" style="max-width:140px;">
                <option value="">All statuses</option>
                <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Open</option>
                <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
            </select>
            <select name="priority" class="form-select" style="max-width:120px;">
                <option value="">All priorities</option>
                <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Low</option>
                <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>High</option>
                <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Urgent</option>
            </select>
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($search || $status || $priority): ?><a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
        </form>
        <span class="text-muted" style="font-size:0.8rem;white-space:nowrap;"><?= $total ?> records</span>
    </div>
</div>

<!-- ── Grievances Table ──────────────────────────────────────– -->
<div class="card" style="border-radius:0 0 var(--radius) var(--radius);">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject</th>
                    <th>Household</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Filed</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="empty-state"><i class="bi bi-inbox"></i><p>No grievances found.</p></td></tr>
            <?php else: foreach ($rows as $i => $row): ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
                <td><strong><?= clean($row['subject']) ?></strong></td>
                <td><?= clean($row['family_name']) ?></td>
                <td>
                    <span class="badge bg-<?= $row['status']==='open'?'danger':($row['status']==='in_progress'?'warning':($row['status']==='resolved'?'success':'secondary')) ?>">
                        <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                    </span>
                </td>
                <td>
                    <span class="badge bg-<?= $row['priority']==='urgent'?'danger':($row['priority']==='high'?'warning':($row['priority']==='medium'?'info':'secondary')) ?>">
                        <?= ucfirst($row['priority']) ?>
                    </span>
                </td>
                <td class="text-muted"><?= date('M d, Y', strtotime($row['date_filed'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" 
                            data-bs-target="#viewModal" onclick="viewRecord(<?= $row['id'] ?>)" title="View">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" 
                            data-bs-target="#formModal" onclick="editRecord(<?= $row['id'] ?>)" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteRecord(<?= $row['id'] ?>, '<?= clean($row['subject']) ?>')" title="Delete">
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
                            <a class="page-link" href="index.php?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $status ? '&status='.$status : '' ?><?= $priority ? '&priority='.$priority : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
</div>

<!-- ── File/Edit Modal ───────────────────────────────────────── -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="recordForm" method="POST" action="save.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">File Grievance</h5>
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
                        <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" id="subject" name="subject" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select id="priority" name="priority" class="form-select">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="resolution_notes" class="form-label">Resolution Notes</label>
                        <textarea id="resolution_notes" name="resolution_notes" class="form-control" rows="3"></textarea>
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

<!-- ── View Modal ────────────────────────────────────────────── -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Grievance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewContent">Loading...</div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function resetForm() {
    document.getElementById('recordForm').reset();
    document.getElementById('recordId').value = '0';
    document.getElementById('status').value = 'open';
    document.getElementById('priority').value = 'medium';
    document.getElementById('modalTitle').textContent = 'File Grievance';
}

function editRecord(id) {
    fetch(`view.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('recordId').value = data.id;
            document.getElementById('household_id').value = data.household_id;
            document.getElementById('subject').value = data.subject;
            document.getElementById('description').value = data.description;
            document.getElementById('status').value = data.status;
            document.getElementById('priority').value = data.priority;
            document.getElementById('resolution_notes').value = data.resolution_notes || '';
            document.getElementById('modalTitle').textContent = 'Edit Grievance';
        });
}

function viewRecord(id) {
    fetch(`view.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            const html = `
                <dl class="row">
                    <dt class="col-sm-3">Subject:</dt>
                    <dd class="col-sm-9"><strong>${data.subject}</strong></dd>
                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9"><span class="badge bg-${data.status==='open'?'danger':data.status==='in_progress'?'warning':'success'}">${data.status.replace('_', ' ').toUpperCase()}</span></dd>
                    <dt class="col-sm-3">Priority:</dt>
                    <dd class="col-sm-9"><span class="badge bg-${data.priority==='urgent'?'danger':'secondary'}">${data.priority.toUpperCase()}</span></dd>
                    <dt class="col-sm-3">Filed:</dt>
                    <dd class="col-sm-9">${new Date(data.date_filed).toLocaleDateString()}</dd>
                </dl>
                <h6 class="mt-3">Description:</h6>
                <p>${data.description}</p>
                ${data.resolution_notes ? `<h6 class="mt-3">Resolution Notes:</h6><p>${data.resolution_notes}</p>` : ''}
            `;
            document.getElementById('viewContent').innerHTML = html;
        });
}

function deleteRecord(id, subject) {
    if (confirm(`Delete "${subject}"? This action cannot be undone.`)) {
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
