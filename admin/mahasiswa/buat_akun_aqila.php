<?php
require_once '../../config/database.php';

$password = password_hash('220101001', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
INSERT INTO users
(username,password,role,is_active)
VALUES
(?,?, 'mahasiswa',1)
");

$stmt->execute([
    '220101001',
    $password
]);

echo 'Berhasil';