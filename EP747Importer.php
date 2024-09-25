<?php

require_once 'Database.php';

class EP747Importer {

    private $conn;
    private $filePath;

    public function __construct($filePath) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->filePath = $filePath;
    }

    public function processFile() {
     
        $handle = fopen($this->filePath, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
              
                if ($this->isValidLine($line)) {
                 
                    $data = $this->parseLine($line);
                    
                    if ($data && isset($data['amount']) && is_numeric($data['amount'])) {
                        $this->insertTransaction($data['type'], $data['amount'], $data['currency']);
                    }
                }
            }
            fclose($handle);
        } else {
            echo "Erro ao abrir o arquivo.";
        }
    }

    private function isValidLine($line) {
        return !(strpos($line, 'NO DATA FOR THIS REPORT') !== false || 
           strpos($line, 'END OF') !== false || 
           strpos($line, 'REPORT ID') !== false);
    }

    
    private function parseLine($line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) < 3) {
            return null;
        }

        return [
            'type' => $parts[0], 
            'amount' => $parts[1],
            'currency' => $parts[2]
        ];
    }

    private function insertTransaction($type, $amount, $currency) {
        $sql = "INSERT INTO transactions (type, amount, currency) VALUES (:type, :amount, :currency)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':amount', $amount);
        $stmt->bindParam(':currency', $currency);
        
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            echo "Erro ao inserir transação: " . $errorInfo[2] . "\n";
        }
    }
}

$importer = new EP747Importer('EP747/EP747_20240705.TXT');
$importer->processFile();
