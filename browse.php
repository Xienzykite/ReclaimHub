<?php require 'header.php';
$qs = trim($_GET['q'] ?? ''); $t = $_GET['t'] ?? ''; $c = $_GET['c'] ?? ''; $l = $_GET['l'] ?? ''; $df = $_GET['df'] ?? '';
$isd = fn($d) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
if (!$isd($df)) $df = '';
$w = ['status="approved"']; $a = [];
if (in_array($t, ['lost', 'found'])) { $w[] = 'type=?'; $a[] = $t; }
if (isset($CATS[$c])) { $w[] = 'cat=?'; $a[] = $c; }
if (in_array($l, $LOCS)) { $w[] = 'place LIKE ?'; $a[] = $l . '%'; }
if ($df !== '') { $w[] = 'seen_on=?'; $a[] = $df; }
if ($qs !== '') { $w[] = '(title LIKE ? OR descr LIKE ? OR place LIKE ?)'; array_push($a, "%$qs%", "%$qs%", "%$qs%"); }
$items = q('SELECT * FROM items WHERE ' . implode(' AND ', $w) . ' ORDER BY seen_on DESC, id DESC', $a)->fetchAll();
$sent = $me ? array_map('intval', q('SELECT item_id FROM claims WHERE user_id=?', [$me['id']])->fetchAll(PDO::FETCH_COLUMN)) : [];
$filtered = $qs !== '' || $t !== '' || $c !== '' || $l !== '' || $df !== '';
page_head('Browse items', 'Reported on campus and approved by the office.', 'FIND', 't'); ?>
<form class="row" method="get" style="margin-bottom:1rem">
<input style="max-width:200px" name="q" placeholder="Search items" value="<?= e($qs) ?>" aria-label="Search">
<select style="max-width:140px" name="t" aria-label="Type"><option value="">Lost + found</option><option value="lost" <?= $t === 'lost' ? 'selected' : '' ?>>Lost</option><option value="found" <?= $t === 'found' ? 'selected' : '' ?>>Found</option></select>
<select style="max-width:160px" name="c" aria-label="Category"><option value="">All categories</option><?php foreach ($CATS as $k => $_) echo '<option ' . ($c === $k ? 'selected' : '') . '>' . e($k) . '</option>'; ?></select>
<select style="max-width:230px" name="l" aria-label="Campus location"><option value="">Anywhere on campus</option><?php foreach ($LOCS as $k) echo '<option ' . ($l === $k ? 'selected' : '') . '>' . e($k) . '</option>'; ?></select>
<input style="max-width:150px" type="date" name="df" value="<?= e($df) ?>" max="<?= date('Y-m-d') ?>" aria-label="Date" title="Date">
<button class="btn">Filter</button><?php if ($filtered) echo '<a class="btn g" href="browse.php">Clear</a>'; ?></form>
<?php if (!$items) echo '<div class="empty">No items match. Try clearing filters, or report your own.</div>'; else { echo '<div class="grid">';
foreach ($items as $i => $it) {
    $lbl = $it['type'] === 'found' ? 'This is mine' : 'I found this';
    if (!$me) $act = '<a class="btn s a" href="login.php">' . $lbl . '</a>';
    elseif ($it['user_id'] == $me['id']) $act = '<span class="badge">Your report</span>';
    elseif (in_array((int)$it['id'], $sent, true)) $act = '<span class="badge pending">Claim sent</span>';
    else $act = '<button class="btn s a" data-claim="' . $it['id'] . '" data-title="' . e($it['title']) . '">' . $lbl . '</button>';
    card($it, $i, $act);
} echo '</div>'; }
require 'footer.php';
