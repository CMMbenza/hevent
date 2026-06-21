<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>H-EVENT invitation numérique</title>

    <link rel="shortcut icon" href="../assets/images/700ea036-0c77-4542-81a1-725f7d2c3030.jpg" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@600;700&family=Montserrat:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
    :root {
        --primary-rose: #d4a396;
        --primary-rose-dark: #b88679;
        --dark-slate: #1a2232;
        --bg-light: #fdfbf9;
        --sidebar-width: 260px;
        --sidebar-collapsed-width: 85px;
    }

    body {
        margin: 0;
        background-color: #fcf9f6;
        font-family: 'Montserrat', sans-serif;
        color: #333;
        overflow-x: hidden;
    }

    /* --- CONFIGURATION DU WRAPPER GLOBAL --- */
    .wrapper {
        display: flex;
        min-height: 100vh;
        width: 100vw;
        overflow-x: hidden;
    }

    /* --- SIDEBAR --- */
    .sidebar {
        width: var(--sidebar-width);
        flex-shrink: 0;
        background-color: #ffffff;
        box-shadow: 4px 0 25px rgba(26, 34, 50, 0.03);
        border-right: 1px solid rgba(212, 163, 150, 0.15);
        padding: 30px 20px;
        display: flex;
        flex-direction: column;
        transition: width 0.3s ease-in-out, padding 0.3s ease-in-out;
        z-index: 1000;
    }

    /* --- SIDEBAR RÉDUITE (COLLAPSED) --- */
    .sidebar.collapsed {
        width: var(--sidebar-collapsed-width);
        padding: 30px 15px;
    }

    .sidebar .logo-box {
        margin-bottom: 40px;
        padding-left: 10px;
        transition: all 0.3s ease;
    }

    .sidebar .logo-text {
        font-family: 'Great Vibes', cursive;
        font-size: 2.2rem;
        color: var(--primary-rose);
        text-decoration: none;
        display: block;
        white-space: nowrap;
        transition: all 0.3s ease;
    }

    .sidebar .menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar .menu li {
        margin-bottom: 8px;
    }

    .sidebar .menu li a {
        text-decoration: none;
        color: #666;
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 18px;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.95rem;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .sidebar .menu li a i {
        font-size: 1.2rem;
        transition: transform 0.2s;
    }

    .sidebar .menu li a:hover {
        background-color: var(--bg-light);
        color: var(--primary-rose-dark);
    }

    .sidebar .menu li a:hover i {
        transform: scale(1.1);
    }

    .sidebar .menu li.active a {
        background-color: var(--dark-slate);
        color: var(--primary-rose);
        box-shadow: 0 4px 15px rgba(26, 34, 50, 0.15);
    }

    .sidebar.collapsed .logo-text {
        font-size: 1.8rem;
        text-align: center;
    }

    .sidebar.collapsed .menu li a {
        justify-content: center;
        padding: 12px;
    }

    .sidebar.collapsed .menu li a span {
        display: none;
    }

    .sidebar.collapsed .menu li a i {
        margin: 0;
    }

    /* --- LE CONTENU PRINCIPAL --- */
    .main-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        background-color: #fcf9f6;
        transition: all 0.3s ease-in-out;
    }

    .main {
        flex: 1;
        padding: 30px;
    }

    /* --- COMPOSANTS DU DASHBOARD --- */
    .dashboard-card {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 24px;
        border: 1px solid rgba(212, 163, 150, 0.12);
        box-shadow: 0 10px 30px rgba(26, 34, 50, 0.02);
        height: 100%;
    }

    .stat-box {
        border-radius: 16px;
        padding: 24px;
        transition: transform 0.2s;
        border: 1px solid transparent;
    }

    .stat-box:hover {
        transform: translateY(-3px);
    }

    .pink {
        background-color: #fdf2f0;
        border-color: #f5dcd6;
        color: #8a4f41;
    }

    .orange {
        background-color: #fdf8f0;
        border-color: #f5e6d6;
        color: #8a6a41;
    }

    .green {
        background-color: #f2fdf5;
        border-color: #d6f5de;
        color: #418a53;
    }

    .purple {
        background-color: #f6f2fd;
        border-color: #ded6f5;
        color: #5b418a;
    }

    .chart-box {
        background-color: #ffffff;
        border-radius: 16px;
        padding: 24px;
        min-height: 320px;
        border: 1px solid rgba(212, 163, 150, 0.12);
        box-shadow: 0 10px 30px rgba(26, 34, 50, 0.02);
    }

    @media(max-width: 991px) {
        .sidebar {
            position: fixed;
            left: -var(--sidebar-width);
            height: 100vh;
        }

        .sidebar.collapsed {
            left: 0;
            width: var(--sidebar-width);
        }

        .sidebar.collapsed .menu li a span {
            display: block;
        }
    }
    </style>
</head>

<body>
    <div class="wrapper">