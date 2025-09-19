<?php
// =====================================================
// admin/api/get-settings.php
// =====================================================
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Não autorizado"));
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT chave, valor FROM configuracoes WHERE chave LIKE 'email_%'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $settings = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['chave']] = $row['valor'];
    }
    
    // Se não há configurações no banco, definir valores padrão
    if (empty($settings)) {
        $settings = array(
            'email_smtp_host' => 'smtp.gmail.com',
            'email_smtp_port' => '587',
            'email_smtp_user' => '',
            'email_smtp_pass' => '',
            'email_from' => '',
            'email_admin' => ''
        );
    }
    
    echo json_encode(array(
        "success" => true,
        "data" => $settings
    ));
    
} catch (Exception $e) {
    error_log("Erro ao buscar configurações: " . $e->getMessage());
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao buscar configurações"
    ));
}
?>