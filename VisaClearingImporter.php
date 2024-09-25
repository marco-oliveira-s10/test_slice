<?php

require_once 'Database.php';

class VisaClearingImporter {
    private $conn;
    private $batchSize;

    public function __construct($batchSize = 1000) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->batchSize = $batchSize; // Tamanho do lote
    }

    public function importFromFile($filePath) {
        echo "Iniciando a importação do arquivo: $filePath\n";
        
        $jsonContent = file_get_contents($filePath);
        if ($jsonContent === false) {
            die("Erro ao ler o arquivo JSON.");
        }
        echo "Arquivo JSON lido com sucesso. Decodificando...\n";

        $dataArray = json_decode($jsonContent, true);
        if ($dataArray === null) {
            die("Erro ao decodificar JSON: " . json_last_error_msg());
        }
        echo "JSON decodificado com sucesso. Total de registros: " . count($dataArray) . "\n";

        // Processar os dados em lotes
        $batches = array_chunk($dataArray, $this->batchSize);
        echo "Processando os dados em lotes de tamanho: $this->batchSize\n";

        foreach ($batches as $batchIndex => $batch) {
            echo "Inserindo lote " . ($batchIndex + 1) . " de " . count($batches) . "...\n";
            $this->insertBatch($batch);
            echo "Lote " . ($batchIndex + 1) . " inserido com sucesso.\n";
        }
        
        echo "Importação concluída.\n";
    }

    private function insertBatch(array $batch) {
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
                        :cardbrandid, :externalid, :local_date, :authorization_date, 
                        :purchase_value, :clearing_debit, :installment_nbr, 
                        :clearing_installment, :installment_value_1, 
                        :installment_value_n, :clearing_value, :issuer_exchange_rate, 
                        :clearing_commission, :clearing_interchange_fee_sign, 
                        :qualifier, :bin_card, :acquirer_id, :mcc, 
                        :dest_value, :boarding_fee, :status, :operation_type, 
                        :cdt_amount, :product_code, :operation_code, 
                        :reason_code, :pan, :late_presentation, :entry_mode, 
                        :pos_entry_mode, :clearing_files_row_id, :clearing_currency, 
                        :clearing_boarding_fee, :clearing_settlement_date, 
                        :clearing_presentation, :clearing_action_code, 
                        :clearing_total_partial_transaction, 
                        :clearing_flag_partial_settlement, 
                        :clearing_cancel, :clearing_confirm, 
                        :clearing_add, :clearing_credit)";

        $stmt = $this->conn->prepare($sql);

        foreach ($batch as $data) {
            // Faça uma verificação se o $data contém as chaves esperadas
            if ($stmt->execute($data) === false) {
                echo "Erro ao inserir dados: " . implode(", ", $stmt->errorInfo()) . "\n";
            }
        }
    }
}

// Exemplo de uso
$importer = new VisaClearingImporter(100); // Definindo o tamanho do lote
$importer->importFromFile('VISA_CLEARING/VISA_TRANSACTIONAL_CLEARING_20240705_01.json');
