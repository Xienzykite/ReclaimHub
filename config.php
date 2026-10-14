<?php
// PIT Finds config. Edit the DB_* values, then open index.php. Tables and the first admin are created on first run.
const APP_NAME = 'PIT Finds', DB_HOST = 'localhost', DB_NAME = 'pitfinds', DB_USER = 'root', DB_PASS = '';
const PICKUP_POINT = 'the Security Office';   // where approved items are released
const ALLOWED_EMAIL_DOMAIN = '';              // e.g. 'pit.edu.ph' to accept only campus emails; '' accepts any
define('BASE', str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') ? '../' : '');
ob_start(); // buffer output so redirects work after header.php has printed
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
if (!is_dir(__DIR__ . '/uploads')) @mkdir(__DIR__ . '/uploads', 0755, true);
if (!file_exists(__DIR__ . '/uploads/.htaccess')) @file_put_contents(__DIR__ . '/uploads/.htaccess', "<FilesMatch \"\\.(php|phtml|phar)$\">\nRequire all denied\n</FilesMatch>\n");
$opts = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$install = !file_exists(__DIR__ . '/.installed');
try {
    if ($install) {
        $p = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
        $p->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4');
    }
    $db = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, $opts);
} catch (PDOException $x) {
    http_response_code(500);
    exit('Database connection failed. Check the DB_* settings in config.php.');
}
if ($install) {
    $db->exec('CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, school_id VARCHAR(20) NOT NULL UNIQUE, name VARCHAR(60) NOT NULL, email VARCHAR(120) NOT NULL UNIQUE, pw_hash VARCHAR(255) NOT NULL, type ENUM("student","faculty","staff") NOT NULL DEFAULT "student", dept VARCHAR(80) NOT NULL DEFAULT "", created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('CREATE TABLE IF NOT EXISTS admins (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(40) NOT NULL UNIQUE, name VARCHAR(60) NOT NULL, pw_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
    $db->exec('CREATE TABLE IF NOT EXISTS items (id INT AUTO_INCREMENT PRIMARY KEY, type ENUM("lost","found") NOT NULL, title VARCHAR(80) NOT NULL, cat VARCHAR(30) NOT NULL, place VARCHAR(160) NOT NULL, seen_on DATE NOT NULL, descr TEXT NOT NULL, image VARCHAR(60) NULL, user_id INT NOT NULL, status ENUM("pending","approved","rejected","returned") NOT NULL DEFAULT "pending", note VARCHAR(255) NOT NULL DEFAULT "", created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(status,id), INDEX(user_id), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)');
    $db->exec('CREATE TABLE IF NOT EXISTS claims (id INT AUTO_INCREMENT PRIMARY KEY, item_id INT NOT NULL, user_id INT NOT NULL, message TEXT NOT NULL, status ENUM("pending","approved","rejected") NOT NULL DEFAULT "pending", note VARCHAR(255) NOT NULL DEFAULT "", created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(item_id,status), INDEX(user_id), FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)');
    $db->prepare('INSERT IGNORE INTO admins (username,name,pw_hash) VALUES ("admin","Campus Admin",?)')->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
    // Sample listings are owned by an unusable "Security Office" account (nobody can log in as it).
    $db->prepare('INSERT IGNORE INTO users (school_id,name,email,pw_hash,type,dept) VALUES ("SECURITY","Security Office","security@pitfinds.local",?,"staff","Security")')->execute([password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT)]);
    $sid = $db->query('SELECT id FROM users WHERE school_id="SECURITY"')->fetchColumn();
    if (!$db->query('SELECT 1 FROM items LIMIT 1')->fetch()) foreach ([['found', 'Black leather wallet', 'Wallet & cards', 'Library — 2nd floor'], ['lost', 'Silver keys with blue tag', 'Keys', 'Main Gate'], ['found', 'Grey backpack', 'Bags', 'Cafeteria']] as $s) {
        $db->prepare('INSERT INTO items (type,title,cat,place,seen_on,descr,user_id,status) VALUES (?,?,?,?,CURDATE(),"Sample listing showing how reports look.",?,"approved")')->execute([...$s, $sid]);
    }
    @touch(__DIR__ . '/.installed');
}

// Upgrade older installs: add the admin 'note' column (message from office to user) once per session.
if (empty($_SESSION['mig'])) {
    try {
        foreach (['items', 'claims'] as $tb) if (!$db->query("SHOW COLUMNS FROM `$tb` LIKE 'note'")->fetch()) $db->exec("ALTER TABLE `$tb` ADD `note` VARCHAR(255) NOT NULL DEFAULT ''");
        $_SESSION['mig'] = 1;
    } catch (PDOException $x) {}
}

$CATS = ['Gadgets' => '📱', 'Wallet & cards' => '💳', 'Keys' => '🔑', 'Bags' => '🎒', 'Clothing' => '🧥', 'Jewelry' => '💍', 'ID & documents' => '🪪', 'Books & supplies' => '📚', 'Other' => '📦'];
$LOCS = ['Main Gate', 'Learning Resource Center', 'Canteen', 'Gymnasium', 'Administration Building', 'COMED Building', 'COTE Building', 'CAS Building', 'ICT Building', 'Student Center', 'CTE Building', 'CGS Building', 'LHS Building', 'Covered Court', 'Parking Area', 'Restrooms', 'Chapel', 'Mini Forest', 'Oval', 'Other Places',];
$TYPES = ['student' => 'Student', 'faculty' => 'Faculty', 'staff' => 'Staff'];

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function q($sql, $a = []) { global $db; $s = $db->prepare($sql); $s->execute($a); return $s; }
function go($url) { header('Location: ' . $url); exit; }
function flash($m = null) { if ($m !== null) { $_SESSION['flash'] = $m; return null; } $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function me() {
    static $u = false;
    if ($u === false) { $u = null; if (!empty($_SESSION['uid'])) { $u = q('SELECT id,school_id,name,email,type,dept FROM users WHERE id=?', [$_SESSION['uid']])->fetch() ?: null; } }
    return $u;
}
function admin_me() {
    static $a = false;
    if ($a === false) { $a = null; if (!empty($_SESSION['aid'])) { $a = q('SELECT id,username,name FROM admins WHERE id=?', [$_SESSION['aid']])->fetch() ?: null; } }
    return $a;
}
function need_login() { if (!me()) { flash('Log in first'); go(BASE . 'login.php'); } }
function need_admin() { if (!admin_me()) { flash('Admin login required'); go(BASE ? 'login.php' : 'admin/login.php'); } }
function csrf() { if (empty($_SESSION['t'])) $_SESSION['t'] = bin2hex(random_bytes(16)); return '<input type="hidden" name="_t" value="' . $_SESSION['t'] . '">'; }
function check() { if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['t'] ?? '', $_POST['_t'] ?? '')) { http_response_code(400); exit('Invalid request'); } }
function page_head($t, $s, $l = 'LOST', $c = '') {
    echo '<div class="phead"><div class="swing"><i></i><div class="tg ' . $c . '">' . e($l) . '</div></div><div><h1>' . e($t) . '</h1><p>' . e($s) . '</p></div></div>';
}
function card($it, $i, $acts = '') {
    global $CATS;
    $bg = $it['image'] ? "background-image:url('" . BASE . "uploads/" . e($it['image']) . "')" : '';
    echo '<div class="card" style="--i:' . $i . '"><div class="im" style="' . $bg . '">' . ($it['image'] ? '' : ($CATS[$it['cat']] ?? '📦')) . '</div><div class="bd"><span class="badge ' . $it['type'] . '">' . $it['type'] . '</span><h3>' . e($it['title']) . '</h3><small>' . e($it['cat']) . ' · ' . e($it['place']) . ' · ' . e($it['seen_on']) . '</small><p>' . e($it['descr']) . '</p><div class="row" style="margin-top:auto">' . $acts . '</div></div></div>';
}
