<?php
require 'db.php';

$name = "Admin Principal";
$email = "pfaye3@gmail.com";
$password = password_hash("papis2026", PASSWORD_DEFAULT);


$stmt = $pdo->prepare(
    "INSERT INTO usersbusinessthies (name, email, password, role) VALUES (?,?,?,?)"
);
$stmt->execute([$name, $email, $password, 'admin']);

echo "✅ Admin créé avec succès";
