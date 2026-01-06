<?php
session_start();
require 'db.php';

$businesses = $pdo->query("
    SELECT b.*, c.name AS category 
    FROM businesses b
    JOIN categories c ON c.id = b.category_id
")->fetchAll();
?>

<h2>📍 ThiesBusiness – Admin</h2>
<a href="add_business.php">➕ Ajouter un business</a>
<table border="1">
<tr>
    <th>Nom</th><th>Catégorie</th><th>Quartier</th><th>Actions</th>
</tr>
<?php foreach ($businesses as $b): ?>
<tr>
    <td><?= $b['name'] ?></td>
    <td><?= $b['category'] ?></td>
    <td><?= $b['quartier'] ?></td>
    <td>
        <a href="edit_business.php?id=<?= $b['id'] ?>">✏️</a>
        <a href="delete_business.php?id=<?= $b['id'] ?>" onclick="return confirm('Supprimer ?')">🗑️</a>
    </td>
</tr>
<?php endforeach; ?>
</table>
