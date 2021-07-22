<?php
$post_id = $_GET['pid'];

require 'StatusChanger.php';

$statusChanger = new StatusChanger();
if (!$statusChanger->updateStatus($post_id, 'CURATED', 'ARCHIVED')) { die('Valid access code for changing statuses was not found.<br>Return to homepage and fill in the form please.'); }
header("Location: view.php");
