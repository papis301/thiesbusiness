<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>ThiesBusiness – Tous les business à Thiès</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        /* NAVBAR */
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,.15);
        }

        /* HERO */
        .hero {
            min-height: 90vh;
            display: flex;
            align-items: center;
            background: linear-gradient(rgba(0,0,0,.7), rgba(0,0,0,.7)),
                        url("assets/thies.jpg") center/cover no-repeat;
            color: white;
        }

        .hero h1 {
            font-size: 3rem;
        }

        /* CATEGORY CARDS */
        .category-card {
            background: white;
            border-radius: 15px;
            padding: 30px 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,.1);
            transition: transform .3s, box-shadow .3s;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,.15);
        }

        .category-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        /* CTA */
        .cta {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
        }

        /* FOOTER */
        footer {
            font-size: 14px;
        }

        /* WhatsApp floating */
        .whatsapp-float {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background: #25D366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,.3);
            text-decoration: none;
            z-index: 1000;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">ThiesBusiness</a>
    <a href="map.php" class="btn btn-warning btn-sm">
        📍 Voir la carte
    </a>
  </div>
</nav>

<!-- HERO -->
<section class="hero text-center">
    <div class="container">
        <h1 class="fw-bold mb-3">
            Tous les business de Thiès<br>sur une seule carte
        </h1>
        <p class="lead mb-4">
            Trouvez rapidement boutiques, services, restaurants et professionnels
            proches de vous.
        </p>
            <form method="GET" style="margin:10px 0;">
                <select name="region" id="region">
                    <option value="">🌍 Toutes les régions</option>
                    <?php
                    require 'db.php';
                    $regions = $pdo->query("SELECT * FROM regions")->fetchAll();
                    foreach ($regions as $r) {
                        $selected = ($_GET['region'] ?? '') == $r['id'] ? 'selected' : '';
                        echo "<option value='{$r['id']}' $selected>{$r['name']}</option>";
                    }
                    ?>
                </select>

                <select name="ville" id="ville">
                    <option value="">🏙 Toutes les villes</option>
                </select>

                <button type="submit">Filtrer</button>
            </form>   


        <a href="map.php" class="btn btn-warning btn-lg px-4">
            Explorer la carte
        </a>
    </div>
</section>

<!-- CATEGORIES -->
<section class="container my-5 py-5">
    <h2 class="text-center fw-bold mb-5">Catégories populaires</h2>

    <div class="row g-4 text-center">
        <div class="col-md-3">
            <div class="category-card">
                <div class="category-icon">🏪</div>
                <h5>Boutiques</h5>
                <p class="text-muted">Magasins et commerces</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="category-card">
                <div class="category-icon">🧰</div>
                <h5>Services</h5>
                <p class="text-muted">Plombiers, électriciens…</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="category-card">
                <div class="category-icon">🍽️</div>
                <h5>Restaurants</h5>
                <p class="text-muted">Fast-food et resto local</p>
            </div>
        </div>

        <div class="col-md-3">
            <div class="category-card">
                <div class="category-icon">🏨</div>
                <h5>Hôtels</h5>
                <p class="text-muted">Hébergement à Thiès</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta py-5 text-center">
    <div class="container">
        <h3 class="fw-bold mb-3">Vous avez un business à Thiès ?</h3>
        <p class="mb-4">
            Contactez l’administrateur et apparaissez sur Google Maps
        </p>

        <a 
            href="https://wa.me/221766487420?text=Bonjour%20je%20souhaite%20ajouter%20mon%20business%20sur%20ThiesBusiness"
            class="btn btn-light btn-lg px-4"
            target="_blank"
        >
            💬 Contacter l’admin sur WhatsApp
        </a>
    </div>
</section>

<!-- FOOTER -->
<footer class="bg-dark text-white text-center py-3">
    © <?= date('Y') ?> ThiesBusiness – Tous droits réservés
</footer>

<!-- WhatsApp floating -->
<a 
  href="https://wa.me/221766487420"
  class="whatsapp-float"
  target="_blank"
>
  💬
</a>

</body>
</html>
