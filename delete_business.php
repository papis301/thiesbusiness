<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) header("Location: login.php");

$id = $_GET['id'] ?? null;
if (!$id) die("Business introuvable");

// Supprimer l'image si existe
$stmt = $pdo->prepare("SELECT image FROM businesses WHERE id=?");
$stmt->execute([$id]);
$img = $stmt->fetchColumn();
if ($img && file_exists("uploads/".$img)) unlink("uploads/".$img);

// Supprimer le business
$stmt = $pdo->prepare("DELETE FROM businesses WHERE id=?");
$stmt->execute([$id]);

header("Location: dashboard.php");
exit;
?>