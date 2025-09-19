<?php
// =====================================================
// admin/api/test-email-simple.php
// Teste de email SEM PHPMailer (usando função nativa mail())
// =====================================================
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Não autorizado"));
    exit();
}

try {
    // Email de teste usando função nativa do PHP
    $to = "seu-email@exemplo.com"; // ALTERE AQUI
    $subject = "Teste de Email - Sistema Tiãozinho";
    $message = "Este é um teste de envio de email do sistema administrativo.\n\n";
    $message .= "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
    $message .= "Sistema: Pesquisas Tiãozinho\n";
    
    // Headers básicos
    $headers = "From: pesquisa@tiaozinho.com\r\n";
    $headers .= "Reply-To: pesquisa@tiaozinho.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Tentar enviar usando função mail() nativa
    $success = mail($to, $subject, $message, $headers);
    
    if ($success) {
        echo json_encode(array(
            "success" => true,
            "message" => "Email enviado com sucesso usando função mail() nativa!",
            "details" => "Enviado para: $to"
        ));
    } else {
        echo json_encode(array(
            "success" => false,
            "message" => "Falha ao enviar email com função mail() nativa",
            "info" => "Verifique se o servidor suporta envio de email"
        ));
    }

} catch (Exception $e) {
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao enviar email: " . $e->getMessage()
    ));
}
?>