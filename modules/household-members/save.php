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
$names          = $_POST['names'] ?? [];
$relationships  = $_POST['relationships'] ?? [];
$ages           = $_POST['ages'] ?? [];
$occupations    = $_POST['occupations'] ?? [];
$contacts       = $_POST['contacts'] ?? [];

if (!$household_id) {
    setFlash('danger', 'Please select a household.');
    header('Location: index.php');
    exit;
}

if ($id > 0) {
    if (!$name || !$relationship) {
        setFlash('danger', 'Please fill in all required fields.');
        header('Location: index.php');
        exit;
    }
    $stmt = $db->prepare('UPDATE household_members SET household_id=?, name=?, relationship=?, age=?, occupation=?, contact=? WHERE id=?');
    $stmt->execute([$household_id, $name, $relationship, $age, $occupation, $contact, $id]);
    setFlash('success', 'Member updated successfully.');
} elseif (is_array($names) && count($names) > 0) {
    $stmt = $db->prepare('INSERT INTO household_members (household_id, name, relationship, age, occupation, contact) VALUES (?, ?, ?, ?, ?, ?)');
    $inserted = 0;
    foreach ($names as $index => $memberName) {
        $memberName = trim($memberName);
        $memberRelationship = trim($relationships[$index] ?? '');
        if (!$memberName || !$memberRelationship) {
            continue;
        }
        $memberAge = !empty($ages[$index]) ? (int)$ages[$index] : null;
        $memberOccupation = trim($occupations[$index] ?? '');
        $memberContact = trim($contacts[$index] ?? '');
        $stmt->execute([$household_id, $memberName, $memberRelationship, $memberAge, $memberOccupation, $memberContact]);
        $inserted++;
    }
    if ($inserted === 0) {
        setFlash('danger', 'Please fill in at least one valid member record.');
    } else {
        setFlash('success', "{$inserted} member" . ($inserted > 1 ? 's' : '') . ' added successfully.');
    }
} else {
    if (!$name || !$relationship) {
        setFlash('danger', 'Please fill in all required fields.');
        header('Location: index.php');
        exit;
    }
    $stmt = $db->prepare('INSERT INTO household_members (household_id, name, relationship, age, occupation, contact) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$household_id, $name, $relationship, $age, $occupation, $contact]);
    setFlash('success', 'Member added successfully.');
}

header('Location: index.php');
exit;
