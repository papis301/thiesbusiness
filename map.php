<?php
require 'db.php';

// Paramètres
$q = $_GET['q'] ?? '';
$id = $_GET['id'] ?? null;

// Coordonnées pour centrer la carte
$centerLat = $_GET['lat'] ?? 14.791005;
$centerLng = $_GET['lng'] ?? -16.925502;
$zoomLevel = $_GET['zoom'] ?? 13;

// Requête principale
$sql = "
    SELECT b.*, c.name AS category_name
    FROM businesses b
    JOIN categories c ON b.category_id = c.id
    WHERE b.status = 1
";

$params = [];

// Si un id est passé → afficher uniquement ce business
if($id){
    $sql .= " AND b.id = ?";
    $params[] = $id;
}
// Sinon recherche
elseif($q){
    $sql .= " AND (LOWER(b.name) LIKE ? OR LOWER(c.name) LIKE ?)";
    $params[] = "%".strtolower($q)."%";
    $params[] = "%".strtolower($q)."%";
}


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Centrer la carte sur un seul business si id est présent
if($id && count($businesses) === 1){
    $centerLat = $businesses[0]['latitude'];
    $centerLng = $businesses[0]['longitude'];
    $zoomLevel = 16;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>ThiesBusiness – Carte des business</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body { margin:0; font-family: Arial, sans-serif; }
#map { height: 90vh; width: 100%; }

.top-bar {
    padding:10px;
    background:#000;
    color:#fff;
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.top-bar a {
    background:#ffc107;
    color:#000;
    padding:8px 12px;
    border-radius:5px;
    text-decoration:none;
    font-weight:bold;
}

.info-window img {
    width:100%;
    border-radius:6px;
    margin-top:5px;
}

.share-btn {
    display:inline-block;
    margin-top:6px;
    padding:6px 10px;
    border-radius:5px;
    text-decoration:none;
    font-size:14px;
    cursor:pointer;
    border:none;
}

.share-map { background:#0d6efd; color:#fff; }
.share-wa { background:#25D366; color:#fff; }
.share-copy { background:#6c757d; color:#fff; }
</style>
</head>

<body>

<!-- BARRE HAUT -->
<div class="top-bar">
    <strong>📍 Business à Thiès</strong>
    <a href="index.php">⬅️ Accueil</a>
</div>

<!-- RECHERCHE -->
<div style="padding:10px;background:#f8f9fa;display:flex;gap:10px;">
    <input
        type="text"
        id="searchInput"
        placeholder="🔍 Rechercher un business ou restaurant"
        style="padding:8px;width:260px;"
        value="<?= htmlspecialchars($q) ?>"
    >
</div>

<div id="map"></div>

<script>
const businesses = <?= json_encode($businesses); ?>;

// Icônes par catégorie
const icons = {
    "boutique": "icons/shop.png",
    "restaurant": "icons/restaurant.png",
    "service": "icons/service.png",
    "hotel": "icons/hotel.png",
    "informatique": "icons/informatique.png"
};

let map;
let markers = [];
const baseUrl = window.location.origin;

function initMap() {

    map = new google.maps.Map(document.getElementById("map"), {
        zoom: parseInt(<?= $zoomLevel ?>),
        center: { lat: parseFloat(<?= $centerLat ?>), lng: parseFloat(<?= $centerLng ?>) },
        styles: [
            { elementType: "labels", stylers: [{ visibility: "off" }] },
            { featureType: "poi", stylers: [{ visibility: "off" }] },
            { featureType: "transit", stylers: [{ visibility: "off" }] },
            { featureType: "road", stylers: [{ visibility: "simplified" }] },
            { featureType: "water", stylers: [{ color: "#cfe9ff" }] }
        ]
    });

    businesses.forEach(b => {

        const marker = new google.maps.Marker({
            position: { lat: parseFloat(b.latitude), lng: parseFloat(b.longitude) },
            map: map,
            title: b.name,
            icon: {
                url: icons[b.category_name.toLowerCase()] || "icons/shop.png",
                scaledSize: new google.maps.Size(32, 32),
                anchor: new google.maps.Point(16, 32)
            }
        });

        // 🔥 Données pour la recherche insensible à la casse
        marker.businessName = b.name.toLowerCase();
        marker.categoryName = b.category_name.toLowerCase();
        marker.quartier = b.quartier.toLowerCase();
        markers.push(marker);

        const mapLink = baseUrl + "/map.php?id=" + b.id + "&zoom=16";
        const waLink = "https://wa.me/?text=" + encodeURIComponent(b.name + " 📍 " + mapLink);

        const content = `
            <div class="info-window">
                <strong>${b.name}</strong><br>
                <small>${b.category_name}</small><br>
                <img src="uploads/${b.image}">
                <br>📞 <a href="tel:${b.phone}">${b.phone}</a>
                <br>📍 ${b.quartier}
                <br><br>
                <a href="${mapLink}" target="_blank" class="share-btn share-map">
                    📤 Voir sur ThiesBusiness
                </a>
                <a href="${waLink}" target="_blank" class="share-btn share-wa">
                    💬 WhatsApp
                </a>
                <button class="share-btn share-copy" id="copyLinkBtn">
                    📋 Copier le lien
                </button>
            </div>
        `;

        const infoWindow = new google.maps.InfoWindow({ content });

        marker.addListener("click", () => {
            infoWindow.open(map, marker);

            setTimeout(() => {
                const btn = document.getElementById("copyLinkBtn");
                if(btn){
                    btn.addEventListener("click", () => {
                        navigator.clipboard.writeText(mapLink).then(() => {
                            alert("Lien copié dans le presse-papier !");
                        }).catch(err => {
                            alert("Erreur : impossible de copier le lien");
                            console.error(err);
                        });
                    });
                }
            }, 100);
        });
    });
}

// 🔍 Recherche instantanée insensible à la casse
document.addEventListener("DOMContentLoaded", () => {
    const input = document.getElementById("searchInput");

    if(input.value){
        const event = new Event('keyup');
        input.dispatchEvent(event);
    }

    input.addEventListener("keyup", () => {
        const value = input.value.toLowerCase(); // ✅ insensible à la casse
        const bounds = new google.maps.LatLngBounds();
        let visibleCount = 0;

        markers.forEach(marker => {
            if (
                marker.businessName.includes(value) ||
                marker.categoryName.includes(value) ||
                marker.quartier.includes(value)
            ) {
                marker.setMap(map);
                bounds.extend(marker.getPosition());
                visibleCount++;
            } else {
                marker.setMap(null);
            }
        });

        if (visibleCount === 1) {
            map.fitBounds(bounds);
            map.setZoom(16);
        } else if (visibleCount > 1) {
            map.fitBounds(bounds);
        }
    });
});
</script>

<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBAmGv7nVuVuYx8Zmph6DmBH1SxIIa9UAM&callback=initMap"
    async defer>
</script>

</body>
</html>
