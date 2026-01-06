<?php
require 'db.php';

if (!isset($_GET['region_id']) || empty($_GET['region_id'])) {
    echo json_encode([]);
    exit;
}

$regionId = (int) $_GET['region_id'];

$stmt = $pdo->prepare("
    SELECT id, name 
    FROM villes 
    WHERE region_id = ?
    ORDER BY name
");
$stmt->execute([$regionId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
