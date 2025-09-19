<?php
// =====================================================
// admin/api/logout.php
// =====================================================
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (isset($_SESSION['admin_id'])) {
    $admin_id = $_SESSION['admin_id'];
    $admin_nome = $_SESSION['admin_nome'] ?? 'Admin';
    
    // Log da ação antes de destruir a sessão
    require_once '../../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $logQuery = "INSERT INTO log_acoes (usuario_id, acao, detalhes, created_at) 
                    VALUES (:usuario_id, 'LOGOUT', :detalhes, NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->bindParam(':usuario_id', $admin_id);
        $detalhes = "Logout realizado - {$admin_nome}";
        $logStmt->bindParam(':detalhes', $detalhes);
        $logStmt->execute();
    } catch (Exception $e) {
        error_log("Erro ao registrar logout: " . $e->getMessage());
    }
    
    // Destruir sessão
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    echo json_encode(array(
        "success" => true,
        "message" => "Logout realizado com sucesso"
    ));
} else {
    echo json_encode(array(
        "success" => false,
        "message" => "Usuário não estava logado"
    ));
}
?>