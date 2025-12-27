<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) header("Location: login.php");

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
$stmt->execute([$id]);

header("Location: categories.php");
