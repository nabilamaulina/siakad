<?php
require_once 'database.php';

function log_activity($id_user, $aktivitas) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_logs (id_user, aktivitas) VALUES (?, ?)");
    $stmt->execute([$id_user, $aktivitas]);
}

function get_greeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 11) return "Selamat Pagi";
    if ($hour >= 11 && $hour < 15) return "Selamat Siang";
    if ($hour >= 15 && $hour < 18) return "Selamat Sore";
    return "Selamat Malam";
}

function calculate_grade($nilai) {
    if ($nilai >= 85) return 'A';
    if ($nilai >= 75) return 'B';
    if ($nilai >= 60) return 'C';
    if ($nilai >= 45) return 'D';
    return 'E';
}