<?php
// templates/header.php
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/function.php';
middleware();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOBAT IK — Sistem Operasional & Basis Akademik Terpadu Ilmu Komputer.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --ocean-bg: #eef3f2;
            --ocean-light: #d4e0de;
            --ocean-sand: #e6e1da;
            --ocean-muted: #5e8281;
            --ocean-deep: #245358;
            --text-dark: #2c3e40;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--ocean-bg);
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* STRUKTUR PEMBUNGKUS LAYOUT UTAMA */
        .wrapper-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* STYLING SIDEBAR KIRI */
        #sidebar {
            background: linear-gradient(180deg, var(--ocean-deep) 0%, #17373b 100%) !important;
            box-shadow: 3px 0 15px rgba(36, 83, 88, 0.15);
            width: 260px;
            min-width: 260px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        /* MENJAGA PRESISI ALIGNMENT BOOTSTRAP */
        #sidebar .nav-item {
            width: 100%;
            padding: 0 15px;
        }

        #sidebar .nav-link {
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

        #sidebar .nav-link:hover,
        #sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15) !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        /* STYLING SUB-MENU DROPDOWN ACADEMIK */
        #menuAkademik .nav-link {
            padding-left: 45px !important;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6) !important;
        }

        #menuAkademik .nav-link:hover,
        #menuAkademik .nav-link.active {
            color: #ffffff !important;
            background-color: transparent !important;
            font-weight: bold;
        }

        .nav-link:not(.collapsed) .chevron-arrow {
            transform: rotate(180deg);
        }

        .chevron-arrow {
            transition: transform 0.2s ease;
        }

        /* KONTEN KANAN UTAMA */
        #main-content {
            flex-grow: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-color: var(--ocean-bg);
            overflow-x: hidden;
        }

        .navbar-custom {
            background-color: #ffffff !important;
            border-bottom: 1px solid rgba(36, 83, 88, 0.1);
        }

        /* UTILITY TAMBAHAN */
        .card-custom {
            border: 1px solid rgba(94, 130, 129, 0.2);
            border-radius: 12px;
            background: #ffffff;
        }

        .text-navy { color: var(--ocean-deep) !important; font-weight: 700; }
        .bg-navy { background: var(--ocean-deep) !important; }

        @media (max-width: 992px) {
            #sidebar { margin-left: -260px; position: absolute; z-index: 1000; }
            #sidebar.active { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="wrapper-layout">