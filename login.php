<?php require_once 'config.php'; if (me()) go('browse.php'); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check(); $id = trim($_POST['id']);
    $u = q('SELECT * FROM users WHERE (email=? OR school_id=?) AND school_id<>"SECURITY"', [strtolower($id), $id])->fetch();
    if ($u && password_verify($_POST['pw'], $u['pw_hash'])) { session_regenerate_id(true); $_SESSION['uid'] = $u['id']; flash('Welcome back, ' . $u['name']); go('browse.php'); }
    $err = 'School ID/email or password is incorrect.'; }
require 'header.php'; page_head('Log in', 'Use your school ID or email.', 'HELLO'); ?>
<form class="panel" method="post"><?= csrf() ?><label for="i">School ID or email</label><input id="i" name="id" required autocomplete="username"><label for="p">Password</label><input id="p" name="pw" type="password" required autocomplete="current-password">
<p class="err"><?= e($err) ?></p><div class="row"><button class="btn a">Log in</button><a class="btn g" href="register.php">Create account</a></div>
<p style="color:var(--mut);font-size:.88rem;margin-top:1rem">Office staff: use the <a href="admin/login.php">staff portal</a>.</p></form>
<?php require 'footer.php';
