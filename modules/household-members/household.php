<?php
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$db = getDB();
$household_id = (int)($_GET['household_id'] ?? 0);

if ($household_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid household ID']);
    exit;
}

$stmt = $db->prepare('SELECT m.name, m.relationship, m.age, m.occupation FROM household_members m WHERE m.household_id = ? ORDER BY CASE WHEN m.relationship = "Head" THEN 0 WHEN m.relationship = "Spouse" THEN 1 WHEN m.relationship = "Father" THEN 2 WHEN m.relationship = "Mother" THEN 3 WHEN m.relationship = "Son" THEN 4 WHEN m.relationship = "Daughter" THEN 5 ELSE 6 END, m.name ASC');
$stmt->execute([$household_id]);
$rows = $stmt->fetchAll();

echo json_encode($rows);
