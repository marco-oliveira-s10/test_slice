<?php

require_once 'Database.php';

class EP747Importer
{
    private $conn;
    private $logFile;

    public function __construct($batchSize = 1000) {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->batchSize = $batchSize; // Tamanho do lote para processar
        $this->logFile = 'import_log.txt';
        $this->logMessage("Iniciando a execução do script...");
    }

    private function logMessage($message) {
        $date = date('Y-m-d H:i:s');
        echo "[$date] $message\n";
        file_put_contents($this->logFile, "[$date] $message\n", FILE_APPEND);
    }

    public function processDirectory($directory) {
        $files = glob($directory . '/*.txt');
        $this->logMessage("Arquivos encontrados: " . count($files));

        if (empty($files)) {
            $this->logMessage("Nenhum arquivo .txt encontrado no diretório: $directory");
            return; // Se não houver arquivos, saia da função
        }

        foreach ($files as $file) {
            $this->logMessage("Iniciando importação do arquivo: $file");
            $this->convertTextToDatabase($file);
        }
    }

    public function convertTextToDatabase($inputFile) {
        $content = file_get_contents($inputFile);
        $reports = explode("\f", $content); // <0x0c> é representado como \f em PHP

        foreach ($reports as $report) {
            if (trim($report) === '') {
                continue;
            }

            $jsonReport = [
                "report_id" => "",
                "reporting_for" => "",
                "rollup_to" => "",
                "funds_xfer_entity" => "",
                "settlement_currency" => "",
                "funds_transfer_amount" => null,
                "totals" => [
                    "ACQUIRER" => [],
                    "ISSUER" => [],
                    "OTHER" => [],
                    "NET_SETTLEMENT_AMOUNT" => []
                ]
            ];

            $lines = explode("\n", $report);
            foreach ($lines as $line) {
                $line = trim($line);
                // Extração de dados...
                if (strpos($line, "REPORT ID:") === 0) {
                    $jsonReport["report_id"] = trim(substr($line, 10));
                } elseif (strpos($line, "REPORTING FOR:") === 0) {
                    $jsonReport["reporting_for"] = trim(substr($line, 15));
                } elseif (strpos($line, "ROLLUP TO:") === 0) {
                    $jsonReport["rollup_to"] = trim(substr($line, 10));
                } elseif (strpos($line, "FUNDS XFER ENTITY:") === 0) {
                    $jsonReport["funds_xfer_entity"] = trim(substr($line, 18));
                } elseif (strpos($line, "SETTLEMENT CURRENCY:") === 0) {
                    $jsonReport["settlement_currency"] = trim(substr($line, 20));
                } elseif (strpos($line, "FUNDS TRANSFER AMOUNT:") === 0) {
                    $jsonReport["funds_transfer_amount"] = floatval(trim(substr($line, 23)));
                } elseif (preg_match('/TOTAL\s+(ACQUIRER|ISSUER|OTHER)\s+(\d+)\s+([\d,\.]+)\s+([\d,\.]+)/', $line, $matches)) {
                    if ($matches) {
                        $key = $matches[1];
                        $jsonReport["totals"][$key] = [
                            "count" => intval($matches[2]),
                            "credit_amount" => floatval(str_replace(',', '', $matches[3])),
                            "debit_amount" => floatval(str_replace(',', '', $matches[4])),
                        ];
                    }
                } elseif (preg_match('/NET SETTLEMENT AMOUNT\s+([\d,\.]+)\s+([\d,\.]+)\s+([\d,\.]+)/', $line, $matches)) {
                    if ($matches) {
                        $jsonReport["totals"]["NET_SETTLEMENT_AMOUNT"] = [
                            "amount" => floatval(str_replace(',', '', $matches[1])),
                            "credit" => floatval(str_replace(',', '', $matches[2])),
                            "debit" => floatval(str_replace(',', '', $matches[3])),
                        ];
                    }
                }
            }

            // Definindo valores padrão caso não existam
            $acquirerCount = $jsonReport["totals"]["ACQUIRER"]["count"] ?? 0;
            $acquirerCreditAmount = $jsonReport["totals"]["ACQUIRER"]["credit_amount"] ?? 0;
            $acquirerDebitAmount = $jsonReport["totals"]["ACQUIRER"]["debit_amount"] ?? 0;

            $issuerCount = $jsonReport["totals"]["ISSUER"]["count"] ?? 0;
            $issuerCreditAmount = $jsonReport["totals"]["ISSUER"]["credit_amount"] ?? 0;
            $issuerDebitAmount = $jsonReport["totals"]["ISSUER"]["debit_amount"] ?? 0;

            $otherCount = $jsonReport["totals"]["OTHER"]["count"] ?? 0;
            $otherCreditAmount = $jsonReport["totals"]["OTHER"]["credit_amount"] ?? 0;
            $otherDebitAmount = $jsonReport["totals"]["OTHER"]["debit_amount"] ?? 0;

            $netSettlementAmount = $jsonReport["totals"]["NET_SETTLEMENT_AMOUNT"]["amount"] ?? 0;
            $netSettlementCredit = $jsonReport["totals"]["NET_SETTLEMENT_AMOUNT"]["credit"] ?? 0;
            $netSettlementDebit = $jsonReport["totals"]["NET_SETTLEMENT_AMOUNT"]["debit"] ?? 0;

            // Prepare e execute a consulta usando PDO
            try {
                $this->conn->beginTransaction(); // Começando uma transação

                // Garantindo que todos os valores estejam no formato correto para SQL
                $fundsTransferAmount = $jsonReport["funds_transfer_amount"] ?? null;

                $query = "INSERT INTO settlements (report_id, reporting_for, rollup_to, funds_xfer_entity, settlement_currency, funds_transfer_amount, 
                    total_acquirer_count, total_acquirer_credit_amount, total_acquirer_debit_amount, 
                    total_issuer_count, total_issuer_credit_amount, total_issuer_debit_amount, 
                    total_other_count, total_other_credit_amount, total_other_debit_amount, 
                    net_settlement_amount, net_settlement_credit, net_settlement_debit) 
                VALUES (
                    :report_id, 
                    :reporting_for, 
                    :rollup_to, 
                    :funds_xfer_entity, 
                    :settlement_currency, 
                    :funds_transfer_amount, 
                    :acquirerCount, 
                    :acquirerCreditAmount, 
                    :acquirerDebitAmount, 
                    :issuerCount, 
                    :issuerCreditAmount, 
                    :issuerDebitAmount, 
                    :otherCount, 
                    :otherCreditAmount, 
                    :otherDebitAmount, 
                    :netSettlementAmount, 
                    :netSettlementCredit, 
                    :netSettlementDebit
                )";

                $stmt = $this->conn->prepare($query);
                $stmt->execute([
                    ':report_id' => $jsonReport["report_id"],
                    ':reporting_for' => $jsonReport["reporting_for"],
                    ':rollup_to' => $jsonReport["rollup_to"],
                    ':funds_xfer_entity' => $jsonReport["funds_xfer_entity"],
                    ':settlement_currency' => $jsonReport["settlement_currency"],
                    ':funds_transfer_amount' => $fundsTransferAmount,
                    ':acquirerCount' => $acquirerCount,
                    ':acquirerCreditAmount' => $acquirerCreditAmount,
                    ':acquirerDebitAmount' => $acquirerDebitAmount,
                    ':issuerCount' => $issuerCount,
                    ':issuerCreditAmount' => $issuerCreditAmount,
                    ':issuerDebitAmount' => $issuerDebitAmount,
                    ':otherCount' => $otherCount,
                    ':otherCreditAmount' => $otherCreditAmount,
                    ':otherDebitAmount' => $otherDebitAmount,
                    ':netSettlementAmount' => $netSettlementAmount,
                    ':netSettlementCredit' => $netSettlementCredit,
                    ':netSettlementDebit' => $netSettlementDebit
                ]);

                $this->conn->commit(); // Confirmando a transação
                $this->logMessage("Dados inseridos com sucesso para o arquivo: $inputFile");

            } catch (Exception $e) {
                $this->conn->rollBack(); // Revertendo a transação em caso de erro
                $this->logMessage("Erro na inserção de dados: " . $e->getMessage());
            }
        }
    }
}

$importer = new EP747Importer();
$importer->processDirectory('EP747');
