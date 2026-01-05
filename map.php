<?php
require 'db.php';

/* ================================
   PARAMÈTRES URL
================================ */
$q        = $_GET['q'] ?? '';
$region   = $_GET['region'] ?? '';
$ville    = $_GET['ville'] ?? '';
$category = $_GET['category'] ?? '';
$id       = $_GET['id'] ?? null;
$zoom     = $_GET['zoom'] ?? 6;

/* ================================
   RÉGIONS ET CATÉGORIES
================================ */
$regions = $pdo->query("SELECT * FROM regions ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

/* ================================
   REQUÊTE BUSINESS
================================ */
$sql = "
    SELECT b.*, c.name AS category_name, r.name AS region_name, v.name AS ville_name
    FROM businesses b
    JOIN categories c ON b.category_id=c.id
    LEFT JOIN regions r ON b.region_id=r.id
    LEFT JOIN villes v ON b.ville_id=v.id
    WHERE b.status=1
";

$params = [];

/* 🔗 Un seul business pour partage */
if($id){
    $sql .= " AND b.id=?";
    $params[] = $id;
} else {
    if($q){
        $sql .= " AND (
            LOWER(b.name) LIKE ? OR
            LOWER(c.name) LIKE ? OR
            LOWER(COALESCE(v.name,'')) LIKE ?
        )";
        $params[] = "%".strtolower($q)."%";
        $params[] = "%".strtolower($q)."%";
        $params[] = "%".strtolower($q)."%";
    }
    if($category) { $sql .= " AND b.category_id=?"; $params[]=$category; }
    if($region)   { $sql .= " AND (b.region_id=? OR b.region_id IS NULL)"; $params[]=$region; }
    if($ville)    { $sql .= " AND (b.ville_id=? OR b.ville_id IS NULL)"; $params[]=$ville; }
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ================================
   CENTRAGE INITIAL (Dakar, Thiès, Mbour)
================================ */
$centerPoints = [
    ['lat'=>14.6928,'lng'=>-17.4467], // Dakar
    ['lat'=>14.7910,'lng'=>-16.9255], // Thiès
    ['lat'=>14.4333,'lng'=>-16.9833]  // Mbour
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>ThiesBusiness – Carte des business</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<style>
body { margin:0; font-family: Arial, sans-serif; }
#map { height:80vh; width:100%; }
.filters { padding:10px; background:#f8f9fa; display:flex; flex-wrap:wrap; gap:8px; }
input, select, button { padding:8px; }
.share-btn { display:inline-block; margin-top:5px; padding:5px 8px; border:none; border-radius:4px; font-size:13px; cursor:pointer; text-decoration:none; }
.share-map { background:#0d6efd; color:#fff; }
.share-wa { background:#25D366; color:#fff; }
.share-copy { background:#6c757d; color:#fff; }
#zoomControls { position:absolute; right:15px; bottom:80px; z-index:5; display:flex; flex-direction:column; }
#zoomControls button { background:#000; color:#fff; border:none; padding:10px; font-size:18px; cursor:pointer; margin-top:3px; }
</style>
</head>
<body>

<!-- FILTRES -->
<div class="filters">
    <input type="text" id="searchInput" placeholder="🔍 Rechercher un business ou restaurant" value="<?= htmlspecialchars($q) ?>">
    <select id="category">
        <option value="">Toutes catégories</option>
        <?php foreach($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($category==$c['id']?'selected':'') ?>><?= $c['name'] ?></option>
        <?php endforeach; ?>
    </select>
    <select id="region">
        <option value="">🌍 Toutes les régions</option>
        <?php foreach($regions as $r): ?>
            <option value="<?= $r['id'] ?>" <?= ($region==$r['id']?'selected':'') ?>><?= $r['name'] ?></option>
        <?php endforeach; ?>
    </select>
    <select id="ville">
        <option value="">🏙 Toutes les villes</option>
    </select>
    <button id="btnSearch">Filtrer</button>
</div>

<div id="map"></div>
<div id="zoomControls">
    <button onclick="map.setZoom(map.getZoom()+1)">➕</button>
    <button onclick="map.setZoom(map.getZoom()-1)">➖</button>
</div>

<script>
const businesses = <?= json_encode($businesses) ?>;
const icons = {
    "boutique":"icons/shop.png",
    "restaurant":"icons/restaurant.png",
    "service":"icons/service.png",
    "hotel":"icons/hotel.png",
    "informatique":"icons/informatique.png",
    "autre":"icons/autre.png"
};

let map, markers=[], bounds;

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat:14.6928, lng:-17.4467 },
        zoom:6,
        zoomControl:true,
        zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_BOTTOM },
        mapTypeControl:false,
        streetViewControl:false,
        fullscreenControl:true,
        styles: [
            { elementType: "labels", stylers: [{ visibility: "off" }] },
            { featureType: "road", stylers: [{ visibility: "simplified" }] },
            { featureType: "water", stylers: [{ color: "#cfe9ff" }] }
        ]
    });

    bounds = new google.maps.LatLngBounds();

    // Centrage sur Dakar, Thiès, Mbour
    const centerPoints = <?= json_encode($centerPoints) ?>;
    centerPoints.forEach(p => bounds.extend(p));

    const baseUrl = window.location.origin;

    businesses.forEach(b => {
        const pos = { lat: parseFloat(b.latitude), lng: parseFloat(b.longitude) };
        const marker = new google.maps.Marker({
            position: pos,
            map: map,
            title: b.name,
            icon: { url: icons[b.category_name.toLowerCase()] || icons["autre"], scaledSize:new google.maps.Size(32,32) }
        });

        marker.businessName = b.name.toLowerCase();
        marker.categoryId = b.category_id;
        marker.regionId = b.region_id ?? '';
        marker.villeId = b.ville_id ?? '';

        const shareUrl = baseUrl + "/map.php?id=" + b.id + "&zoom=16";
        const waUrl = "https://wa.me/?text=" + encodeURIComponent(b.name + " 📍 " + shareUrl);

        const content = document.createElement('div');
        content.className="info-window";
        content.innerHTML = `
            <strong>${b.name}</strong><br>
            ${b.category_name}<br>
            📍 ${b.ville_name ?? 'Inconnue'}, ${b.region_name ?? 'Inconnue'}<br>
            📞 <a href="tel:${b.phone}">${b.phone}</a><br><br>
            <button class="share-btn share-copy" onclick="navigator.clipboard.writeText('${shareUrl}')">📋 Copier lien</button>
            <a href="${shareUrl}" target="_blank" class="share-btn share-map">🔗 URL</a>
            <a href="${waUrl}" target="_blank" class="share-btn share-wa">💬 WhatsApp</a>
        `;

        const infoWindow = new google.maps.InfoWindow({ content });
        marker.addListener("click", ()=>infoWindow.open(map, marker));

        markers.push(marker);
        bounds.extend(pos);
    });

    map.fitBounds(bounds);
}

// 🔍 Recherche instantanée et filtres
function filterMarkers(){
    const search=document.getElementById('searchInput').value.toLowerCase();
    const category=document.getElementById('category').value;
    const region=document.getElementById('region').value;
    const ville=document.getElementById('ville').value;

    let visibleCount=0;
    let boundsSearch=new google.maps.LatLngBounds();

    markers.forEach(marker=>{
        let match=true;
        if(search && !marker.businessName.includes(search)) match=false;
        if(category && marker.categoryId!=category) match=false;
        if(region && marker.regionId!=region) match=false;
        if(ville && marker.villeId!=ville) match=false;

        if(match){
            marker.setMap(map);
            boundsSearch.extend(marker.getPosition());
            visibleCount++;
        } else marker.setMap(null);
    });

    if(visibleCount===1) map.setZoom(16);
    if(visibleCount>=1) map.fitBounds(boundsSearch);
}

document.getElementById('btnSearch').addEventListener('click',filterMarkers);
document.getElementById('searchInput').addEventListener('keyup',filterMarkers);
document.getElementById('category').addEventListener('change',filterMarkers);
document.getElementById('region').addEventListener('change', function(){
    fetch('get_villes.php?region_id='+this.value)
    .then(res=>res.json())
    .then(data=>{
        const ville=document.getElementById('ville');
        ville.innerHTML='<option value="">🏙 Toutes les villes</option>';
        data.forEach(v=>ville.innerHTML+=`<option value="${v.id}">${v.name}</option>`);
        filterMarkers();
    });
});
document.getElementById('ville').addEventListener('change',filterMarkers);
</script>

<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBAmGv7nVuVuYx8Zmph6DmBH1SxIIa9UAM&callback=initMap"></script>
</body>
</html>
