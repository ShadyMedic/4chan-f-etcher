<?php

$post_id = $_POST['pid'];

if ($_FILES['flashFile']['size'] > 12582912) { die("File is too large - 10 MB is the limit, just like on 4chan."); }
if ($_FILES['flashFile']['type'] !== 'application/x-shockwave-flash') { die("File has invalid format - only .swf file are accepted, just like on 4chan."); }
if ($_FILES['flashFile']['error'] !== 0) { die("An unknown error occurred during the file upload - try again or ping @Shady on Discord"); }

if (file_exists('downloads/'.$post_id)) { die("This post has already file uploaded. If you accidentally uploaded incorrect file, ping @Shady on Discord"); }

move_uploaded_file($_FILES['flashFile']['tmp_name'], 'downloads/'.$post_id.'.swf');

require 'StatusChanger.php';

$statusChanger = new StatusChanger();
$statusChanger->updateStatus($post_id, 'ARCHIVED', 'NOT ARCHIVED');

header("Location: view.php");
