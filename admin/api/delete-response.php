<?php
// =====================================================
// admin/api/delete-response.php
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

$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(array("success" => false, "message" => "ID não fornecido"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    // Primeiro buscar os dados da pesquisa para o log
    $selectQuery = "SELECT nome, email FROM pesquisas WHERE id = :id";
    $selectStmt = $db->prepare($selectQuery);
    $selectStmt->bindParam(':id', $id);
    $selectStmt->execute();
    
    if ($selectStmt->rowCount() === 0) {
        echo json_encode(array("success" => false, "message" => "Pesquisa não encontrada"));
        exit();
    }
    
    $pesquisa = $selectStmt->fetch(PDO::FETCH_ASSOC);
    
    // Deletar a pesquisa
    $deleteQuery = "DELETE FROM pesquisas WHERE id = :id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(':id', $id);
    $deleteStmt->execute();
    
    if ($deleteStmt->rowCount() > 0) {
        // Log da ação
        $logQuery = "INSERT INTO log_acoes (usuario_id, acao, detalhes, created_at) 
                    VALUES (:usuario_id, 'DELETE_RESPONSE', :detalhes, NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->bindParam(':usuario_id', $_SESSION['admin_id']);
        $detalhes = "Deletou pesquisa ID: {$id} - {$pesquisa['nome']} ({$pesquisa['email']})";
        $logStmt->bindParam(':detalhes', $detalhes);
        $logStmt->execute();
        
        echo json_encode(array(
            "success" => true,
            "message" => "Pesquisa excluída com sucesso"
        ));
    } else {
        echo json_encode(array("success" => false, "message" => "Erro ao excluir pesquisa"));
    }
    
} catch (Exception $e) {
    error_log("Erro ao deletar pesquisa: " . $e->getMessage());
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao excluir pesquisa: " . $e->getMessage()
    ));
}
?>