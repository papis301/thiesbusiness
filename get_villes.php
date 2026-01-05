<?php
require 'db.php';

$region_id = $_GET['region_id'] ?? null;

header('Content-Type: application/json');

if (!$region_id) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM villes WHERE region_id = ? ORDER BY name");
$stmt->execute([$region_id]);
$villes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($villes);
