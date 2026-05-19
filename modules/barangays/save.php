<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', 'staff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$id           = (int)($_POST['id'] ?? 0);
$name         = trim($_POST['name'] ?? '');
$description  = trim($_POST['description'] ?? '');

if (!$name) {
    setFlash('danger', 'Barangay name is required.');
    header('Location: index.php');
    exit;
}

if ($id > 0) {
    $stmt = $db->prepare('UPDATE barangays SET name=?, description=? WHERE id=?');
    $stmt->execute([$name, $description, $id]);
    setFlash('success', 'Barangay updated successfully.');
} else {
    $stmt = $db->prepare('INSERT INTO barangays (name, description) VALUES (?, ?)');
    $stmt->execute([$name, $description]);
    setFlash('success', 'Barangay added successfully.');
}

header('Location: index.php');
exit;
