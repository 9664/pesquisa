<?php

// =====================================================
// ARQUIVO 4: admin/api/export-logs.php
// Salvar em: /admin/api/export-logs.php
// EXPORTAR LOGS DE AÇÕES DOS ADMINS
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
header('Content-Disposition: attachment; filename=logs_acoes_' . date('Y-m-d') . '.csv');

// Criar output
$output = fopen('php://output', 'w');

// UTF-8 BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Headers do CSV
fputcsv($output, array(
    'ID', 'Data/Hora', 'Usuário', 'Ação', 'Detalhes'
), ';');

// Buscar dados
$query = "SELECT 
          l.id,
          l.created_at,
          u.nome as usuario,
          l.acao,
          l.detalhes
          FROM log_acoes l
          LEFT JOIN usuarios_admin u ON l.usuario_id = u.id
          ORDER BY l.created_at DESC";
          
$stmt = $db->prepare($query);
$stmt->execute();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, array(
        $row['id'],
        date('d/m/Y H:i:s', strtotime($row['created_at'])),
        $row['usuario'],
        $row['acao'],
        $row['detalhes']
    ), ';');
}

fclose($output);
?>