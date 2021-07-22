<?php


use Archive7z\Archive7z;

class Flashfreezer
{
    private const DOWNLOADS_FOLDER = 'downloads';
    private const META_FOLDER = 'metadata';
    private const MIN_ARCHIVE_SIZE = 200; //In MB
    
    private $db;
    private $archivingIds = array();
    
    /**
     * Create the object and connect to the database
     * Flashfreezer constructor
     */
    public function __construct()
    {
        require 'Db.php';
        
        //Connect to the database
        $this->db = Db::connect();
    }
    
    /**
     * Method that packs all entries with the "ARCHIVED" status into a .7z and returns a download link
     * The .swf files of packed entries and their metadata files are deleted
     */
    public function freeze()
    {
        $dirname = $this->createTempDirectory();
        
        $archive = new Archive7z($dirname.'.7z');
        $archive->addEntry($dirname.'/');
        
        if (filesize($dirname.'.7z') < self::MIN_ARCHIVE_SIZE * 1000000) {
            echo "<p style='background-color: #FF9999; border: 1px solid black'>The result archive file is too small - only ".
                 (filesize($dirname.'.7z') / 1000000)." MB. The current minimum limit is set to ".
                 self::MIN_ARCHIVE_SIZE." MB</p>";
            
            //Delete archive file
            unlink($dirname.'.7z');
            
            //Delete temporary folder
            $this->deleteTempDirectory($dirname);
            die();
        }
        
        if ($archive->isValid()) {
            $unstableLifeResponse = $this->uploadArchiveToFlashfreeze($dirname.'.7z');
            $uploadedSize = $unstableLifeResponse->size;
            $downloadUrl = $unstableLifeResponse->url;
            $deleteKey = $unstableLifeResponse->delete_key;
            
            if (filesize($dirname.'.7z') > $uploadedSize) {
                echo '<p style="background-color: #FF9999; border: 1px solid black">An error occurred - the uploaded file is smaller than the original file. Maybe it was uploaded just partially</p>';
                
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $downloadUrl);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array('Linx-Delete-Key: '.$deleteKey, 'User-Agent: Shady'));
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                
                $response = curl_exec($curl);
                if (trim($response) === "DELETED") {
                    echo "<p style='background-color: #FFBB00; border: 1px solid black'>Successfully deleted incomplete submission from unstable.life</p>";
                    die();
                }
            }
            
            //Create new freeze record
            $statement = $this->db->prepare('INSERT INTO freezes (url,flashes,size,delete_key) VALUES (?,?,?,?)');
            $statement->execute(array($downloadUrl, count($this->archivingIds), $uploadedSize, $deleteKey));
            
            //Update statuses of freezed entries
            $statement = $this->db->prepare('UPDATE flashes SET status = "FLASHFREEZED", download_link = ? WHERE post_id IN ('.
                                            implode(',', $this->archivingIds).
                                            ')'); //archivingIds is safe - it was fetched from the "flashes" table
            $statement->execute(array($downloadUrl));
            
            //Delete archive file
            unlink($dirname.'.7z');
            
            //Delete temporary folder
            $this->deleteTempDirectory($dirname);
            
            //Delete freezed files and their metadata files
            $this->deleteFreezedFiles();
            
            return true;
        }
        return false;
    }
    
    /**
     * Method copying all files of uncurated entries that are not marked as uncuratable into a temporary folder
     * If the temporary folder already exists, it's replaced
     * @return string Name of the temporary folder
     * @throws Exception
     */
    private function createTempDirectory(): string
    {
        //Fetch basic metadata of entries whose status is "ARCHIVED"
        $result = $this->db->query('SELECT post_id, filename, time_posted FROM flashes WHERE status = "ARCHIVED" ORDER BY time_posted ASC');
        $data = $result->fetchAll();
        
        if (count($data) === 0) {
            die("<p style='background-color: #FF9999; border: 1px solid black'>No flashes that could be uploaded to Flashfreeze were found.</p>");
        }
        
        $files = array();
        foreach ($data as $row) {
            $files[$row['post_id']] = $row['filename'];
            $this->archivingIds[] = $row['post_id'];
        }
        $dateFrom = (new DateTime($data[0]['time_posted']))->format('Y.m.d');
        $dateTo = (new DateTime($data[count($data) - 1]['time_posted']))->format('Y.m.d');
        
        $tempFolderName = '4chan-scrape-'.$dateFrom.'-'.$dateTo;
        
        if (is_dir($tempFolderName)) {
            $this->deleteTempDirectory($tempFolderName);
        }
        
        foreach ($files as $id => $filename) {
            mkdir($tempFolderName.'/'.$id, 0777, true);
            
            if (file_exists(self::DOWNLOADS_FOLDER.'/'.$id.'.swf')) {
                copy(self::DOWNLOADS_FOLDER.'/'.$id.'.swf', $tempFolderName.'/'.$id.'/'.$filename.'.swf');
            } //Copy the SWF file to the temporary directory
            if (file_exists(self::META_FOLDER.'/'.$id.'.txt')) {
                copy(self::META_FOLDER.'/'.$id.'.txt', $tempFolderName.'/'.$id.'/metadata.txt');
            } //Copy the metadata file to the temporary directory
        }
        return $tempFolderName;
    }
    
    /**
     * Method uploading the 7z archive to Flashfreeze server managed by Flashpoint admins
     * @param string $path Path to the archive to upload
     * @return mixed JSON object returned by the Linx server (see https://demo.linx-server.net/API/)
     */
    private function uploadArchiveToFlashfreeze(string $path)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://flashfreeze.submissions.unstable.life/upload/');
        //curl_setopt($curl, CURLOPT_URL, 'https://bluepload.unstable.life/upload/');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/json', 'User-Agent: Shady'));
        curl_setopt($curl, CURLOPT_PUT, true);
        curl_setopt($curl, CURLOPT_INFILE, fopen($path, 'r'));
        curl_setopt($curl, CURLOPT_INFILESIZE, filesize($path));
        
        $response = curl_exec($curl);
        return json_decode($response);
    }
    
    /**
     * Method deleting the temporary directory and everything inside of it
     * @param string $dirName Name of the folder to delete
     */
    private function deleteTempDirectory(string $dirName)
    {
        //Copied from https://stackoverflow.com/a/3349792/14011077
        $it = new RecursiveDirectoryIterator($dirName, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dirName);
    }
    
    /**
     * Method deleteing SWF and meta files of successfully flashfreezed entries
     */
    private function deleteFreezedFiles()
    {
        foreach ($this->archivingIds as $id) {
            if (file_exists(self::DOWNLOADS_FOLDER.'/'.$id.'.swf')) {
                unlink(self::DOWNLOADS_FOLDER.'/'.$id.'.swf');
            }
            if (file_exists(self::META_FOLDER.'/'.$id.'.txt')) {
                unlink(self::META_FOLDER.'/'.$id.'.txt');
            }
        }
    }
}
