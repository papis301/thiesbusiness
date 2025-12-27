<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) header("Location: login.php");

$id = $_GET['id'];

$category = $pdo->prepare("SELECT * FROM categories WHERE id=?");
$category->execute([$id]);
$category = $category->fetch();

if (!$category) die("Catégorie introuvable");

if ($_POST) {
    $stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=?");
    $stmt->execute([$_POST['name'], $id]);
    header("Location: categories.php");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Modifier catégorie</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-4">
    <h3>✏️ Modifier catégorie</h3>

    <form method="post" class="card p-4 shadow-sm">
        <input class="form-control mb-3" name="name"
               value="<?= htmlspecialchars($category['name']) ?>" required>
        <button class="btn btn-warning">Modifier</button>
    </form>
</div>
</body>
</html>
