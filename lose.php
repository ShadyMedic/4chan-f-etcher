<?php
$flash_id = $_GET['fid'];

require 'StatusChanger.php';

$statusChanger = new StatusChanger();
if (!$statusChanger->updateStatus($flash_id, 'LOST', 'NOT ARCHIVED')) {
    header("HTTP/1.1 401 Unauthorized");
    die('Valid access code for changing statuses was not found.<br>Return to homepage and fill in the form please.');
}

unlink('metadata/'.$post_id.'.txt');

header("Location: view.php");
