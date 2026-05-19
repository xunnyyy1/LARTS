<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $db = getDB();
    $db->prepare('DELETE FROM households WHERE id=?')->execute([$id]);
    setFlash('success', 'Household deleted.');
}
header('Location: index.php');
exit;