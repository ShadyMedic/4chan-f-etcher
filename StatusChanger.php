<?php


class StatusChanger
{
    private const ACCESSCODE = 'u7vr3qs6cpf29jl';
    private const ADMIN_CODE = 'qpvdd5iw7zxy5jo54sw6xh11iif56r2';
    
    private $db;
    
    public function __construct()
    {
        require 'Db.php';
        $this->db = Db::connect();
    }
    
    /**
     * Method changing the status of a given entry
     * In case the second argument is set to "LOST", the entry is deleted
     * @param int $post_id Post ID whose status should be changed
     * @param string $newStatus New status - "NOT ARCHIVED", "ARCHIVED", "UNCURATABLE", "FLASHFREEZED", "CURATED",
     *     "LOST"
     * @param string $currentStatus Status that the entry must have now (prevention against request forgery attack)
     * @return bool TRUE if the status is successfully changed, FALSE if the use didn't enter proper access code on the index page
     */
    public function updateStatus(int $post_id, string $newStatus, string $currentStatus): bool
    {
        if (empty($_COOKIE) || !isset($_COOKIE['accesscode']) || ($_COOKIE['accesscode'] !== self::ACCESSCODE && $_COOKIE['accesscode'] !== self::ADMIN_CODE)) {
            return false;
        }
        
        if ($newStatus === "LOST") {
            $statement = $this->db->prepare('DELETE FROM flashes WHERE post_id = ? AND status = "NOT ARCHIVED"');
            return $statement->execute(array($post_id));
        } else {
            $statement = $this->db->prepare('UPDATE flashes SET status = ? WHERE post_id = ? AND status = ?');
            return $statement->execute(array($newStatus, $post_id, $currentStatus));
        }
    }
}