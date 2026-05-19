<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'Invalid barangay ID.');
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare('DELETE FROM barangays WHERE id=?');
$stmt->execute([$id]);

setFlash('success', 'Barangay deleted successfully.');
header('Location: index.php');
exit;
