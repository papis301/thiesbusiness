<?php
session_start();
require 'db.php';
//if (!isset($_SESSION['admin'])) header("Location: login.php");

// Récupérer catégories, régions
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$regions = $pdo->query("SELECT * FROM regions ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $image = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "uploads/".$image);

    $stmt = $pdo->prepare("
        INSERT INTO businesses
        (name, description, category_id, phone, quartier, latitude, longitude, image, region_id, ville_id)
        VALUES (?,?,?,?,?,?,?,?,?,?)
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
        $_POST['region'] ?: null,
        $_POST['ville'] ?: null
    ]);

    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ajouter un business</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    #map { height: 350px; width: 100%; border-radius:10px; margin-bottom:15px; }
</style>
</head>

<body class="bg-light">
<div class="container my-4">

<h3 class="mb-3">📍 Ajouter un business</h3>

<div id="map"></div>
<p class="text-muted">Cliquez sur la carte pour définir l’emplacement du business</p>

<form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
    <div class="row g-3">

        <div class="col-md-6">
            <label class="form-label">Nom du business</label>
            <input class="form-control" name="name" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Catégorie</label>
            <select class="form-select" name="category">
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description"></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input class="form-control" name="phone">
        </div>

        <div class="col-md-6">
            <label class="form-label">Quartier</label>
            <input class="form-control" name="quartier">
        </div>

        <!-- Région -->
        <div class="col-md-6">
            <label class="form-label">Région</label>
            <select class="form-select" name="region" id="region">
                <option value="">-- Sélectionner une région --</option>
                <?php foreach($regions as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Ville -->
        <div class="col-md-6">
            <label class="form-label">Ville</label>
            <select class="form-select" name="ville" id="ville">
                <option value="">-- Sélectionner une ville --</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Latitude</label>
            <input class="form-control" name="lat" id="lat" readonly required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Longitude</label>
            <input class="form-control" name="lng" id="lng" readonly required>
        </div>

        <div class="col-12">
            <label class="form-label">Image</label>
            <input type="file" class="form-control" name="image" required>
        </div>

        <div class="col-12 text-end">
            <button class="btn btn-success">💾 Enregistrer</button>
        </div>

    </div>
</form>

</div>

<script>
let map, marker;

function initMap() {
    // CENTRAGE SUR LE SÉNÉGAL
    const senegal = { lat: 14.4974, lng: -14.4524 };

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 6,
        center: senegal
    });

    map.addListener("click", function(e) {
        placeMarker(e.latLng);
    });
}

function placeMarker(location) {
    if(marker) marker.setPosition(location);
    else marker = new google.maps.Marker({ position: location, map: map });
    document.getElementById("lat").value = location.lat().toFixed(6);
    document.getElementById("lng").value = location.lng().toFixed(6);
}

// Chargement des villes selon la région
document.getElementById('region').addEventListener('change', function(){
    const regionId = this.value;
    const villeSelect = document.getElementById('ville');
    villeSelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';
    if(regionId){
        fetch('get_villes.php?region_id=' + regionId)
            .then(res => res.json())
            .then(data => {
                data.forEach(v => {
                    villeSelect.innerHTML += `<option value="${v.id}">${v.name}</option>`;
                });
            });
    }
});
</script>

<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBAmGv7nVuVuYx8Zmph6DmBH1SxIIa9UAM&callback=initMap">
</script>

</body>
</html>
