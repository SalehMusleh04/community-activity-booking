<?php


class DBConn {
    private $host = 'localhost';
    private $db_name = 'community_center_booking';
    private $username = 'root';
    private $password = '';
    private $conn = null;

    public function connect(){
        if($this->conn === null){
            $dsn = "mysql:host=".$this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4"; 
            $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
            try{
                $this->conn = new PDO($dsn,$this->username,$this->password , $options);

            } catch (PDOException $e) {
                die("Connection Failed : " . $e->getMessage());
            }
        }
        return $this->conn;
    }

}



?>