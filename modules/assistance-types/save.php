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
$max_amount   = (float)($_POST['max_amount'] ?? 0);

if (!$name) {
    setFlash('danger', 'Type name is required.');
    header('Location: index.php');
    exit;
}

if ($id > 0) {
    $stmt = $db->prepare('UPDATE assistance_types SET name=?, description=?, max_amount=? WHERE id=?');
    $stmt->execute([$name, $description, $max_amount, $id]);
    setFlash('success', 'Assistance type updated successfully.');
} else {
    $stmt = $db->prepare('INSERT INTO assistance_types (name, description, max_amount) VALUES (?, ?, ?)');
    $stmt->execute([$name, $description, $max_amount]);
    setFlash('success', 'Assistance type added successfully.');
}

header('Location: index.php');
exit;
