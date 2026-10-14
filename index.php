<?php require 'header.php';
$ok = q('SELECT COUNT(*) FROM items WHERE status="approved"')->fetchColumn();
$rt = q('SELECT COUNT(*) FROM items WHERE status="returned"')->fetchColumn();
$us = q('SELECT COUNT(*) FROM users WHERE school_id<>"SECURITY"')->fetchColumn(); ?>
<div class="hero"><div><h1>Lost something on campus? Found something?</h1><p>Report it here, check what others turned in, and get belongings back to their owners. Approved items are released at <?= e(PICKUP_POINT) ?>.</p>
<div class="row" style="margin-top:1.2rem"><a class="btn a" href="report.php?type=lost">Report lost item</a><a class="btn" href="report.php?type=found">Report found item</a><a class="btn g" href="browse.php">Browse items</a></div></div>
<div class="rack"><div class="swing"><i></i><div class="tg c">LOST</div></div><div class="swing"><i></i><div class="tg t">FOUND</div></div><div class="swing"><i></i><div class="tg">RETURNED</div></div></div></div>
<div class="stats"><div class="stat" style="--i:0"><b data-n="<?= $ok ?>">0</b><span>items listed</span></div><div class="stat" style="--i:1"><b data-n="<?= $rt ?>">0</b><span>returned to owners</span></div><div class="stat" style="--i:2"><b data-n="<?= $us ?>">0</b><span>campus members</span></div></div>
<?php require 'footer.php';
