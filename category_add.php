<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) header("Location: login.php");

if ($_POST) {
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$_POST['name']]);
    header("Location: categories.php");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Ajouter catégorie</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-4">
    <h3>➕ Ajouter une catégorie</h3>

    <form method="post" class="card p-4 shadow-sm">
        <input class="form-control mb-3" name="name" placeholder="Nom catégorie" required>
        <button class="btn btn-success">Enregistrer</button>
    </form>
</div>
</body>
</html>
