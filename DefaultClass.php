<?php

require_once 'Database.php';

class DefaultClass {

    private $conn;
    private $data;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function setData($data) {
        
        $sql = "SELECT * FROM test";
        $stmt = $this->conn->query($sql);
        $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getData() {
        
        return $this->data;
    }
}

$exec = new DefaultClass();
$exec->setData(null);
echo json_encode($exec->getData());