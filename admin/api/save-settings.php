<?php
// =====================================================
// admin/api/save-settings.php
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

$smtp_host = $_POST['smtp_host'] ?? '';
$smtp_port = $_POST['smtp_port'] ?? '587';
$smtp_user = $_POST['smtp_user'] ?? '';
$smtp_pass = $_POST['smtp_pass'] ?? '';
$email_from = $_POST['email_from'] ?? '';
$email_admin = $_POST['email_admin'] ?? '';

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Array com as configurações a serem salvas
    $settings = array(
        'email_smtp_host' => $smtp_host,
        'email_smtp_port' => $smtp_port,
        'email_smtp_user' => $smtp_user,
        'email_from' => $email_from,
        'email_admin' => $email_admin
    );
    
    // Se a senha foi fornecida, incluir na configuração
    if (!empty($smtp_pass)) {
        $settings['email_smtp_pass'] = $smtp_pass;
    }
    
    // Salvar cada configuração
    foreach ($settings as $chave => $valor) {
        $query = "INSERT INTO configuracoes (chave, valor) 
                  VALUES (:chave, :valor)
                  ON DUPLICATE KEY UPDATE valor = :valor2";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':chave', $chave);
        $stmt->bindParam(':valor', $valor);
        $stmt->bindParam(':valor2', $valor);
        $stmt->execute();
    }
    
    // Log da ação
    $logQuery = "INSERT INTO log_acoes (usuario_id, acao, detalhes, created_at) 
                VALUES (:usuario_id, 'SAVE_SETTINGS', :detalhes, NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->bindParam(':usuario_id', $_SESSION['admin_id']);
    $detalhes = "Atualizou configurações de email";
    $logStmt->bindParam(':detalhes', $detalhes);
    $logStmt->execute();
    
    $db->commit();
    
    echo json_encode(array(
        "success" => true,
        "message" => "Configurações salvas com sucesso"
    ));
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Erro ao salvar configurações: " . $e->getMessage());
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao salvar configurações: " . $e->getMessage()
    ));
}
?>