<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', 'staff', 'encoder');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$id                 = (int)($_POST['id'] ?? 0);
$household_id       = (int)($_POST['household_id'] ?? 0);
$subject            = trim($_POST['subject'] ?? '');
$description        = trim($_POST['description'] ?? '');
$status             = trim($_POST['status'] ?? 'open');
$priority           = trim($_POST['priority'] ?? 'medium');
$resolution_notes   = trim($_POST['resolution_notes'] ?? '');

if (!$household_id || !$subject || !$description) {
    setFlash('danger', 'Please fill in all required fields.');
    header('Location: index.php');
    exit;
}

if ($id > 0) {
    $stmt = $db->prepare('UPDATE grievances SET household_id=?, subject=?, description=?, status=?, priority=?, resolution_notes=? WHERE id=?');
    $stmt->execute([$household_id, $subject, $description, $status, $priority, $resolution_notes, $id]);
    setFlash('success', 'Grievance updated successfully.');
} else {
    $stmt = $db->prepare('INSERT INTO grievances (household_id, subject, description, status, priority, resolution_notes, filed_by, date_filed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$household_id, $subject, $description, $status, $priority, $resolution_notes, $_SESSION['user_id'], date('Y-m-d')]);
    setFlash('success', 'Grievance filed successfully.');
}

header('Location: index.php');
exit;
