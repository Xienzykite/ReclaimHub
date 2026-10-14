<?php require_once 'config.php'; if (me()) go('browse.php'); $err = ''; $v = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check();
    $n = trim($v['name']); $em = strtolower(trim($v['email'])); $sid = strtoupper(trim($v['school_id'])); $d = trim($v['dept'] ?? '');
    if ($n === '' || !preg_match('/^[A-Z0-9-]{3,20}$/', $sid) || $sid === 'SECURITY') $err = 'Enter your name and a valid school ID (letters, numbers, dashes).';
    elseif (!isset($TYPES[$v['type'] ?? ''])) $err = 'Choose student, faculty or staff.';
    elseif (!filter_var($em, FILTER_VALIDATE_EMAIL)) $err = 'Enter a valid email.';
    elseif (ALLOWED_EMAIL_DOMAIN !== '' && !str_ends_with($em, '@' . ALLOWED_EMAIL_DOMAIN)) $err = 'Use your campus email (@' . ALLOWED_EMAIL_DOMAIN . ').';
    elseif (strlen($v['pw']) < 6) $err = 'Password needs at least 6 characters.';
    elseif ($v['pw'] !== $v['pw2']) $err = 'Passwords do not match.';
    elseif (q('SELECT 1 FROM users WHERE email=? OR school_id=?', [$em, $sid])->fetch()) $err = 'That email or school ID already has an account. Log in instead.';
    else { q('INSERT INTO users (school_id,name,email,pw_hash,type,dept) VALUES (?,?,?,?,?,?)', [$sid, $n, $em, password_hash($v['pw'], PASSWORD_DEFAULT), $v['type'], $d]);
        session_regenerate_id(true); $_SESSION['uid'] = $db->lastInsertId(); flash('Account created'); go('report.php'); } }
require 'header.php'; page_head('Create account', 'For students, faculty and staff of the campus.', 'JOIN', 'c'); ?>
<form class="panel" method="post"><?= csrf() ?>
<label for="n">Full name</label><input id="n" name="name" required maxlength="60" value="<?= e($v['name'] ?? '') ?>" autocomplete="name">
<label for="s">School ID</label><input id="s" name="school_id" required maxlength="20" value="<?= e($v['school_id'] ?? '') ?>">
<label for="t">I am a</label><select id="t" name="type"><?php foreach ($TYPES as $k => $l) echo '<option value="' . $k . '" ' . (($v['type'] ?? '') === $k ? 'selected' : '') . '>' . $l . '</option>'; ?></select>
<label for="d">Course or department</label><input id="d" name="dept" maxlength="80" value="<?= e($v['dept'] ?? '') ?>" placeholder="e.g. BSIT, Engineering Dept.">
<label for="e">Email</label><input id="e" name="email" type="email" required value="<?= e($v['email'] ?? '') ?>" autocomplete="email">
<label for="p">Password (6+ characters)</label><input id="p" name="pw" type="password" minlength="6" required autocomplete="new-password">
<label for="p2">Confirm password</label><input id="p2" name="pw2" type="password" required autocomplete="new-password">
<p class="err"><?= e($err) ?></p><div class="row"><button class="btn a">Create account</button><a class="btn g" href="login.php">I have an account</a></div></form>
<?php require 'footer.php';
