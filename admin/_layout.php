<?php require_once __DIR__ . '/../config.php';
function admin_head($title) {
    $a = admin_me(); $toast = flash();
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e($title) . ' · PIT Finds Staff</title>'
        . '<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;800&family=Instrument+Sans:wght@400;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="../style.css?v=' . @filemtime(__DIR__ . '/../style.css') . '"><link rel="stylesheet" href="admin.css?v=' . @filemtime(__DIR__ . '/admin.css') . '"></head><body class="admin">'
        . '<header><a class="logo" href="index.php"><b>🏷️</b>PIT Finds<span class="pill">STAFF</span></a><nav><a href="../index.php">View site</a>'
        . ($a ? '<a href="logout.php">Log out (' . e($a['username']) . ')</a>' : '') . '</nav></header><main class="page">';
    if ($toast) echo '<div class="toast" id="toast">' . e($toast) . '</div><script>setTimeout(()=>document.getElementById("toast")?.remove(),3000)</script>';
}
function admin_foot() { echo '</main><p class="afoot">PIT Finds staff portal · <a href="../index.php">View public site</a></p></body></html>'; }
