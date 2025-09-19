<?php
// =====================================================
// ARQUIVO 3: admin/api/clear-all.php
// Salvar em: /admin/api/clear-all.php
// LIMPAR TODAS AS PESQUISAS (USE COM CUIDADO!)
// =====================================================

session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Não autorizado"));
    exit();
}

require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Método não permitido"));
    exit();
}

// Verificar confirmação
$confirm = $_POST['confirm'] ?? '';

if ($confirm !== 'DELETE_ALL_DATA') {
    echo json_encode(array(
        "success" => false,
        "message" => "Confirmação incorreta. Para deletar todos os dados, envie confirm=DELETE_ALL_DATA"
    ));
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Contar quantos registros existem
    $countQuery = "SELECT COUNT(*) as total FROM pesquisas";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Fazer backup antes de deletar (opcional - criar tabela de backup)
    $backupQuery = "CREATE TABLE IF NOT EXISTS pesquisas_backup_" . date('Ymd_His') . " AS SELECT * FROM pesquisas";
    $db->exec($backupQuery);
    
    // Deletar todos os registros
    $deleteQuery = "TRUNCATE TABLE pesquisas";
    $db->exec($deleteQuery);
    
    // Log da ação
    $logQuery = "INSERT INTO log_acoes (usuario_id, acao, detalhes, created_at) 
                VALUES (:usuario_id, 'CLEAR_ALL', :detalhes, NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->bindParam(':usuario_id', $_SESSION['admin_id']);
    $detalhes = "Deletou TODOS os dados ({$total} registros). Backup criado.";
    $logStmt->bindParam(':detalhes', $detalhes);
    $logStmt->execute();
    
    echo json_encode(array(
        "success" => true,
        "message" => "Todos os dados foram deletados com sucesso",
        "deleted_count" => $total,
        "backup_table" => "pesquisas_backup_" . date('Ymd_His')
    ));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao deletar dados: " . $e->getMessage()
    ));
}