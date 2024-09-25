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

    public function importFromDirectory($directoryPath) {
        echo "Iniciando a importação de arquivos JSON na pasta: $directoryPath\n";

        // Pega todos os arquivos .json no diretório
        $files = glob($directoryPath . '/*.json');
        if (empty($files)) {
            echo "Nenhum arquivo JSON encontrado na pasta.\n";
            return;
        }

        // Importa cada arquivo encontrado
        foreach ($files as $filePath) {
            echo "Importando arquivo: $filePath\n";
            $this->importFromFile($filePath);
        }

        echo "Importação de todos os arquivos concluída.\n";
    }

    public function importFromFile($filePath) {
    echo "Iniciando a importação do arquivo: $filePath\n";

    $fileHandle = fopen($filePath, 'r');
    if ($fileHandle === false) {
        die("Erro ao abrir o arquivo JSON: $filePath");
    }
    echo "Arquivo JSON aberto com sucesso. Decodificando...\n";

    $jsonString = '';
    while (($line = fgets($fileHandle)) !== false) {
        $line = trim($line);
        if (!empty($line)) {
            $jsonString .= $line; // Acumula as linhas
        }
    }

    fclose($fileHandle);

    // Agora, decodifica o JSON acumulado
    $data = json_decode($jsonString, true);
    if ($data === null) {
        echo "Erro ao decodificar JSON: " . json_last_error_msg() . "\n";
        return; // Retorna após erro
    }

    // Aqui você pode continuar com o processamento dos dados decodificados
    // Por exemplo, inserir no banco de dados
    $this->insertBatch($data); // Modifique conforme sua lógica
    echo "Importação do arquivo $filePath concluída.\n";
}



   private function insertBatch(array $batch) {
    // Verifica se há dados para inserir
    if (empty($batch)) {
        echo "Nenhum dado para inserir.\n";
        return;
    }

    // Prepara a parte inicial da SQL
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
            VALUES ";

    // Cria um array para os placeholders
    $values = [];
    $placeholders = [];

    // Prepara os dados para o bulk insert
    foreach ($batch as $i => $data) {
        $placeholders[] = "(
            :source{$i}, :source_date{$i}, :dest_currency{$i}, :arn{$i}, :slice_code{$i}, 
            :cardbrandid{$i}, :externalid{$i}, :local_date{$i}, :authorization_date{$i}, 
            :purchase_value{$i}, :clearing_debit{$i}, :installment_nbr{$i}, 
            :clearing_installment{$i}, :installment_value_1{$i}, 
            :installment_value_n{$i}, :clearing_value{$i}, :issuer_exchange_rate{$i}, 
            :clearing_commission{$i}, :clearing_interchange_fee_sign{$i}, 
            :qualifier{$i}, :bin_card{$i}, :acquirer_id{$i}, :mcc{$i}, 
            :dest_value{$i}, :boarding_fee{$i}, :status{$i}, :operation_type{$i}, 
            :cdt_amount{$i}, :product_code{$i}, :operation_code{$i}, 
            :reason_code{$i}, :pan{$i}, :late_presentation{$i}, 
            :entry_mode{$i}, :pos_entry_mode{$i}, :clearing_files_row_id{$i}, 
            :clearing_currency{$i}, :clearing_boarding_fee{$i}, 
            :clearing_settlement_date{$i}, :clearing_presentation{$i}, 
            :clearing_action_code{$i}, :clearing_total_partial_transaction{$i}, 
            :clearing_flag_partial_settlement{$i}, :clearing_cancel{$i}, 
            :clearing_confirm{$i}, :clearing_add{$i}, :clearing_credit{$i}
        )";

        // Adiciona os valores para cada campo
        foreach ($data as $key => $value) {
            $values["{$key}{$i}"] = $value; // Armazena os valores para bind
        }
    }

    // Junta os placeholders
    $sql .= implode(", ", $placeholders);
    $sql .= " ON CONFLICT (slice_code) DO NOTHING"; // Adiciona a cláusula ON CONFLICT

    // Prepara a instrução
    $stmt = $this->conn->prepare($sql);

    // Executa a instrução com os dados preparados
    if ($stmt->execute($values) === false) {
        echo "Erro ao inserir dados: " . implode(", ", $stmt->errorInfo()) . "\n";
    } else {
        echo "Importação concluída.\n";
    }
}

}

// Exemplo de uso
$importer = new VisaClearingImporter(100); // Definindo o tamanho do lote
$importer->importFromDirectory('VISA_CLEARING'); // Importa todos os arquivos JSON na pasta
