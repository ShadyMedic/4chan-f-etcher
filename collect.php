<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>4chan /f/ scraper</title>
</head>
<body>
<h2 style="text-align: center"><a href="../">Back to Homepage</a></h2>
<?php
    require 'Controller.php';

    $controller = new Controller();
    $controller->process();
?>
</body>
</html>
