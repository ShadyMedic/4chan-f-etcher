<?php


class DataProcessor
{
    private const DOWNLOADS_FOLDER = 'downloads';
    private const META_FOLDER = 'metadata';
    
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
            $parsedData['download'] = 'http://'.urldecode($entry['download']);
            $parsedData['source'] = 'https://boards.4chan.org/f/'.urldecode($entry['source']);
            
            $allParsedData[] = $parsedData;
        }
        
        foreach ($allParsedData as $entry) {
            ob_start();
            
            echo '<div style="border: 1px solid black; background-color: #FFFFFF;">'; //Color will be replaced before ob gets outputted
            
            $id = $entry['post_id'];
            $statement = $this->db->prepare('SELECT COUNT(*) as "cnt" FROM flashes WHERE post_id = ?');
            $statement->execute(array($id));
            if ($statement->fetch()['cnt'] !== 0) {
                //Entry already saved
                echo '<div>Post ID '.$entry['post_id'].' is already saved</div>';
                $color = "#BBFFFF";
            } else {
                $fileName = $entry['post_id'].'.swf';
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
                
                if (file_put_contents(self::DOWNLOADS_FOLDER.'/'.$fileName,
                    file_get_contents($entry['download']))) {
                    //if ($downloadResult && filesize(self::DOWNLOADS_FOLDER.'/'.$fileName) > 0) {
                    echo '<div>Download successful</div>';
                    $status = "ARCHIVED";
                    $downloadLink = null; //Not needed
                    $color = "#99FF99";
                } else {
                    echo '<div>File download failed. Try downloading it manually from <a href="'.$entry['download'].
                         '">'.$entry['download'].'</a></div>';
                    unlink(self::DOWNLOADS_FOLDER.'/'.urlencode($fileName));
                    $status = "NOT ARCHIVED";
                    $downloadLink = $entry['download'];
                    $color = "#FF9999";
                }
                
                //Save metadata to the database
                $statement = $this->db->prepare("INSERT INTO flashes (post_id, time_posted, author, subject, category, filename, size, status, download_link, source) VALUES (?,?,?,?,?,?,?,?,?,?)");
                $statement->execute(array(
                    $entry['post_id'],
                    $entry['time_posted'],
                    $entry['author'],
                    $entry['subject'],
                    $entry['category'],
                    $entry['filename'],
                    $entry['size'],
                    $status,
                    $downloadLink,
                    $entry['source']
                ));
                
                //Save metadata file
                $metadata = 'Subject: '.$entry['subject'].'
Original filename: '.$entry['filename'].'
Category: '.$entry['category'].'
Author: '.$entry['author'].'
Source: '.$entry['source'].'
Time posted: '.$entry['time_posted'].'
';
                file_put_contents(self::META_FOLDER.'/'.$entry['post_id'].'.txt', $metadata);
                echo 'Saved data for post ID '.$id.'<br>';
                
                error_reporting(E_ALL);
            }
            
            echo '</div>';
            
            $output = str_replace('#FFFFFF', $color, ob_get_contents());
            ob_end_clean();
            echo $output;
        }
    }
}
