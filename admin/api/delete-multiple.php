<?php
// =====================================================
// admin/api/delete-multiple.php
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

$ids = $_POST['ids'] ?? array();

if (empty($ids) || !is_array($ids)) {
    echo json_encode(array("success" => false, "message" => "IDs não fornecidos"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Preparar query para deletar
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $query = "DELETE FROM pesquisas WHERE id IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($ids);
    
    $deletedCount = $stmt->rowCount();
    
    // Log da ação
    $logQuery = "INSERT INTO log_acoes (usuario_id, acao, detalhes, created_at) 
                VALUES (:usuario_id, 'DELETE_MULTIPLE', :detalhes, NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->bindParam(':usuario_id', $_SESSION['admin_id']);
    $detalhes = "Deletou {$deletedCount} pesquisas em lote. IDs: " . implode(', ', $ids);
    $logStmt->bindParam(':detalhes', $detalhes);
    $logStmt->execute();
    
    $db->commit();
    
    echo json_encode(array(
        "success" => true,
        "message" => "{$deletedCount} pesquisa(s) excluída(s) com sucesso",
        "deleted_count" => $deletedCount
    ));
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Erro ao deletar pesquisas: " . $e->getMessage());
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao excluir pesquisas: " . $e->getMessage()
    ));
}
?>