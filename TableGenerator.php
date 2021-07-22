<?php


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
        $result = $this->db->query('SELECT * FROM flashes ORDER BY post_id DESC');
        $data = $result->fetchAll();
        $table = array();
        
        foreach ($data as $tableRow)
        {
            $temp = array();
            
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
        
        return $table;
    }
}