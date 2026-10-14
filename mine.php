<?php require 'header.php'; need_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check(); q('DELETE FROM items WHERE id=? AND user_id=? AND status="pending"', [(int)$_POST['id'], $me['id']]); flash('Report deleted'); go('mine.php'); }
$my = q('SELECT i.*,(SELECT COUNT(*) FROM claims c WHERE c.item_id=i.id) AS nc FROM items i WHERE user_id=? ORDER BY id DESC', [$me['id']])->fetchAll();
$cl = q('SELECT c.*,i.title FROM claims c JOIN items i ON i.id=c.item_id WHERE c.user_id=? ORDER BY c.id DESC', [$me['id']])->fetchAll();
page_head('My reports', 'Track your reports and claims.', 'MINE', 't');
if (!$my) echo '<div class="empty">No reports yet. <a href="report.php">Report an item</a></div>'; else { echo '<div class="grid">';
foreach ($my as $k => $it) { $a = '<span class="badge ' . $it['status'] . '">' . $it['status'] . '</span>' . ($it['nc'] ? '<small>' . $it['nc'] . ' claim(s)</small>' : '') . ($it['note'] !== '' ? '<span class="note">Office: ' . e($it['note']) . '</span>' : '');
    if ($it['status'] === 'pending') $a .= '<form method="post" onsubmit="return confirm(\'Delete this report?\')">' . csrf() . '<input type="hidden" name="id" value="' . $it['id'] . '"><button class="btn s r">Delete</button></form>';
    card($it, $k, $a); } echo '</div>'; }
if ($cl) { echo '<h2 style="margin:2rem 0 .8rem">My claims</h2><div class="panel w scroll"><table class="tbl"><tr><th>Item</th><th>Your message</th><th>Status</th></tr>';
foreach ($cl as $c) echo '<tr><td>' . e($c['title']) . '</td><td>' . e($c['message']) . '</td><td><span class="badge ' . ($c['status'] === 'approved' ? 'returned' : ($c['status'] === 'rejected' ? 'rejected' : 'pending')) . '">' . $c['status'] . '</span>' . ($c['status'] === 'approved' ? '<br><small>Collect at ' . e(PICKUP_POINT) . ' with your school ID.</small>' : '') . '</td></tr>';
echo '</table></div>'; }
require 'footer.php';
