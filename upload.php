<?php
$flashId = $_POST['fid'];

require 'StatusChanger.php';
$statusChanger = new StatusChanger();
if (!$statusChanger->updateStatus($flashId, 'NOT ARCHIVED', 'NOT ARCHIVED')) {
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
if (file_exists(DataProcessor::DOWNLOADS_FOLDER.'/'.$flashId.'.swf')) {
    header("HTTP/1.1 409 Conflict");
    die("This post has already file uploaded. If you accidentally uploaded incorrect file, ping @Shady on Discord");
}

move_uploaded_file($_FILES['flashFile']['tmp_name'], DataProcessor::DOWNLOADS_FOLDER.DIRECTORY_SEPARATOR.'currentUpload.swf');

//Check hash
$db = Db::connect();
$hash = hash_file("sha256", DataProcessor::DOWNLOADS_FOLDER.DIRECTORY_SEPARATOR.'currentUpload.swf');
$statement = $db->prepare('SELECT flash_id FROM flashes WHERE hash = ?');
$statement->execute(array($hash));
$queryResult = $statement->fetch();
if ($queryResult && !empty($queryResult['flash_id'])) {
	//Uploaded flash is a duplicate
	$originalFlashId = $queryResult['flash_id'];
	unlink(DataProcessor::DOWNLOADS_FOLDER.DIRECTORY_SEPARATOR.'currentUpload.swf');
	file_put_contents(DataProcessor::META_FOLDER.DIRECTORY_SEPARATOR.$originalFlashId.'.txt',
		'
[THE SAME FILE WAS POSTED WITH THE FOLLOWING METADATA AGAIN]

'.file_get_contents(DataProcessor::META_FOLDER.DIRECTORY_SEPARATOR.$flashId.'.txt'), FILE_APPEND);
	$statement = $db->prepare('UPDATE posts SET flash_id = ? WHERE flash_id = ?');
	$statement->execute(array($originalFlashId, $flashId));
	$statusChanger->updateStatus($flashId, 'LOST', 'NOT ARCHIVED');
}
else {
	rename(DataProcessor::DOWNLOADS_FOLDER.DIRECTORY_SEPARATOR.'currentUpload.swf', DataProcessor::DOWNLOADS_FOLDER.DIRECTORY_SEPARATOR.$flashId.'.swf');
	$statement = $db->prepare('UPDATE flashes SET hash = ? WHERE flash_id = ?');
	$statement->execute(array($hash, $flashId));
	$statusChanger->updateStatus($flashId, 'ARCHIVED', 'NOT ARCHIVED');
}

header("Location: view.php");
