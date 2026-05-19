<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', 'staff', 'encoder');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$id             = (int)($_POST['id'] ?? 0);
$household_id   = (int)($_POST['household_id'] ?? 0);
$name           = trim($_POST['name'] ?? '');
$relationship   = trim($_POST['relationship'] ?? '');
$age            = !empty($_POST['age']) ? (int)$_POST['age'] : null;
$occupation     = trim($_POST['occupation'] ?? '');
$contact        = trim($_POST['contact'] ?? '');

if (!$household_id || !$name || !$relationship) {
    setFlash('danger', 'Please fill in all required fields.');
    header('Location: index.php');
    exit;
}

if ($id > 0) {
    $stmt = $db->prepare('UPDATE household_members SET household_id=?, name=?, relationship=?, age=?, occupation=?, contact=? WHERE id=?');
    $stmt->execute([$household_id, $name, $relationship, $age, $occupation, $contact, $id]);
    setFlash('success', 'Member updated successfully.');
} else {
    $stmt = $db->prepare('INSERT INTO household_members (household_id, name, relationship, age, occupation, contact) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$household_id, $name, $relationship, $age, $occupation, $contact]);
    setFlash('success', 'Member added successfully.');
}

header('Location: index.php');
exit;
