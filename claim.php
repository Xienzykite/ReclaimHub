<?php require_once 'config.php'; need_login(); check();
$id = (int)($_POST['item_id'] ?? 0); $m = mb_substr(trim($_POST['message'] ?? ''), 0, 1000); $uid = me()['id'];
$it = q('SELECT user_id FROM items WHERE id=? AND status="approved"', [$id])->fetch();
if (!$it) flash('That item is no longer available');
elseif ($it['user_id'] == $uid) flash('You cannot claim your own report');
elseif ($m === '') flash('Please describe how you can prove it is yours');
elseif (q('SELECT 1 FROM claims WHERE item_id=? AND user_id=? AND status IN ("pending","approved")', [$id, $uid])->fetch()) flash('You already sent a claim for this item');
else { q('INSERT INTO claims (item_id,user_id,message) VALUES (?,?,?)', [$id, $uid, $m]); flash('Claim sent to the office'); }
go('browse.php');
