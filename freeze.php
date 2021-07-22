<?php
$killswitch = 'on';
define('ADMIN_CODE', 'qpvdd5iw7zxy5jo54sw6xh11iif56r2');

if ($killswitch !== 'on') {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    die();
}

if ($_COOKIE['accesscode'] !== ADMIN_CODE) {
    header($_SERVER["SERVER_PROTOCOL"]." 403 Forbidden");
    die();
}

set_time_limit(0);

//Composer autoloader
require __DIR__.'/vendor/autoload.php';

require 'Flashfreezer.php';
$freezer = new Flashfreezer();
if ($freezer->freeze()) {
    echo "<p style='background-color: #99FF99; border: 1px solid black'>All flashes with the \"ARCHIVED\" status vere compressed into a <i>.7z</i> file and uploaded to flashfreeze.submissions.unstable.life. Upload was successful and files were deleted from the scraping server. Statuses and download links of all affected files have been updated.</p>";
} else {
    echo "<p style='background-color: #FF9999; border: 1px solid black'>Couldn't create a valid <i>.7z</i> archive. Try again later.</p>";
}
