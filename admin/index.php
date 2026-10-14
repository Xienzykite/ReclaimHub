<?php require_once __DIR__ . '/_layout.php'; need_admin(); $ad = admin_me();
$tabs = ['reports' => 'Reports', 'claims' => 'Claims', 'members' => 'Members', 'admins' => 'Admins'];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'reports';
$s = $_GET['s'] ?? '';
$back = 'index.php?tab=' . $tab . ($s !== '' ? '&s=' . urlencode($s) : '');
$unlink = function ($im) { if ($im) @unlink(__DIR__ . '/../uploads/' . basename($im)); };

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check(); $act = $_POST['act'] ?? ''; $id = (int)($_POST['id'] ?? 0); $note = mb_substr(trim($_POST['note'] ?? ''), 0, 255);
    if ($act === 'item_approve') { q('UPDATE items SET status="approved", note=? WHERE id=? AND status IN ("pending","rejected")', [$note, $id]); flash('Report approved and now listed'); }
    elseif ($act === 'item_reject') {
        q('UPDATE items SET status="rejected", note=? WHERE id=? AND status IN ("pending","approved")', [$note, $id]);
        q('UPDATE claims SET status="rejected", note="This listing was removed by the office" WHERE item_id=? AND status="pending"', [$id]); flash('Report rejected');
    }
    elseif ($act === 'item_return') {
        q('UPDATE items SET status="returned", note=? WHERE id=? AND status="approved"', [$note, $id]);
        q('UPDATE claims SET status="rejected", note="Item was already returned" WHERE item_id=? AND status="pending"', [$id]); flash('Marked as returned');
    }
    elseif ($act === 'item_delete') { $unlink(q('SELECT image FROM items WHERE id=?', [$id])->fetchColumn()); q('DELETE FROM items WHERE id=?', [$id]); flash('Report deleted'); }
    elseif ($act === 'claim_approve') {
        $c = q('SELECT item_id FROM claims WHERE id=? AND status="pending"', [$id])->fetch();
        if ($c) { $db->beginTransaction();
            q('UPDATE claims SET status="approved", note=? WHERE id=?', [$note, $id]);
            q('UPDATE claims SET status="rejected", note="Another claim for this item was approved" WHERE item_id=? AND status="pending" AND id<>?', [$c['item_id'], $id]);
            q('UPDATE items SET status="returned" WHERE id=?', [$c['item_id']]);
            $db->commit(); flash('Claim approved. The item is marked returned.');
        } else flash('That claim was already decided');
    }
    elseif ($act === 'claim_reject') { q('UPDATE claims SET status="rejected", note=? WHERE id=? AND status="pending"', [$note, $id]); flash('Claim rejected'); }
    elseif ($act === 'member_delete') {
        $u = q('SELECT school_id FROM users WHERE id=?', [$id])->fetch();
        if ($u && $u['school_id'] !== 'SECURITY') {
            foreach (q('SELECT image FROM items WHERE user_id=?', [$id])->fetchAll(PDO::FETCH_COLUMN) as $im) $unlink($im);
            q('DELETE FROM users WHERE id=?', [$id]); flash('Member and their reports removed');
        }
    }
    elseif ($act === 'admin_add') {
        $un = strtolower(trim($_POST['username'] ?? '')); $nm = trim($_POST['name'] ?? ''); $pw = $_POST['pw'] ?? '';
        if (!preg_match('/^[a-z0-9_]{3,40}$/', $un) || $nm === '') flash('Username (3-40 letters, numbers, underscore) and name are required');
        elseif (strlen($pw) < 8) flash('Password needs at least 8 characters');
        elseif (q('SELECT 1 FROM admins WHERE username=?', [$un])->fetch()) flash('That username is taken');
        else { q('INSERT INTO admins (username,name,pw_hash) VALUES (?,?,?)', [$un, mb_substr($nm, 0, 60), password_hash($pw, PASSWORD_DEFAULT)]); flash('Admin added'); }
    }
    elseif ($act === 'admin_del') {
        if ($id == $ad['id']) flash('You cannot delete your own account');
        elseif (q('SELECT COUNT(*) FROM admins')->fetchColumn() <= 1) flash('At least one admin must remain');
        else { q('DELETE FROM admins WHERE id=?', [$id]); flash('Admin removed'); }
    }
    elseif ($act === 'pw_change') {
        $h = q('SELECT pw_hash FROM admins WHERE id=?', [$ad['id']])->fetchColumn(); $new = $_POST['new'] ?? '';
        if (!password_verify($_POST['cur'] ?? '', $h)) flash('Current password is incorrect');
        elseif (strlen($new) < 8 || $new !== ($_POST['new2'] ?? '')) flash('New password needs 8+ characters and must match the confirmation');
        else { q('UPDATE admins SET pw_hash=? WHERE id=?', [password_hash($new, PASSWORD_DEFAULT), $ad['id']]); flash('Password changed'); }
    }
    go($back);
}

$np = (int)q('SELECT COUNT(*) FROM items WHERE status="pending"')->fetchColumn();
$nc = (int)q('SELECT COUNT(*) FROM claims WHERE status="pending"')->fetchColumn();
$cnt = ['reports' => $np, 'claims' => $nc];
$nl = (int)q('SELECT COUNT(*) FROM items WHERE status="approved"')->fetchColumn();
$nr = (int)q('SELECT COUNT(*) FROM items WHERE status="returned"')->fetchColumn();
$nm = (int)q('SELECT COUNT(*) FROM users WHERE school_id<>"SECURITY"')->fetchColumn();
admin_head('Dashboard'); page_head('Dashboard', 'Review reports, decide claims, manage members. Your notes are shown to users.', 'STAFF', 'c');
echo '<div class="stats"><div class="stat' . ($np ? ' hot' : '') . '" style="--i:0"><b>' . $np . '</b><span>reports to review</span></div><div class="stat' . ($nc ? ' hot' : '') . '" style="--i:1"><b>' . $nc . '</b><span>claims to decide</span></div><div class="stat" style="--i:2"><b>' . $nl . '</b><span>items listed</span></div><div class="stat" style="--i:3"><b>' . $nr . '</b><span>returned</span></div><div class="stat" style="--i:4"><b>' . $nm . '</b><span>members</span></div></div>';
echo '<div class="tabs">'; foreach ($tabs as $k => $l) echo '<a href="index.php?tab=' . $k . '" class="' . ($k === $tab ? 'on' : '') . '">' . $l . (!empty($cnt[$k]) ? '<i>' . $cnt[$k] . '</i>' : '') . '</a>'; echo '</div>';
$chips = function ($opts, $cur) use ($tab) { echo '<div class="tabs sub">'; foreach ($opts as $o) echo '<a href="index.php?tab=' . $tab . '&s=' . $o . '" class="' . ($o === $cur ? 'on' : '') . '">' . ucfirst($o) . '</a>'; echo '</div>'; };
$hints = ['reports' => 'Approve a report to list it publicly. Rejected reports stay hidden; your note tells the user why.', 'claims' => 'Check the proof against the item description before approving. Approving returns the item and closes other claims.', 'members' => 'Everyone registered on the public site.', 'admins' => 'Staff accounts for this portal.'];
echo '<p class="hint">' . e($hints[$tab]) . '</p>';
$noteform = fn($id, $btns) => '<form method="post" class="afrm">' . csrf() . '<input type="hidden" name="id" value="' . $id . '"><input name="note" maxlength="255" placeholder="Note to user (optional)" aria-label="Note to user">' . $btns . '</form>';

if ($tab === 'reports') {
    $st = in_array($s, ['pending', 'approved', 'rejected', 'returned', 'all']) ? $s : 'pending'; $chips(['pending', 'approved', 'rejected', 'returned', 'all'], $st);
    $rows = q('SELECT i.*,u.name uname,u.school_id sid,u.email uemail,(SELECT COUNT(*) FROM claims c WHERE c.item_id=i.id AND c.status="pending") nc FROM items i JOIN users u ON u.id=i.user_id' . ($st !== 'all' ? ' WHERE i.status=?' : '') . ' ORDER BY i.id DESC', $st !== 'all' ? [$st] : [])->fetchAll();
    if (!$rows) echo '<div class="empty">Nothing here.</div>'; else { echo '<div class="grid">';
        foreach ($rows as $k => $it) {
            $a = '<small style="width:100%">' . ($it['type'] === 'lost' ? 'Owner' : 'Finder') . ': ' . e($it['uname']) . ' · ' . e($it['sid']) . ' · ' . e($it['uemail']) . '</small><span class="badge ' . $it['status'] . '">' . $it['status'] . '</span>' . ($it['nc'] ? '<small>' . $it['nc'] . ' pending claim(s)</small>' : '') . ($it['note'] !== '' ? '<span class="note">Note: ' . e($it['note']) . '</span>' : '');
            $b = '';
            if (in_array($it['status'], ['pending', 'rejected'])) $b .= '<button class="btn s ok" name="act" value="item_approve">Approve</button>';
            if (in_array($it['status'], ['pending', 'approved'])) $b .= '<button class="btn s r" name="act" value="item_reject">Reject</button>';
            if ($it['status'] === 'approved') $b .= '<button class="btn s" name="act" value="item_return">Mark returned</button>';
            $a .= $noteform($it['id'], $b) . '<form method="post" onsubmit="return confirm(\'Delete this report permanently?\')">' . csrf() . '<input type="hidden" name="id" value="' . $it['id'] . '"><button class="btn s g" name="act" value="item_delete">Delete</button></form>';
            card($it, $k, $a);
        } echo '</div>'; }
} elseif ($tab === 'claims') {
    $st = in_array($s, ['pending', 'approved', 'rejected', 'all']) ? $s : 'pending'; $chips(['pending', 'approved', 'rejected', 'all'], $st);
    $rows = q('SELECT c.*,i.title,i.type,i.place,i.descr,i.status istatus,u.name uname,u.school_id sid,u.email uemail,u.dept,u.type utype,r.name rname FROM claims c JOIN items i ON i.id=c.item_id JOIN users u ON u.id=c.user_id JOIN users r ON r.id=i.user_id' . ($st !== 'all' ? ' WHERE c.status=?' : '') . ' ORDER BY c.id DESC', $st !== 'all' ? [$st] : [])->fetchAll();
    if (!$rows) echo '<div class="empty">No claims here.</div>'; else {
        echo '<div class="panel w scroll"><table class="tbl"><tr><th>Item</th><th>Claimant</th><th>Proof given</th><th>Decision</th></tr>';
        foreach ($rows as $c) {
            echo '<tr><td><b>' . e($c['title']) . '</b><br><small>' . e($c['type']) . ' · ' . e($c['place']) . ' · ' . e($c['istatus']) . '<br>Reported by ' . e($c['rname']) . '</small><br><small>' . e($c['descr']) . '</small></td>'
                . '<td>' . e($c['uname']) . '<br><small>' . e($c['sid']) . ' · ' . e($c['utype']) . ($c['dept'] !== '' ? ' · ' . e($c['dept']) : '') . '<br>' . e($c['uemail']) . '</small></td>'
                . '<td>' . nl2br(e($c['message'])) . '</td><td><span class="badge ' . $c['status'] . '">' . $c['status'] . '</span>';
            if ($c['note'] !== '') echo '<span class="note">' . e($c['note']) . '</span>';
            if ($c['status'] === 'pending') echo $noteform($c['id'], '<button class="btn s ok" name="act" value="claim_approve">Approve</button><button class="btn s r" name="act" value="claim_reject">Reject</button>');
            echo '</td></tr>';
        } echo '</table></div>'; }
} elseif ($tab === 'members') {
    $rows = q('SELECT u.*,(SELECT COUNT(*) FROM items i WHERE i.user_id=u.id) nr,(SELECT COUNT(*) FROM claims c WHERE c.user_id=u.id) ncl FROM users u WHERE school_id<>"SECURITY" ORDER BY u.id DESC')->fetchAll();
    if (!$rows) echo '<div class="empty">No members yet.</div>'; else {
        echo '<div class="panel w scroll"><table class="tbl"><tr><th>Name</th><th>School ID</th><th>Type</th><th>Dept</th><th>Email</th><th>Reports</th><th>Claims</th><th>Joined</th><th></th></tr>';
        foreach ($rows as $u) echo '<tr><td>' . e($u['name']) . '</td><td>' . e($u['school_id']) . '</td><td>' . e($u['type']) . '</td><td>' . e($u['dept']) . '</td><td>' . e($u['email']) . '</td><td>' . $u['nr'] . '</td><td>' . $u['ncl'] . '</td><td>' . e(substr($u['created_at'], 0, 10)) . '</td><td><form method="post" onsubmit="return confirm(\'Remove this member and all their reports and claims?\')">' . csrf() . '<input type="hidden" name="id" value="' . $u['id'] . '"><button class="btn s r" name="act" value="member_delete">Remove</button></form></td></tr>';
        echo '</table></div>'; }
} else {
    $rows = q('SELECT id,username,name,created_at FROM admins ORDER BY id')->fetchAll();
    echo '<div class="panel w scroll" style="margin-bottom:1rem"><table class="tbl"><tr><th>Username</th><th>Name</th><th>Created</th><th></th></tr>';
    foreach ($rows as $r) echo '<tr><td>' . e($r['username']) . ($r['id'] == $ad['id'] ? ' <small>(you)</small>' : '') . '</td><td>' . e($r['name']) . '</td><td>' . e(substr($r['created_at'], 0, 10)) . '</td><td>' . ($r['id'] == $ad['id'] ? '' : '<form method="post" onsubmit="return confirm(\'Remove this admin?\')">' . csrf() . '<input type="hidden" name="id" value="' . $r['id'] . '"><button class="btn s r" name="act" value="admin_del">Remove</button></form>') . '</td></tr>';
    echo '</table></div>';
    if (password_verify('admin123', q('SELECT pw_hash FROM admins WHERE id=?', [$ad['id']])->fetchColumn())) echo '<p class="err">You are still using the default password. Change it below.</p>';
    ?><div class="two"><form class="panel" method="post"><?= csrf() ?><h3>Add admin</h3><input type="hidden" name="act" value="admin_add">
<label for="au">Username</label><input id="au" name="username" required maxlength="40"><label for="an">Full name</label><input id="an" name="name" required maxlength="60"><label for="ap">Password (8+ characters)</label><input id="ap" name="pw" type="password" minlength="8" required autocomplete="new-password">
<div class="row" style="margin-top:1rem"><button class="btn a">Add admin</button></div></form>
<form class="panel" method="post"><?= csrf() ?><h3>Change my password</h3><input type="hidden" name="act" value="pw_change">
<label for="pc">Current password</label><input id="pc" name="cur" type="password" required autocomplete="current-password"><label for="pn">New password (8+ characters)</label><input id="pn" name="new" type="password" minlength="8" required autocomplete="new-password"><label for="pn2">Confirm new password</label><input id="pn2" name="new2" type="password" required autocomplete="new-password">
<div class="row" style="margin-top:1rem"><button class="btn a">Change password</button></div></form></div><?php
}
admin_foot();
