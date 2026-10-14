<?php require_once __DIR__ . '/_layout.php'; if (admin_me()) go('index.php'); $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
    if (($_SESSION['lock'] ?? 0) > time()) $err = 'Too many attempts. Try again in a few minutes.';
    else {
        $u = q('SELECT * FROM admins WHERE username=?', [strtolower(trim($_POST['u'] ?? ''))])->fetch();
        if ($u && password_verify($_POST['pw'] ?? '', $u['pw_hash'])) { session_regenerate_id(true); unset($_SESSION['fails'], $_SESSION['lock']); $_SESSION['aid'] = $u['id']; flash('Welcome, ' . $u['name']); go('index.php'); }
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock'] = time() + 300; $_SESSION['fails'] = 0; }
        $err = 'Username or password is incorrect.';
    } }
admin_head('Staff login'); page_head('Staff portal', 'For office staff only.', 'STAFF', 'c'); ?>
<form class="panel login" method="post"><div class="lk">🔐</div><?= csrf() ?><label for="u">Username</label><input id="u" name="u" required autocomplete="username"><label for="p">Password</label><input id="p" name="pw" type="password" required autocomplete="current-password">
<p class="err"><?= e($err) ?></p><div class="row"><button class="btn a">Log in</button><a class="btn g" href="../index.php">Back to site</a></div></form>
<?php admin_foot();
