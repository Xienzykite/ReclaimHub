<?php require 'header.php'; need_login(); $err = ''; $f = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check(); $f = $_POST; $img = null;
    $title = trim($f['title'] ?? ''); $descr = trim($f['descr'] ?? ''); $d = $f['seen_on'] ?? ''; $dd = DateTime::createFromFormat('Y-m-d', $d);
    if (!in_array($f['type'] ?? '', ['lost', 'found']) || !isset($CATS[$f['cat'] ?? '']) || !in_array($f['loc'] ?? '', $LOCS)) $err = 'Choose a valid type, category and campus location.';
    elseif ($title === '' || $descr === '') $err = 'Item name and description are required.';
    elseif (!$dd || $dd->format('Y-m-d') !== $d || $d > date('Y-m-d')) $err = 'Enter a valid date (not in the future).';
    if (!$err && !empty($_FILES['photo']['tmp_name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $info = @getimagesize($_FILES['photo']['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? null;
        if (!$ext || $_FILES['photo']['size'] > 4000000) $err = 'Photo must be a JPG, PNG, WebP or GIF under 4 MB.';
        else { $img = bin2hex(random_bytes(8)) . '.' . $ext; if (!move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/uploads/' . $img)) { $img = null; $err = 'Could not save the photo. Is uploads/ writable?'; } }
    }
    if (!$err) {
        $spot = trim($f['spot'] ?? ''); $place = $f['loc'] . ($spot !== '' ? ' — ' . $spot : '');
        q('INSERT INTO items (type,title,cat,place,seen_on,descr,image,user_id) VALUES (?,?,?,?,?,?,?,?)', [$f['type'], $title, $f['cat'], $place, $d, $descr, $img, $me['id']]);
        flash('Report sent to the office for review'); go('mine.php');
    }
}
$t = ($_GET['type'] ?? $_POST['type'] ?? 'lost') === 'found' ? 'found' : 'lost';
page_head('Report an item', 'The office reviews every report before it is listed.', 'NEW', 'c'); ?>
<form class="panel" method="post" enctype="multipart/form-data"><?= csrf() ?>
<div class="seg"><label><input type="radio" name="type" value="lost" <?= $t === 'lost' ? 'checked' : '' ?>><span>I lost something</span></label><label><input type="radio" name="type" value="found" <?= $t === 'found' ? 'checked' : '' ?>><span>I found something</span></label></div>
<p style="color:var(--mut);font-size:.9rem;margin:.7rem 0 0">Found something? Please also hand it in to <?= e(PICKUP_POINT) ?>.</p>
<label for="ti">Item name</label><input id="ti" name="title" required maxlength="80" placeholder="Black leather wallet" value="<?= e($f['title'] ?? '') ?>">
<label for="ca">Category</label><select id="ca" name="cat"><?php foreach ($CATS as $k => $_) echo '<option>' . e($k) . '</option>'; ?></select>
<label for="lo">Campus location</label><select id="lo" name="loc"><?php foreach ($LOCS as $k) echo '<option>' . e($k) . '</option>'; ?></select>
<label for="sp">Exact spot (optional)</label><input id="sp" name="spot" maxlength="80" placeholder="Room number, floor, bench, table" value="<?= e($f['spot'] ?? '') ?>">
<label for="da">Date</label><input id="da" name="seen_on" type="date" required value="<?= e($f['seen_on'] ?? date('Y-m-d')) ?>" max="<?= date('Y-m-d') ?>">
<label for="de">Description</label><textarea id="de" name="descr" required placeholder="Color, brand, marks, contents (keep one detail private to prove ownership)"><?= e($f['descr'] ?? '') ?></textarea>
<label for="ph">Photo (optional)</label><input id="ph" name="photo" type="file" accept="image/*">
<p class="err"><?= e($err) ?></p><div class="row"><button class="btn a">Send report</button></div></form>
<?php require 'footer.php';
