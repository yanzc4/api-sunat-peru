<?php
require 'vendor/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=facturacion', 'root', '');
$stmt = $pdo->query("SELECT * FROM usuarios WHERE email = 'admin@admin.com'");
$row = $stmt->fetch();
var_dump($row);
var_dump(password_verify('admin123', $row['password']));
