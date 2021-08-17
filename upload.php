<?php
$flash_id = $_POST['fid'];

require 'StatusChanger.php';
$statusChanger = new StatusChanger();
if (!$statusChanger->updateStatus($flash_id, 'NOT ARCHIVED', 'NOT ARCHIVED')) {
    header("HTTP/1.1 401 Unauthorized");
    die('Valid access code for changing statuses was not found.<br>Return to homepage and fill in the form please.');
}

if ($_FILES['flashFile']['size'] > 12582912) {
    header("HTTP/1.1 413 Request Entity Too Large");
    die("File is too large - 10 MB is the limit, just like on 4chan.");
}
if ($_FILES['flashFile']['type'] !== 'application/x-shockwave-flash') {
    header("HTTP/1.1 415 Unsupported Media Type");
    die("File has invalid format - only .swf file are accepted, just like on 4chan.");
}
if ($_FILES['flashFile']['error'] !== 0) {
    header("HTTP/1.1 500 Internal Server Error");
    die("An unknown error occurred during the file upload - try again or ping @Shady on Discord");
}

require 'DataProcessor.php';
if (file_exists(DataProcessor::DOWNLOADS_FOLDER.'/'.$flash_id.'.swf')) {
    header("HTTP/1.1 409 Conflict");
    die("This post has already file uploaded. If you accidentally uploaded incorrect file, ping @Shady on Discord");
}

move_uploaded_file($_FILES['flashFile']['tmp_name'], 'downloads/'.$flash_id.'.swf');

$statusChanger->updateStatus($flash_id, 'ARCHIVED', 'NOT ARCHIVED');
header("Location: view.php");
