<?php
// =====================================================
// ARQUIVO 1: admin/api/test-email.php
// Salvar em: /admin/api/test-email.php
// =====================================================

session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Não autorizado"));
    exit();
}

require_once '../../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("success" => false, "message" => "Método não permitido"));
    exit();
}

$test_email = $_POST['test_email'] ?? '';

if (empty($test_email)) {
    echo json_encode(array("success" => false, "message" => "Email não fornecido"));
    exit();
}

// Buscar configurações do banco
$database = new Database();
$db = $database->getConnection();

$query = "SELECT chave, valor FROM configuracoes WHERE chave LIKE 'email_%'";
$stmt = $db->prepare($query);
$stmt->execute();

$settings = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['chave']] = $row['valor'];
}

// Configurar PHPMailer
$mail = new PHPMailer(true);

try {
    // Configurações do servidor
    $mail->isSMTP();
    $mail->Host       = $settings['email_smtp_host'] ?? 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $settings['email_smtp_user'] ?? '';
    $mail->Password   = $settings['email_smtp_pass'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $settings['email_smtp_port'] ?? 587;
    
    // Debug (remover em produção)
    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    
    // Destinatários
    $mail->setFrom($settings['email_from'] ?? 'noreply@tiaozinho.com', 'Tiãozinho Supermercados');
    $mail->addAddress($test_email, 'Teste');
    
    // Conteúdo
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Subject = 'Email de Teste - Sistema de Pesquisa Tiãozinho';
    
    $mail->Body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; text-align: center; border-radius: 10px; }
            .content { padding: 20px; background: #f9f9f9; margin-top: 20px; border-radius: 10px; }
            .success { color: #10b981; font-size: 24px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Teste de Email</h1>
            </div>
            <div class='content'>
                <p class='success'>✅ Email configurado corretamente!</p>
                <p>Este é um email de teste do Sistema de Pesquisa Tiãozinho.</p>
                <p>Se você está recebendo este email, significa que as configurações de SMTP estão corretas.</p>
                <hr>
                <p><strong>Configurações utilizadas:</strong></p>
                <ul>
                    <li>Servidor: {$settings['email_smtp_host']}</li>
                    <li>Porta: {$settings['email_smtp_port']}</li>
                    <li>Usuário: {$settings['email_smtp_user']}</li>
                    <li>Remetente: {$settings['email_from']}</li>
                </ul>
                <hr>
                <p><small>Enviado em: " . date('d/m/Y H:i:s') . "</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $mail->send();
    
    // Log do teste
    $logQuery = "INSERT INTO log_acoes (usuario_id, acao, detalhes, created_at) 
                VALUES (:usuario_id, 'TEST_EMAIL', :detalhes, NOW())";
    $logStmt = $db->prepare($logQuery);
    $logStmt->bindParam(':usuario_id', $_SESSION['admin_id']);
    $detalhes = "Enviou email de teste para: {$test_email}";
    $logStmt->bindParam(':detalhes', $detalhes);
    $logStmt->execute();
    
    echo json_encode(array(
        "success" => true,
        "message" => "Email de teste enviado com sucesso"
    ));
    
} catch (Exception $e) {
    error_log("Erro ao enviar email de teste: {$mail->ErrorInfo}");
    echo json_encode(array(
        "success" => false,
        "message" => "Erro ao enviar email: " . $mail->ErrorInfo
    ));
}