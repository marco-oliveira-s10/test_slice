# Slice - Desafio DEV 2024

# SOBRE O DESAFIO

- Criamos ele para que seja interessante, divertido e mostre um pouco dos
desafios reais que enfrentamos na Slice. O intuito não é que seja difícil e com pegadinhas. Nosso objetivo é
entender como você resolve problemas, lida com dados e transações. Separamos duas fontes de dados que representam uma amostra verossímil de um típico cliente: um banco emissor de cartões de crédito Visa. Diariamente recebemos 40 arquivos diferentes, separamos aqui 2:

# Clearing e EP747.

I) EP747 é um arquivo-fonte originado na bandeira Visa.
- É um formato proprietário em TXT;
- É uma fonte "sintética" (traz apenas lançamentos totalizados);
- Contém blocos e dados que podem ser de estruturas diferentes;
- O bloco VSS-600 traz a Agenda 27 Dias;
- Traz dados em moeda nacional e internacional;
- É uma fonte "compulsória", valores expressos no EP devem ser pagos pelo Emissor;

# IMPORTANTE:
No EP747 chegam informações, mesmo que totalizadas, sobre CHARGEBACK, FEES DE BANDEIRA, entre outras, que não constam na fonte CLEARING.

II) A PLANILHA XLSX é gerada pela processadora a partir do EP747.
- Ela faz um "mapping" dos nomes dos grupos vindos no EP para nomes mais amigáveis;
- COMPRA
- QUASI-CASH
- CRÉDITO-VOUCHER
- ORIGINAL-CREDIT
- SAQUE
- REAPRESENTAÇÃO e REVERSO dos tipos de transação acima

III) CLEARING é um serviço da processadora, composto de vários arquivosfonte originados na processadora a partir de fontes Base II (e outras) da bandeira Visa.
- Os arquivos são em formato proprietário JSON;
- É uma fonte "analítica" (traz lançamentos 'granulares', a nível de transação);
- Traz dados em várias moedas;
- É uma fonte que traz os valores e as datas para liquidação dos vários tipos de transação;

IV) ANEXOS / FUNCTIONS Disponibilizamos partes de duas funções da Slice que detectam os diferentes tipos de transação no CLEARING e no EP747.

# ENTREGÁVEIS

Esperamos que você faça:
1. Dois códigos de importação para banco de dados dos arquivos CLEARING e EP747;
2. Query’s que mostrem os seguintes resultados em ambos os arquivos:
	2.1. Soma total de COMPRAS em BRL;
	2.2. Soma total de COMPRAS em USD;
	2.3. Soma total de SAQUES em BRL;
	2.4. Soma total de SAQUES em USD;
	2.5. Soma total de REPASSE LÍQUIDO em BRL;
	2.6. Soma total de REPASSE LÍQUIDO em USD;

# REGRAS
- Use qualquer linguagem e banco de dados que quiser;
- Leve o tempo que precisar;
- Quando terminar, responda ao email para agendar a sua demonstração;
- Mantenha sigilo em relação aos dados disponibilizados. Não compartilhe, encaminhe ou divulgue de qualquer forma;
- Nos dê seu feedback sobre o nível de dificuldade do desafio, se acertamos na escolha do tema e se foi interessante/divertido;


# Time Slice.
