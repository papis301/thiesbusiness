<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin'])) header("Location: login.php");

$id = $_GET['id'] ?? null;
if (!$id) die("Business introuvable");

// Récupérer le business
$stmt = $pdo->prepare("SELECT * FROM businesses WHERE id=?");
$stmt->execute([$id]);
$business = $stmt->fetch();
if (!$business) die("Business introuvable");

// Récupérer les catégories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $image = $business['image']; // garder l'ancienne image
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], "uploads/".$image);
    }

    $stmt = $pdo->prepare("
        UPDATE businesses SET
            name=?, description=?, category_id=?, phone=?, quartier=?, latitude=?, longitude=?, image=?
        WHERE id=?
    ");
    $stmt->execute([
        $_POST['name'],
        $_POST['description'],
        $_POST['category'],
        $_POST['phone'],
        $_POST['quartier'],
        $_POST['lat'],
        $_POST['lng'],
        $image,
        $id
    ]);

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier Business</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    #map { height: 300px; width: 100%; border-radius:10px; margin-bottom:15px; }
</style>
</head>
<body class="bg-light">
<div class="container my-4">

<h3>✏️ Modifier un business</h3>

<div id="map"></div>
<p class="text-muted">Cliquez sur la carte pour définir l’emplacement du business</p>

<form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">

    <div class="row g-3">

        <div class="col-md-6">
            <label class="form-label">Nom du business</label>
            <input class="form-control" name="name" value="<?= htmlspecialchars($business['name']) ?>" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Catégorie</label>
            <select class="form-select" name="category">
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id']==$business['category_id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description"><?= htmlspecialchars($business['description']) ?></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input class="form-control" name="phone" value="<?= htmlspecialchars($business['phone']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Quartier</label>
            <input class="form-control" name="quartier" value="<?= htmlspecialchars($business['quartier']) ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">Latitude</label>
            <input class="form-control" name="lat" id="lat" value="<?= $business['latitude'] ?>" required readonly>
        </div>

        <div class="col-md-6">
            <label class="form-label">Longitude</label>
            <input class="form-control" name="lng" id="lng" value="<?= $business['longitude'] ?>" required readonly>
        </div>

        <div class="col-12">
            <label class="form-label">Image actuelle</label><br>
            <img src="uploads/<?= $business['image'] ?>" width="150" class="mb-2"><br>
            <label class="form-label">Changer l'image</label>
            <input type="file" class="form-control" name="image">
        </div>

        <div class="col-12 text-end">
            <button class="btn btn-warning">Modifier</button>
        </div>

    </div>
</form>

</div>

<script>
let map, marker;
function initMap() {
    const pos = { lat: parseFloat("<?= $business['latitude'] ?>"), lng: parseFloat("<?= $business['longitude'] ?>") };
    map = new google.maps.Map(document.getElementById("map"), { zoom: 13, center: pos });
    marker = new google.maps.Marker({ position: pos, map: map });

    map.addListener("click", function(e) {
        marker.setPosition(e.latLng);
        document.getElementById("lat").value = e.latLng.lat().toFixed(6);
        document.getElementById("lng").value = e.latLng.lng().toFixed(6);
    });
}
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBAmGv7nVuVuYx8Zmph6DmBH1SxIIa9UAM&callback=initMap" async defer></script>

</body>
</html>
