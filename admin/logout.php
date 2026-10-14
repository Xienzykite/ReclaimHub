<?php require_once __DIR__ . '/../config.php'; unset($_SESSION['aid']); flash('Logged out'); go('login.php');
