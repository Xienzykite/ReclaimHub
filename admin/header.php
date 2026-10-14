<?php require_once __DIR__ . '/../config.php'; $adm = admin_me(); $toast = flash(); ?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>PIT Finds · Staff portal</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;800&family=Instrument+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../style.css"><link rel="stylesheet" href="admin.css"></head><body class="admin">
<header><a class="logo" href="<?= $adm ? 'index.php' : 'login.php' ?>"><b>🏷️</b>PIT Finds<span class="pill">STAFF</span></a><nav>
<?php if ($adm) echo '<a href="index.php" class="on">Dashboard</a><a href="logout.php">Log out (' . e($adm['name']) . ')</a>'; else echo '<a href="../index.php">Student site</a>'; ?></nav></header>
<main class="page">
