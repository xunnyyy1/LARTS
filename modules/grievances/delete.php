<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', 'staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'Invalid grievance ID.');
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare('DELETE FROM grievances WHERE id=?');
$stmt->execute([$id]);

setFlash('success', 'Grievance deleted successfully.');
header('Location: index.php');
exit;
