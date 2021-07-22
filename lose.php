<?php
$post_id = $_GET['pid'];

require 'StatusChanger.php';

$statusChanger = new StatusChanger();
if (!$statusChanger->updateStatus($post_id, 'LOST', 'NOT ARCHIVED')) { die('Valid access code for changing statuses was not found.<br>Return to homepage and fill in the form please.'); }

unlink('metadata/'.$post_id.'.txt');

header("Location: view.php");
