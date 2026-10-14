<?php require_once __DIR__ . '/config.php'; $me = me(); $cur = basename($_SERVER['PHP_SELF']); $toast = flash();
$nav = [['index.php', 'Home'], ['browse.php', 'Browse']];
if ($me) { $nav[] = ['report.php', 'Report']; $nav[] = ['mine.php', 'My reports']; }
if (!$me) { $nav[] = ['login.php', 'Log in']; $nav[] = ['register.php', 'Sign up']; }
?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>PIT Finds</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;800&family=Instrument+Sans:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css?v=<?= @filemtime(__DIR__ . '/style.css') ?>"></head><body>
<header><a class="logo" href="index.php"><b>🏷️</b>PIT Finds</a><nav>
<?php foreach ($nav as [$u, $l]) echo '<a href="' . $u . '" class="' . ($u === $cur ? 'on' : '') . '">' . $l . '</a>'; ?>
<?php if ($me) echo '<a href="logout.php">Log out (' . e(explode(' ', $me['name'])[0]) . ')</a>'; ?></nav></header>
<main class="page">
