<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin', 'staff', 'encoder');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$db = getDB();
$id         = (int)($_POST['id'] ?? 0);
$family     = trim($_POST['family_name'] ?? '');
$head       = trim($_POST['head_name'] ?? '');
$barangay   = trim($_POST['barangay'] ?? '');
$address    = trim($_POST['address'] ?? '');
$contact    = trim($_POST['contact'] ?? '');
$members    = max(1, (int)($_POST['members_count'] ?? 1));
$income     = (float)($_POST['monthly_income'] ?? 0);
$expenses   = (float)($_POST['monthly_expenses'] ?? 0);
$date       = $_POST['date_registered'] ?? date('Y-m-d');
$status     = computeStatus($income, $expenses);

if (!$family || !$head || !$barangay || !$address) {
    setFlash('danger', 'Please fill in all required fields.');
    header('Location: index.php');
    exit;
}

if ($id > 0) {
    $stmt = $db->prepare('UPDATE households SET family_name=?,head_name=?,barangay=?,address=?,contact=?,
        members_count=?,monthly_income=?,monthly_expenses=?,economic_status=?,date_registered=? WHERE id=?');
    $stmt->execute([$family,$head,$barangay,$address,$contact,$members,$income,$expenses,$status,$date,$id]);
    setFlash('success', 'Household record updated successfully.');
} else {
    $stmt = $db->prepare('INSERT INTO households (family_name,head_name,barangay,address,contact,
        members_count,monthly_income,monthly_expenses,economic_status,date_registered,created_by)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$family,$head,$barangay,$address,$contact,$members,$income,$expenses,$status,$date,$_SESSION['user_id']]);
    setFlash('success', 'Household registered successfully.');
}

header('Location: index.php');
exit;