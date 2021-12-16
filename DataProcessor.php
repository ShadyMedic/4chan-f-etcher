<?php

/**
 * Class taking care of data extracted from the HTML table from 4chan.org/f, downloading SWF files and saving
 * everything in the database and file system
 */
class DataProcessor
{
    public const DOWNLOADS_FOLDER = 'downloads';
    public const META_FOLDER = 'metadata';
    
    private $db;
    
    /**
     * Create the object and connect to the database
     * DataProcessor constructor
     */
    public function __construct()
    {
        require 'Db.php';
        
        //Connect to the database
        $this->db = Db::connect();
    }
    
    /**
     * Process the extracted data and save the new entries in the database
     */
    public function process(array $data): void
    {
        $allParsedData = array();
        
        foreach ($data as $entry) {
            $parsedData = array();
            
            $parsedData['post_id'] = $entry['no.'];
            $parsedData['time_posted'] = (DateTime::createFromFormat('m/d/y(D)H:i:s',
                trim($entry['date'])))->format('Y-m-d H:i:s');
            $parsedData['author'] = $entry['name'];
            $parsedData['subject'] = $entry['subject'];
            $parsedData['category'] = ($entry['tag'] === '[H]') ? 'Hentai' : (($entry['tag'] === '[P]') ? 'Porn' :
                (($entry['tag'] === '[J]') ? 'Japanese' : (($entry['tag'] === '[A]') ? 'Anime' :
                    (($entry['tag'] === '[G]') ? 'Game' : (($entry['tag'] === '[L]') ? 'Loop' : 'Other')))));
            $parsedData['filename'] = $entry['file'];
            $parsedData['size'] = round((strpos($entry['size'], 'KB') !== false) ? explode(' ', $entry['size'])[0] :
                explode(' ', $entry['size'])[0] * 1000);
            $parsedData['download'] = 'http://'.$entry['download'];
            $parsedData['source'] = 'https://boards.4chan.org/f/'.urldecode($entry['source']);
            
            $allParsedData[] = $parsedData;
        }
        
        foreach ($allParsedData as $entry) {
            ob_start();
            
            echo '<div style="border: 1px solid black; background-color: #FFFFFF;">'; //Color will be replaced before ob gets outputted
            
            //Check if the post isn't already saved
            $id = $entry['post_id'];
            $statement = $this->db->prepare('SELECT COUNT(*) as "cnt" FROM posts WHERE post_id = ?');
            $statement->execute(array($id));
            if ($statement->fetch()['cnt'] !== 0) {
                //Entry already saved
                echo '<div>Post ID '.$entry['post_id'].' is already saved</div>';
                $color = "#BBFFFF";
                $this->finishPostOutput($color);
                continue;
            }
            
            echo '<div>Downloading SWF file for post ID '.$entry['post_id'].'</div>';
            /*
            echo '<p>'.utf8_encode($entry['download']).'</p>';
            echo '<p>'.utf8_decode($entry['download']).'</p>';
            echo '<p>'.urlencode($entry['download']).'</p>';
            echo '<p>'.urldecode($entry['download']).'</p>';
            */
            /*
            $ch = curl_init($entry['download']);
            $fp = fopen(self::DOWNLOADS_FOLDER.'/'.$fileName, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $downloadResult = curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            */
            
            error_reporting(E_ALL & ~E_WARNING);
            
            if (!file_put_contents(self::DOWNLOADS_FOLDER.'/currentDownload.swf',
                file_get_contents($entry['download']))) {
                echo '<div>File download failed. Try downloading it manually from <a href="'.$entry['download'].'">'.
                     $entry['download'].'</a></div>';
                unlink(self::DOWNLOADS_FOLDER.'/currentDownload.swf');
                $status = "NOT ARCHIVED";
                $downloadLink = $entry['download'];
                $color = "#FF9999";
                
                $this->savePostMetadata($entry, $this->saveFlashMetadata($entry['size'], null, $status, $downloadLink));
                echo 'Saved data for post ID '.$id.'<br>';
                $this->finishPostOutput($color);
                
                error_reporting(E_ALL);
                continue;
            }
            error_reporting(E_ALL);
            
            //Downloaded successfully
            echo '<div>Download successful</div>';
            
            //Check hash
            $hash = hash_file("sha256", self::DOWNLOADS_FOLDER.'/currentDownload.swf');
            $statement = $this->db->prepare('SELECT flash_id FROM flashes WHERE hash = ?');
            $statement->execute(array($hash));
            $queryResult = $statement->fetch();
            if ($queryResult && !empty($queryResult['flash_id'])) {
                //Flash is a duplicate
                $flashId = $queryResult['flash_id'];
                echo '<div>Flash posed in post ID '.$entry['post_id'].' is a duplicate of Flash ID '.
                     $flashId.'</div>';
                unlink(self::DOWNLOADS_FOLDER.'/currentDownload.swf');
                $color = "#FFBBFF";
                $this->savePostMetadata($entry, $flashId);
                $this->finishPostOutput($color);
                continue;
            }
    
            $statement = $this->db->prepare('SELECT flash_id FROM flashes ORDER BY flash_id DESC LIMIT 1');
            $statement->execute(array());
            $newId = $statement->fetch()['flash_id'] + 1;
            
            //Flash is unique
            rename(self::DOWNLOADS_FOLDER.'/currentDownload.swf',
                self::DOWNLOADS_FOLDER.'/'.$newId.'.swf');
            $status = "ARCHIVED";
            $downloadLink = null; //Not needed
            $color = "#99FF99";
            $this->savePostMetadata($entry, $this->saveFlashMetadata($entry['size'], $hash, $status, $downloadLink));
            $this->finishPostOutput($color);
        }
    }
    
    /**
     * Call this right before starting to process another post in the process method
     * Closes the output buffer
     */
    private function finishPostOutput($color)
    {
        echo '</div>';
        
        $output = str_replace('#FFFFFF', $color, ob_get_contents());
        ob_end_clean();
        echo $output;
    }
    
    /**
     * Saves the metadata of the post into database and into the textfile
     */
    private function savePostMetadata($postInfo, $flashId)
    {
        //Save metadata of the post to the database
        $statement = $this->db->prepare("INSERT INTO posts (post_id, time_posted, author, subject, category, filename, flash_id, source) VALUES (?,?,?,?,?,?,?,?)");
        $statement->execute(array(
            $postInfo['post_id'],
            $postInfo['time_posted'],
            $postInfo['author'],
            $postInfo['subject'],
            $postInfo['category'],
            $postInfo['filename'],
            $flashId,
            $postInfo['source']
        ));
        
        //Save metadata file
        $metadata = 'Subject: '.$postInfo['subject'].'
Original filename: '.$postInfo['filename'].'
Category: '.$postInfo['category'].'
Author: '.$postInfo['author'].'
Source: '.$postInfo['source'].'
Time posted: '.$postInfo['time_posted'].'
';
        if (file_exists(self::META_FOLDER.'/'.$flashId.'.txt')) {
            //Append new set of metadata to an existing one
            $metadata = '
[THE SAME FILE WAS POSTED WITH THE FOLLOWING METADATA AGAIN]

'.$metadata;
            file_put_contents(self::META_FOLDER.'/'.$flashId.'.txt', $metadata, FILE_APPEND);
        } else {
            //Create new metadata file
            file_put_contents(self::META_FOLDER.'/'.$flashId.'.txt', $metadata);
        }
    }
    
    /**
     * Saves metadata of the flash file into the database
     * @return int ID of the insterted record
     */
    private function saveFlashMetadata($size, $hash, $status, $download_link)
    {
        //Save metadata of the flash to the database
        $statement = $this->db->prepare("INSERT INTO flashes (size, hash, status, download_link) VALUES (?,?,?,?)");
        $statement->execute(array(
            $size,
            $hash,
            $status,
            $download_link
        ));
        
        return $this->db->lastInsertId();
    }
}
