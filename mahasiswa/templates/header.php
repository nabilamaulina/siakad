<?php
// siakad/mahasiswa/templates/header.php

// 1. Memastikan session sudah dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mengamankan pemuatan fungsi dan security jika file pendukung tersedia
$sec_path = __DIR__ . '/../../config/security.php';
$fun_path = __DIR__ . '/../../config/function.php';

if (file_exists($sec_path)) { require_once $sec_path; }
if (file_exists($fun_path)) { require_once $fun_path; }

/**
 * Mengamankan halaman secara dinamis berdasarkan role yang sedang login.
 */
if (function_exists('middleware')) {
    middleware(['mahasiswa']);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOBAT IK — Sistem Operasional & Basis Akademik Terpadu Ilmu Komputer.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f4f7f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .nav-link {
            color: rgba(255, 255, 255, 0.75) !important;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff !important;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
    </style>
</head>
<body>

<div class="d-flex" id="wrapper" style="min-height: 100vh;">
    
    <?php 
    // Memanggil file sidebar mahasiswa asli milikmu.
    // sidebar.php bawaanmu otomatis membuka #page-content-wrapper dan mencetak top navbar.
    require_once __DIR__ . '/sidebar.php'; 
    ?>

    <div class="container-fluid p-4 flex-grow-1">