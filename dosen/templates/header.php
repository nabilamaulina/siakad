<?php
// siakad/dosen/templates/header.php

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
$role_aktif = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';

if (function_exists('middleware')) {
    if ($role_aktif === 'admin') {
        middleware(['admin']);
    } elseif ($role_aktif === 'dosen') {
        middleware(['dosen']);
    } elseif ($role_aktif === 'mahasiswa') {
        middleware(['mahasiswa']);
    } else {
        middleware(); 
    }
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
            font-size: 14px;
            border-radius: 10px;
            margin: 4px 0;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .nav-link:hover,
        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            font-weight: 600;
        }
    </style>
</head>
<body>