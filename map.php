<?php
require 'db.php';

/*
  On récupère les business actifs avec leur catégorie
*/
$businesses = $pdo->query("
    SELECT b.*, c.name AS category_name
    FROM businesses b
    JOIN categories c ON b.category_id = c.id
    WHERE b.status = 1
")->fetchAll(PDO::FETCH_ASSOC);
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
</style>
</head>

<body>

<!-- BARRE HAUT -->
<div class="top-bar">
    <strong>📍 Business à Thiès</strong>
    <a href="index.php">⬅️ Accueil</a>
</div>

<div id="map"></div>

<script>
const businesses = <?= json_encode($businesses); ?>;

/*
  Icônes par catégorie
  👉 crée un dossier /icons et mets les images correspondantes
*/
const icons = {
    "Boutique": "icons/shop.png",
    "Restaurant": "icons/restaurant.png",
    "Service": "icons/service.png",
    "Hotel": "icons/hotel.png"
};

function initMap() {

    const thies = { lat: 14.791005, lng: -16.925502 };

    const map = new google.maps.Map(document.getElementById("map"), {
        zoom: 13,
        center: thies,

        // STYLE ÉPURÉ (sans infos Google)
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
            position: {
                lat: parseFloat(b.latitude),
                lng: parseFloat(b.longitude)
            },
            map: map,
            title: b.name,
            icon: icons[b.category_name] || "icons/default.png"
        });

        const content = `
            <div class="info-window">
                <strong>${b.name}</strong><br>
                <small>${b.category_name}</small><br>
                <img src="uploads/${b.image}">
                <br>📞 <a href="tel:${b.phone}">${b.phone}</a>
                <br>📍 ${b.quartier}
            </div>
        `;

        const infoWindow = new google.maps.InfoWindow({
            content: content
        });

        marker.addListener("click", () => {
            infoWindow.open(map, marker);
        });
    });
}
</script>

<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBAmGv7nVuVuYx8Zmph6DmBH1SxIIa9UAM&callback=initMap"
    async defer>
</script>

</body>
</html>
