<?php
$pageTitle = 'Audit Logs';
require_once __DIR__ . '/../../includes/header.php';

// Admin only check
if (!isAdmin()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php?err=unauthorized');
    exit;
}

$db = getDB();

// ── Filters ────────────────────────────────────────────────────
$search     = trim($_GET['search'] ?? '');
$action     = trim($_GET['action'] ?? '');
$user_id    = trim($_GET['user'] ?? '');
$date_from  = trim($_GET['date_from'] ?? '');
$date_to    = trim($_GET['date_to'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 20;

$where  = [];
$params = [];

if ($search) {
    $where[]  = '(a.description LIKE ? OR a.table_name LIKE ?)';
    $params   = array_merge($params, ["%$search%", "%$search%"]);
}
if ($action) {
    $where[]  = 'a.action = ?';
    $params[] = $action;
}
if ($user_id) {
    $where[]  = 'a.user_id = ?';
    $params[] = $user_id;
}
if ($date_from) {
    $where[]  = 'DATE(a.created_at) >= ?';
    $params[] = $date_from;
}
if ($date_to) {
    $where[]  = 'DATE(a.created_at) <= ?';
    $params[] = $date_to;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs a $whereSQL");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = ceil($total / $perPage);
$offset= ($page - 1) * $perPage;

// Fetch
$stmt = $db->prepare("SELECT a.*, u.name as user_name FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    $whereSQL ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Lists for filters
$users = $db->query('SELECT id, name FROM users ORDER BY name')->fetchAll(PDO::FETCH_KEY_PAIR);
$actions = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT'];
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>Audit Logs</div>
        <div class="page-header-sub">View system activity and changes (Admin Only)</div>
    </div>
</div>

<div class="card mb-0" style="border-bottom:0;border-radius:var(--radius) var(--radius) 0 0;">
    <div class="toolbar">
        <form method="GET" class="d-flex gap-2 flex-grow-1 flex-wrap">
            <input type="text" name="search" class="form-control" style="max-width:200px;"
                   placeholder="Search description / table…" value="<?= clean($search) ?>">
            <select name="action" class="form-select" style="max-width:140px;">
                <option value="">All actions</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?= $act ?>" <?= $action === $act ? 'selected' : '' ?>><?= $act ?></option>
                <?php endforeach; ?>
            </select>
            <select name="user" class="form-select" style="max-width:160px;">
                <option value="">All users</option>
                <?php foreach ($users as $id => $name): ?>
                    <option value="<?= $id ?>" <?= $user_id == $id ? 'selected' : '' ?>><?= clean($name) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date_from" class="form-control" style="max-width:140px;" value="<?= clean($date_from) ?>">
            <input type="date" name="date_to" class="form-control" style="max-width:140px;" value="<?= clean($date_to) ?>">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($search || $action || $user_id || $date_from || $date_to): ?><a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a><?php endif; ?>
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
                    <th>Date & Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="9" class="empty-state"><i class="bi bi-inbox"></i><p>No audit logs found.</p></td></tr>
            <?php else: foreach ($rows as $i => $row): ?>
            <tr>
                <td class="text-muted" style="font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
                <td><?= date('M d, Y H:i', strtotime($row['created_at'])) ?></td>
                <td><?= clean($row['user_name'] ?? 'System') ?></td>
                <td>
                    <span class="badge bg-<?= $row['action']==='DELETE'?'danger':($row['action']==='CREATE'?'success':($row['action']==='UPDATE'?'warning':'info')) ?>">
                        <?= $row['action'] ?>
                    </span>
                </td>
                <td><?= clean($row['table_name'] ?? '—') ?></td>
                <td><?= $row['record_id'] ?? '—' ?></td>
                <td><?= clean(substr($row['description'] ?? '', 0, 35)) ?></td>
                <td><code style="font-size:0.75rem;"><?= $row['ip_address'] ?? '—' ?></code></td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" 
                            data-bs-target="#detailsModal" onclick="viewDetails(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" title="View Details">
                            <i class="bi bi-eye"></i>
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
                        <a class="page-link" href="index.php?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?><?= $action ? '&action='.$action : '' ?><?= $user_id ? '&user='.$user_id : '' ?><?= $date_from ? '&date_from='.$date_from : '' ?><?= $date_to ? '&date_to='.$date_to : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- ── Details Modal ─────────────────────────────────────────── -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Audit Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailsContent">Loading...</div>
            </div>
        </div>
    </div>
</div>

<?php
$extraScript = <<<'JS'
<script>
function viewDetails(data) {
    let html = `
        <dl class="row mb-3">
            <dt class="col-sm-3">User:</dt>
            <dd class="col-sm-9"><strong>${data.user_name || 'System'}</strong></dd>
            <dt class="col-sm-3">Action:</dt>
            <dd class="col-sm-9"><span class="badge bg-${data.action==='DELETE'?'danger':data.action==='CREATE'?'success':'warning'}">${data.action}</span></dd>
            <dt class="col-sm-3">Table:</dt>
            <dd class="col-sm-9"><code>${data.table_name || '—'}</code></dd>
            <dt class="col-sm-3">Record ID:</dt>
            <dd class="col-sm-9">${data.record_id || '—'}</dd>
            <dt class="col-sm-3">Date & Time:</dt>
            <dd class="col-sm-9">${new Date(data.created_at).toLocaleString()}</dd>
            <dt class="col-sm-3">IP Address:</dt>
            <dd class="col-sm-9"><code>${data.ip_address || '—'}</code></dd>
        </dl>
    `;
    
    if (data.description) {
        html += `<h6>Description:</h6><p>${data.description}</p>`;
    }
    
    if (data.old_values) {
        const oldValues = JSON.parse(data.old_values);
        html += `<h6 class="mt-3">Old Values:</h6><pre class="bg-light p-3">${JSON.stringify(oldValues, null, 2)}</pre>`;
    }
    
    if (data.new_values) {
        const newValues = JSON.parse(data.new_values);
        html += `<h6 class="mt-3">New Values:</h6><pre class="bg-light p-3">${JSON.stringify(newValues, null, 2)}</pre>`;
    }
    
    document.getElementById('detailsContent').innerHTML = html;
}
</script>
JS;
?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
