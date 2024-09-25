<?php

require_once 'Database.php';

class VisaClearingImporter {
    private $conn;
    private $batchSize;
    private $logFile;

    public function __construct($batchSize = 1000) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->batchSize = $batchSize; // Tamanho do lote para processar
        $this->logFile = 'import_log.txt';
    }

    public function importFromDirectory($directoryPath) {
        echo "Iniciando a importação de arquivos JSON na pasta: $directoryPath\n";
        $this->log("Iniciando a importação de arquivos JSON na pasta: $directoryPath");

        $files = glob($directoryPath . '/*.json');
        if (empty($files)) {
            echo "Nenhum arquivo JSON encontrado na pasta.\n";
            $this->log("Nenhum arquivo JSON encontrado na pasta.");
            return;
        }

        foreach ($files as $filePath) {
            echo "Importando arquivo: $filePath\n";
            $this->log("Importando arquivo: $filePath");
            $this->importFromFile($filePath);
        }

        echo "Importação de todos os arquivos concluída.\n";
        $this->log("Importação de todos os arquivos concluída.");
    }

    public function importFromFile($filePath) {
        echo "Iniciando a importação do arquivo: $filePath\n";
        $this->log("Iniciando a importação do arquivo: $filePath");

        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            $errorMessage = "Erro ao abrir o arquivo JSON: $filePath";
            die($errorMessage);
        }

        echo "Arquivo JSON aberto com sucesso. Decodificando...\n";
        $this->log("Arquivo JSON aberto com sucesso. Decodificando...");

        $dataArray = json_decode($fileContent, true);
        if ($dataArray === null) {
            $errorMessage = "Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
            die($errorMessage);
        }

        $this->conn->beginTransaction();

        try {

            foreach ($dataArray as $data) {
                $this->insertData($data);
            }

            $this->conn->commit();

            echo "Importação do arquivo $filePath concluída para o banco de dados.\n";
            $this->log("Importação do arquivo $filePath concluída para o banco de dados.");
        } catch (Exception $e) {
            $this->conn->rollBack();
            $errorMessage = "Erro na importação: " . $e->getMessage() . "\n";
            echo $errorMessage;
            $this->log($errorMessage);
        }
    }

    private function insertData(array $data) {
        $sql = "INSERT INTO clearing_transactions (source, source_date, dest_currency, arn, slice_code, 
            cardbrandid, externalid, local_date, authorization_date, purchase_value, 
            clearing_debit, installment_nbr, clearing_installment, installment_value_1, 
            installment_value_n, clearing_value, issuer_exchange_rate, clearing_commission, 
            clearing_interchange_fee_sign, qualifier, bin_card, acquirer_id, mcc, 
            dest_value, boarding_fee, status, operation_type, cdt_amount, 
            product_code, operation_code, reason_code, pan, late_presentation, 
            entry_mode, pos_entry_mode, clearing_files_row_id, clearing_currency, 
            clearing_boarding_fee, clearing_settlement_date, clearing_presentation, 
            clearing_action_code, clearing_total_partial_transaction, 
            clearing_flag_partial_settlement, clearing_cancel, clearing_confirm, 
            clearing_add, clearing_credit) 
        VALUES (:source, :source_date, :dest_currency, :arn, :slice_code, 
            :cardbrandid, :externalid, :local_date, :authorization_date, :purchase_value, 
            :clearing_debit, :installment_nbr, :clearing_installment, :installment_value_1, 
            :installment_value_n, :clearing_value, :issuer_exchange_rate, :clearing_commission, 
            :clearing_interchange_fee_sign, :qualifier, :bin_card, :acquirer_id, :mcc, 
            :dest_value, :boarding_fee, :status, :operation_type, :cdt_amount, 
            :product_code, :operation_code, :reason_code, :pan, :late_presentation, 
            :entry_mode, :pos_entry_mode, :clearing_files_row_id, :clearing_currency, 
            :clearing_boarding_fee, :clearing_settlement_date, :clearing_presentation, 
            :clearing_action_code, :clearing_total_partial_transaction, 
            :clearing_flag_partial_settlement, :clearing_cancel, :clearing_confirm, 
            :clearing_add, :clearing_credit)";

        $stmt = $this->conn->prepare($sql);
        
        if ($stmt->execute($data) === false) {
            throw new Exception("Erro ao inserir dados para slice_code '{$data['slice_code']}': " . implode(", ", $stmt->errorInfo()));
        }
        $successMessage = "O slice_code '{$data['slice_code']}' está na fila para inserção.\n";
        echo $successMessage;
        $this->log($successMessage);
    }

    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[$timestamp] $message", FILE_APPEND);
    }
}

$importer = new VisaClearingImporter();
$importer->importFromDirectory('VISA_CLEARING');