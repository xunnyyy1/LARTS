<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $uname= trim($_POST['username'] ?? '');
    $role = $_POST['role'] ?? 'encoder';
    $pass = $_POST['password'] ?? '';

    if (!$name || !$uname) {
        setFlash('danger','Name and username are required.');
        header('Location: index.php'); exit;
    }

    if ($id > 0) {
        if ($pass) {
            $db->prepare('UPDATE users SET name=?,username=?,role=?,password=? WHERE id=?')
               ->execute([$name,$uname,$role,password_hash($pass,PASSWORD_DEFAULT),$id]);
        } else {
            $db->prepare('UPDATE users SET name=?,username=?,role=? WHERE id=?')
               ->execute([$name,$uname,$role,$id]);
        }
        setFlash('success','User updated.');
    } else {
        if (!$pass) { setFlash('danger','Password required for new user.'); header('Location: index.php'); exit; }
        $db->prepare('INSERT INTO users (name,username,password,role) VALUES (?,?,?,?)')
           ->execute([$name,$uname,password_hash($pass,PASSWORD_DEFAULT),$role]);
        setFlash('success','User created.');
    }
    header('Location: index.php'); exit;
}

if (isset($_GET['del'])) {
    $delId = (int)$_GET['del'];
    if ($delId !== $_SESSION['user_id']) {
        $db->prepare('DELETE FROM users WHERE id=?')->execute([$delId]);
        setFlash('success','User deleted.');
    } else {
        setFlash('danger','You cannot delete your own account.');
    }
    header('Location: index.php'); exit;
}

$users = $db->query('SELECT * FROM users ORDER BY role,name')->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <div>
        <div class="page-header-title"><i class="bi bi-person-gear me-2 text-primary"></i>Manage Users</div>
        <div class="page-header-sub">Control system access and user roles</div>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
        <i class="bi bi-plus-lg me-1"></i>Add User
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $i => $u): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?= clean($u['name']) ?></strong></td>
                <td><code><?= clean($u['username']) ?></code></td>
                <td>
                    <span class="badge bg-<?= $u['role']==='admin'?'danger':($u['role']==='staff'?'primary':'success') ?>">
                        <?= ucfirst($u['role']) ?>
                    </span>
                </td>
                <td><?= $u['created_at'] ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <button class="btn btn-sm btn-outline-secondary edit-user-btn"
                            data-id="<?= $u['id'] ?>"
                            data-name="<?= clean($u['name']) ?>"
                            data-username="<?= clean($u['username']) ?>"
                            data-role="<?= $u['role'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <a href="?del=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger"
                           data-confirm="Delete user <?= clean($u['name']) ?>?">
                            <i class="bi bi-trash"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle"><i class="bi bi-person-plus me-2"></i>Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="user_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="user_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="user_username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="user_role" class="form-select">
                            <option value="encoder">Encoder</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password <span id="pass_note" class="text-muted">(required)</span></label>
                        <input type="password" name="password" id="user_pass" class="form-control">
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

<script>
document.querySelectorAll('.edit-user-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit User';
        document.getElementById('user_id').value       = btn.dataset.id;
        document.getElementById('user_name').value     = btn.dataset.name;
        document.getElementById('user_username').value = btn.dataset.username;
        document.getElementById('user_role').value     = btn.dataset.role;
        document.getElementById('user_pass').value     = '';
        document.getElementById('pass_note').textContent = '(leave blank to keep current)';
        new bootstrap.Modal(document.getElementById('userModal')).show();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>