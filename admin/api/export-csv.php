<?php
// =====================================================
 // admin/api/export-csv.php
// =====================================================
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo "Não autorizado";
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Headers para download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=pesquisas_' . date('Y-m-d') . '.csv');

// Criar output
$output = fopen('php://output', 'w');

// UTF-8 BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers do CSV
fputcsv($output, array(
    'ID', 'Data', 'Nome', 'Email', 'Telefone', 'Loja Preferida', 
    'Canais de Comunicação', 'Outros Canais', 'NPS', 
    'Motivo Visita', 'Encontrou Tudo', 'O que Faltou',
    'Avaliação Preço', 'Avaliação Fila', 'Avaliação Qualidade',
    'Avaliação Limpeza', 'Avaliação Atendimento', 'Avaliação Satisfação',
    'O que faria voltar', 'Receber Ofertas'
), ';');

// Buscar dados
$query = "SELECT * FROM pesquisas ORDER BY data_pesquisa DESC";
$stmt = $db->prepare($query);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Formatar loja
    $loja = str_replace('_', ' ', ucfirst($row['loja_preferida']));
    
    // Formatar canais
    $canais = str_replace(',', ', ', $row['canais_promocao']);
    
    fputcsv($output, array(
        $row['id'],
        date('d/m/Y H:i', strtotime($row['data_pesquisa'])),
        $row['nome'],
        $row['email'],
        $row['telefone'],
        $loja,
        $canais,
        $row['outros_canais_especificar'],
        $row['nps'],
        $row['motivo_visita'],
        $row['encontrou_tudo'],
        $row['o_que_faltou'],
        $row['avaliacao_preco'],
        $row['avaliacao_fila'],
        $row['avaliacao_qualidade'],
        $row['avaliacao_limpeza'],
        $row['avaliacao_atendimento'],
        $row['avaliacao_satisfacao'],
        $row['voltar_amanha'],
        $row['receber_ofertas'] ? 'Sim' : 'Não'
    ), ';');
}

fclose($output);
?>