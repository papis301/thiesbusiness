<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) header("Location: login.php");

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<title>Catégories</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-4">
    <div class="d-flex justify-content-between mb-3">
        <h3>📂 Catégories</h3>
        <a href="category_add.php" class="btn btn-success">➕ Ajouter</a>
    </div>

    <table class="table table-bordered bg-white shadow-sm">
        <tr>
            <th>#</th>
            <th>Nom</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($categories as $c): ?>
        <tr>
            <td><?= $c['id'] ?></td>
            <td><?= htmlspecialchars($c['name']) ?></td>
            <td>
                <a href="category_edit.php?id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                <a href="category_delete.php?id=<?= $c['id'] ?>"
                   onclick="return confirm('Supprimer cette catégorie ?')"
                   class="btn btn-danger btn-sm">🗑️</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
