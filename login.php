<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    // 1️⃣ Récupérer l'utilisateur par email
    $stmt = $pdo->prepare("SELECT * FROM usersbusinessthies WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2️⃣ Vérifier le mot de passe
    if ($user && password_verify($password, $user['password'])) {

        // 3️⃣ Vérifier le rôle
        if ($user['role'] === 'admin') {
            $_SESSION['admin'] = $user['id'];
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Accès refusé";
        }

    } else {
        echo "Identifiants incorrects";
    }
}
?>

<form method="post">
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br>
    <button>Connexion</button>
</form>
