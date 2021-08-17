<?php

/**
 * Class fetching data from the database and creating associative array for the view
 */
class TableGenerator
{
    private $db;
    
    public function __construct()
    {
        require 'Db.php';
        $this->db = Db::connect();
    }
    
    /**
     * Fetches data from the database and stores the data in an array
     * @return array The array with the data from the database
     */
    public function generate(): array
    {
        $result = $this->db->query('SELECT * FROM posts JOIN flashes ON flashes.flash_id = posts.flash_id ORDER BY post_id DESC;');
        $data = $result->fetchAll();
        # $table = array();
        $table = $data;
        /*
        foreach ($data as $tableRow)
        {
            $temp = array();
            
            $temp[] = $tableRow['flash_id'];
            $temp[] = $tableRow['post_id'];
            $temp[] = $tableRow['subject'];
            $temp[] = $tableRow['filename'];
            $temp[] = $tableRow['size'];
            $temp[] = $tableRow['category'];
            $temp[] = $tableRow['author'];
            $temp[] = $tableRow['source'];
            $temp[] = $tableRow['time_posted'];
            $temp[] = $tableRow['status'];
            $temp[] = $tableRow['download_link'];
            
            $table[] = $temp;
        }
        */
        
        return $table;
    }
}