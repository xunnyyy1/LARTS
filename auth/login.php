<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/dashboard/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['password'])) {
            $_SESSION['user_id']   = $u['id'];
            $_SESSION['user_name'] = $u['name'];
            $_SESSION['user_role'] = $u['role'];
            session_regenerate_id(true);
            header('Location: ' . BASE_URL . '/dashboard/index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – LARTS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<style>
    html, body {
        margin: 0; padding: 0;
        height: 100%;
        background: linear-gradient(135deg, #0f1e3d 0%, #1a3a6b 60%, #1a56db 100%);
    }
</style>
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-house-heart-fill"></i>
        </div>
        <h1 class="text-center fw-800 mb-1" style="font-size:1.4rem;font-weight:800;">LARTS</h1>
        <p class="text-center text-muted mb-4" style="font-size:0.8rem;">Livelihood Assistance &amp; Resource Tracking System</p>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 py-2" style="font-size:0.85rem;">
            <i class="bi bi-exclamation-triangle-fill"></i><?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control"
                           placeholder="Enter username"
                           value="<?= clean($_POST['username'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control"
                           placeholder="Enter password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="divider mt-4"></div>
        <div class="text-center text-muted" style="font-size:0.75rem;">
            <strong>Demo Accounts</strong><br>
            admin / password &nbsp;|&nbsp; mstaff / password &nbsp;|&nbsp; jencoder / password
        </div>

        <div class="text-center mt-3" style="font-size:0.7rem;color:#aaa;">
            Davao Del Norte State College &bull; IT223 Project
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>