<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'db.php';

// Charger les catégories
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Charger les régions
$regions = $pdo->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* =======================
       1. RÉCUPÉRATION DES DONNÉES
       ======================= */
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId  = $_POST['category'] ?? null;
    $phone       = trim($_POST['phone'] ?? '');
    $quartier    = trim($_POST['quartier'] ?? '');
    $lat         = $_POST['lat'] ?? null;
    $lng         = $_POST['lng'] ?? null;

    /* =======================
       2. UPLOAD IMAGE
       ======================= */
    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $image = time() . '_' . basename($_FILES['image']['name']);
        move_uploaded_file(
            $_FILES['image']['tmp_name'],
            $uploadDir . $image
        );
    }

    /* =======================
       3. REGION & VILLE (NULL SAFE)
       ======================= */
    // REGION
    $regionId = null;
    if (!empty($_POST['region']) && is_numeric($_POST['region'])) {
        $check = $pdo->prepare("SELECT id FROM regions WHERE id = ?");
        $check->execute([(int)$_POST['region']]);
        if ($check->fetchColumn()) {
            $regionId = (int)$_POST['region'];
        }
    }

    // VILLE - CORRECTION ICI
    $villeId = null;
    if (!empty($_POST['ville']) && is_numeric($_POST['ville'])) {
        // Vérifier d'abord si la ville existe
        $checkVille = $pdo->prepare("SELECT id FROM villes WHERE id = ?");
        $checkVille->execute([(int)$_POST['ville']]);
        
        if ($checkVille->fetchColumn()) {
            // Vérifier la cohérence région-ville si les deux sont fournis
            if ($regionId !== null) {
                $checkConsistency = $pdo->prepare("SELECT id FROM villes WHERE id = ? AND region_id = ?");
                $checkConsistency->execute([(int)$_POST['ville'], $regionId]);
                if (!$checkConsistency->fetchColumn()) {
                    // Incohérence détectée : réinitialiser villeId
                    $villeId = null;
                    // Optionnel : ajouter un message d'erreur
                    $error = "La ville sélectionnée n'appartient pas à la région choisie.";
                } else {
                    $villeId = (int)$_POST['ville'];
                }
            } else {
                $villeId = (int)$_POST['ville'];
            }
        }
    }

    /* =======================
       4. INSERTION SQL (avec gestion d'erreur améliorée)
       ======================= */
    try {
        $stmt = $pdo->prepare("
            INSERT INTO businesses
            (name, description, category_id, phone, quartier, latitude, longitude, image, region_id, ville_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // Débogage : afficher les valeurs
        error_log("Inserting: regionId=$regionId, villeId=$villeId");

        $stmt->bindValue(1, $name);
        $stmt->bindValue(2, $description);
        $stmt->bindValue(3, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(4, $phone);
        $stmt->bindValue(5, $quartier);
        $stmt->bindValue(6, $lat);
        $stmt->bindValue(7, $lng);
        $stmt->bindValue(8, $image);
        $stmt->bindValue(9, $regionId, $regionId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(10, $villeId, $villeId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

        if ($stmt->execute()) {
            header('Location: dashboard.php');
            exit;
        } else {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Erreur d'insertion: " . $errorInfo[2]);
        }
    } catch (PDOException $e) {
        // Gestion spécifique des erreurs PDO
        error_log("PDO Error: " . $e->getMessage());
        if ($e->getCode() == '23000') {
            die("Erreur de clé étrangère: Vérifiez que la ville sélectionnée existe dans la table 'villes'.");
        }
        die("Erreur de base de données: " . $e->getMessage());
    } catch (Exception $e) {
        error_log("General Error: " . $e->getMessage());
        die("Erreur: " . $e->getMessage());
    }
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
    .error { color: red; font-size: 0.9em; margin-top: 5px; }
</style>
</head>

<body class="bg-light">
<div class="container my-4">

<h3 class="mb-3">📍 Ajouter un business</h3>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div id="map"></div>
<p class="text-muted">Cliquez sur la carte pour définir l'emplacement du business</p>

<form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
    <div class="row g-3">

        <div class="col-md-6">
            <label class="form-label">Nom du business <span class="text-danger">*</span></label>
            <input class="form-control" name="name" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
            <select class="form-select" name="category" required>
                <option value="">-- Sélectionner --</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="3"></textarea>
        </div>

        <div class="col-md-6">
            <label class="form-label">Téléphone</label>
            <input class="form-control" name="phone" type="tel">
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
                    <option value="<?= htmlspecialchars($r['id']) ?>"><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Ville -->
        <div class="col-md-6">
            <label class="form-label">Ville</label>
            <select class="form-select" name="ville" id="ville">
                <option value="">-- Sélectionner une ville --</option>
            </select>
            <small class="text-muted">Sélectionnez d'abord une région</small>
        </div>

        <div class="col-md-6">
            <label class="form-label">Latitude <span class="text-danger">*</span></label>
            <input class="form-control" name="lat" id="lat" readonly required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Longitude <span class="text-danger">*</span></label>
            <input class="form-control" name="lng" id="lng" readonly required>
        </div>

        <div class="col-12">
            <label class="form-label">Image <span class="text-danger">*</span></label>
            <input type="file" class="form-control" name="image" accept="image/*" required>
            <small class="text-muted">Formats acceptés: JPG, PNG, GIF</small>
        </div>

        <div class="col-12 text-end">
            <a href="dashboard.php" class="btn btn-secondary me-2">Annuler</a>
            <button type="submit" class="btn btn-success">💾 Enregistrer</button>
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
document.getElementById('region').addEventListener('change', function () {
    const regionId = this.value;
    const villeSelect = document.getElementById('ville');

    villeSelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';

    if (regionId) {
        fetch('get_villes.php?region_id=' + regionId)
            .then(res => {
                if (!res.ok) throw new Error('Erreur réseau');
                return res.json();
            })
            .then(data => {
                if (Array.isArray(data)) {
                    data.forEach(v => {
                        const opt = document.createElement('option');
                        opt.value = v.id;
                        opt.textContent = v.name;
                        villeSelect.appendChild(opt);
                    });
                }
            })
            .catch(err => {
                console.error('Erreur chargement villes:', err);
                villeSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }
});
</script>

<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBAmGv7nVuVuYx8Zmph6DmBH1SxIIa9UAM&callback=initMap">
</script>

</body>
</html>