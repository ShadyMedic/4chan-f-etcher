<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>4chan /f/ scraper</title>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="view.js"></script>
    <link rel="stylesheet" type="text/css" href="view.css">
</head>
<body>
<h2 style="text-align: center"><a href="../">Back to Homepage</a></h2>
<fieldset>
    <strong style="color: #FFBB00">WARNING: After clicking "Mark as curated" or "Mark as uncuratable" , you can't change
        the status anymore.</strong>
    <br>
    <span style="color: #FFBB00">In case you made unwanted changes and can't revert them, ping @Shady on Discord (or DM him)</span>
</fieldset>
<table border="1">
    <tr>
        <th>Post ID</th>
        <th>Subject</th>
        <th>File Name</th>
        <th>Size [KB]</th>
        <th>Category</th>
        <th>Author</th>
        <th>Source</th>
        <th>Time Posted</th>
        <th colspan="3"></th>
        <th>Status</th>
    </tr>
    <?php
    require 'TableGenerator.php';
    $table = (new TableGenerator())->generate();
    foreach ($table as $row) {
        echo '<tr>';
        echo '<td>'.$row['post_id'].'</td>';
        echo '<td>'.$row['subject'].'</td>';
        echo '<td style="max-width: 15vw;">'.$row['filename'].'</td>';
        echo '<td style="text-align: right">'.$row['size'].'</td>';
        echo '<td style="text-align: center">'.$row['category'].'</td>';
        echo '<td>'.$row['author'].'</td>';
        echo '<td style="max-width: 10vw; word-wrap: normal; white-space: nowrap;">'.$row['source'].'</td>';
        echo '<td>'.$row['time_posted'].'</td>';
        
        //First button (download)
        if ($row['status'] !== "NOT ARCHIVED" && $row['status'] !== "FLASHFREEZED" && $row['status'] !== "WEBARCHIVED") {
            echo '<td class="actions">'.'<a href="downloads/'.$row['flash_id'].
                 '.swf" class="swfDownloadLink" download><button style="background-color: #99FFFF" title="Downloads the SWF file" onclick="swapDownloadLink(event)">Download SWF</button></a>'.
                 '<a href="metadata/'.$row['flash_id'].
                 '.txt" class="metaDownloadLink" style="display:none" download><button style="background-color: #d599ff" title="Downloads the SWF file" onclick="swapDownloadLink(event)">Download Metadata</button></a>'.
                 '</td>';
        } else {
            if ($row['status'] === "FLASHFREEZED") {
                echo '<td class="actions" colspan="3"><a href="'.$row['download_link'].
                     '" target="_blank"><button style="background-color: #5890ff" title="Downloads an archive containing this SWF file along with many other flashfreezed SWFs">Download Flashfreezed Batch</button></a></td>';
            } else if ($row['status'] === "WEBARCHIVED") {
                echo '<td class="actions" colspan="3"><a href="'.$row['download_link'].
                     '" target="_blank"><button style="background-color: #ddaaff" title="Downloads an archive containing this SWF file along with many other uncuratable SWFs">Download From Web Archive</button></a></td>';
            } else {
                echo '<td class="actions"><a href="'.$row['download_link'].
                     '"><button style="background-color: #999999" title="Downloads the SWF file from 4chan">Get from 4chan</button></a></td>';
            }
        }
        
        //Second button (uncuratable/upload)
        if ($row['status'] === "ARCHIVED") {
            echo '<td class="actions"><a href="uncuratable.php?fid='.$row['flash_id'].
                 '"><button style="background-color: #FFFF99" title="Marks this curation as uncuratable because it violates one of Flashpoint rules (usually because it\'s a video embeded in SWF)">Mark as uncuratable</button></a></td>';
        } else {
            if ($row['status'] === "NOT ARCHIVED") {
                echo '
                    <td class="actions">
                        <form action="upload.php" method="POST" enctype="multipart/form-data">
                            <input name="fid" type="hidden" value="'.$row['flash_id'].'"/>
                            <input name="flashFile" type="file" accept=".swf"/>
                            <input type="submit"/>
                        </form>
                    </td>';
            } else {
                if ($row['status'] !== "FLASHFREEZED" && $row['status'] !== "WEBARCHIVED") {
                    echo '<td></td>';
                }
            }
        }
        
        //Third button (curated/lost)
        if ($row['status'] === "ARCHIVED") {
            echo '<td class="actions"><a href="curate.php?fid='.$row['flash_id'].
                 '"><button style="background-color: #99FF99" title="Marks this curation as curated">Mark as curated</button></a></td>';
        } else {
            if ($row['status'] === "NOT ARCHIVED") {
                echo '<td class="actions"><a href="lose.php?fid='.$row['flash_id'].
                     '"><button style="background-color: #992222; color: #FFFFFF" title="Marks this file as lost and deletes its metadata">Mark as slipped</button></a></td>';
            } else {
                if ($row['status'] !== "FLASHFREEZED" && $row['status'] !== "WEBARCHIVED") {
                    echo '<td></td>';
                }
            }
        }
        
        switch ($row['status']) {
            case 'NOT ARCHIVED':
                $color = "#BB4444";
                $lore = "Only metadata of this file has been saved, not the file itself. Please, download it manually and upload it.";
                break;
            case 'ARCHIVED':
                $color = "#FF9999";
                $lore = "This file has been archived in this system, but not in Flashpoint.";
                break;
            case 'UNCURATABLE':
                $color = "#FFFF99";
                $lore = "Somebody marked this file as uncuratable, because it violates Flashpoint rules. You can still download it, but don't try to curate it.";
                break;
            case 'FLASHFREEZED':
                $color = "#99FFFF";
                $lore = "This file has been uploaded in Flashfreeze. If you want to curate it, please, download the whole batch.";
                break;
            case 'WEBARCHIVED':
                $color = "#DDAAFF";
                $lore = "This file has been uploaded to Web Archive. It's marked as uncuratable, because it violates Flashpoint rules. If you want to get it, you need to download the whole batch.";
                break;
            case 'CURATED':
                $color = "#99FF99";
                $lore = "Somebody marked this file curated and probably uploaded it to one of the curation channels.";
                break;
        }
        echo '<td style="text-align: center; background-color: '.$color.'" title="'.$lore.'">'.$row['status'].'</td>';
        
        echo '</tr>';
    }
    ?>
</table>
</body>
</html>
