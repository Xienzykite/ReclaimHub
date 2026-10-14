<?php require_once 'config.php'; unset($_SESSION['uid']); flash('Logged out'); go('index.php');
