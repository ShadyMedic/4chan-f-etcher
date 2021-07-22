<?php
if (!empty($_POST) && !empty($_POST['accesscode'])) {
    setcookie('accesscode', $_POST['accesscode'], time() + 2592000, '/'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>4chan /f/ scraper</title>
    <style>
        table {
            margin: auto;
        }

        td {
            text-align: center;
            vertical-align: middle;
        }

        .headline {
            width: 50vw;
            font-size: 48px;
        }
    </style>
</head>
<body>
<table>
    <tr style="height: 50vh">
        <td class="headline">
            <a href="view.php">View and download<br>collected files</a>
            <br>
            <small style="font-size: small">Might take a while, if there are many flashes saved</small>
        </td>
        <td class="headline">
            <a href="collect.php">Save new files<br>from 4chan.org/f/</a>
            <br>
            <small style="font-size: small">This might take up to a minute or two</small>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <form method="POST">
                <label style="font-size: medium">
                    To be able to change statuses of entries, you need to enter an accesscode.<br>
                    You can find it on our Discord server in #archivist-lounge - just search for "4csc accesscode" in
                    that channel.<br>
                    This will set a cookie in your browser, that'll be used to verify that you have permission to do the
                    changes.<br>
                    If you don't have the code, you can still download anything, you just can't change the statuses.
                </label>
                <br>
                <input type="password" name="accesscode" id="accesscode" maxlength="31"/>
                <input type="submit"/>
            </form>
        </td>
    </tr>
</table>
</body>
</html>
