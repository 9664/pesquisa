<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

/**
 * Configurações centralizadas de email
 */
class EmailConfig {
    // IMPORTANTE: CONFIGURE COM SEUS DADOS REAIS DE EMAIL
    const SMTP_HOST = 'mail.tiaozinho.com';        // ALTERE: servidor SMTP
    const SMTP_PORT = 587;                         // Porta SMTP (587 para STARTTLS, 465 para SSL)
    const SMTP_USERNAME = 'pesquisa@tiaozinho.com'; // ALTERE: usuário SMTP
    const SMTP_PASSWORD = '1234qwer!@#$QWER';     // ALTERE: senha do email
    const SMTP_ENCRYPTION = PHPMailer::ENCRYPTION_STARTTLS; // ou PHPMailer::ENCRYPTION_SMTPS para SSL
    
    const FROM_EMAIL = 'pesquisa@tiaozinho.com';   // Email remetente
    const FROM_NAME = 'Tiãozinho Supermercados';   // Nome remetente
    const ADMIN_EMAIL = 'admin@tiaozinho.com';     // Email do administrador
    const ADMIN_NAME = 'Administrador';            // Nome do administrador
}

/**
 * Configura uma instância do PHPMailer com as configurações padrão
 * @return PHPMailer
 * @throws Exception
 */
function configurarPHPMailer() {
    $mail = new PHPMailer(true);
    
    // Configurações do servidor SMTP
    $mail->isSMTP();
    $mail->Host = EmailConfig::SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = EmailConfig::SMTP_USERNAME;
    $mail->Password = EmailConfig::SMTP_PASSWORD;
    $mail->SMTPSecure = EmailConfig::SMTP_ENCRYPTION;
    $mail->Port = EmailConfig::SMTP_PORT;
    
    // Configurações gerais
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->isHTML(true);
    
    // Configurações de debug (desabilitar em produção)
    if (defined('DEBUG') && DEBUG === true) {
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }
    
    return $mail;
}

/**
 * Envia email de agradecimento para o cliente
 * @param string $email Email do cliente
 * @param string $nome Nome do cliente
 * @return bool
 */
function enviarEmailAgradecimento($email, $nome) {
    try {
        $mail = configurarPHPMailer();
        
        // Remetente e destinatário
        $mail->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
        $mail->addAddress($email, $nome);
        
        // Assunto e conteúdo
        $mail->Subject = 'Obrigado por participar da nossa pesquisa!';
        $mail->Body = gerarTemplateAgradecimento($nome);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao enviar email de agradecimento para {$email}: " . $e->getMessage());
        return false;
    }
}

/**
 * Envia notificação para o administrador sobre nova pesquisa
 * @param array $data Dados da pesquisa
 * @return bool
 */
function enviarNotificacaoAdmin($data) {
    try {
        $mail = configurarPHPMailer();
        
        // Remetente e destinatário
        $mail->setFrom(EmailConfig::FROM_EMAIL, 'Sistema de Pesquisa');
        $mail->addAddress(EmailConfig::ADMIN_EMAIL, EmailConfig::ADMIN_NAME);
        
        // Assunto e conteúdo
        $classificacaoNPS = classificarNPS($data['nps']);
        $mail->Subject = "Nova pesquisa respondida - NPS: {$data['nps']} ({$classificacaoNPS})";
        $mail->Body = gerarTemplateNotificacao($data);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro ao enviar notificação admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Classifica o NPS em Promotor, Neutro ou Detrator
 * @param int $nps Valor do NPS (0-10)
 * @return string
 */
function classificarNPS($nps) {
    if ($nps >= 9) return 'Promotor';
    if ($nps >= 7) return 'Neutro';
    return 'Detrator';
}

/**
 * Gera template HTML para email de agradecimento
 * @param string $nome Nome do cliente
 * @return string
 */
function gerarTemplateAgradecimento($nome) {
    $logoUrl = 'https://tiaozinho.com/assets/logo-tiaozinho.png';
    
    return "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Obrigado pela sua participação!</title>
        <style>
            body { 
                font-family: Arial, sans-serif; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4; 
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                background-color: white; 
                border-radius: 10px; 
                overflow: hidden; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            }
            .header { 
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
                color: white; 
                padding: 30px 20px; 
                text-align: center; 
            }
            .logo { 
                width: 150px; 
                height: auto; 
                margin-bottom: 15px; 
                filter: brightness(0) invert(1); 
            }
            .content { 
                padding: 30px 20px; 
                line-height: 1.6; 
                color: #333; 
            }
            .footer { 
                text-align: center; 
                padding: 20px; 
                background-color: #f8f9fa; 
                color: #666; 
                font-size: 12px; 
            }
            .highlight { 
                background-color: #fff3cd; 
                padding: 15px; 
                border-radius: 5px; 
                border-left: 4px solid #ffc107; 
                margin: 20px 0; 
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='{$logoUrl}' alt='Tiãozinho Supermercados' class='logo'>
                <h1>Obrigado, " . htmlspecialchars($nome) . "!</h1>
            </div>
            <div class='content'>
                <p>Agradecemos imensamente por dedicar seu tempo para responder nossa pesquisa de satisfação.</p>
                
                <p>Sua opinião é fundamental para continuarmos melhorando nossos serviços e oferecendo sempre a melhor experiência de compra para você e sua família.</p>
                
                <div class='highlight'>
                    <strong>🎁 Como agradecimento, preparamos ofertas especiais para você!</strong><br>
                    Fique atento ao seu WhatsApp e email para receber nossas melhores promoções.
                </div>
                
                <p>Continuamos trabalhando para ser sempre o seu supermercado de confiança, com os melhores produtos, preços justos e atendimento de qualidade.</p>
                
                <p><strong>Atenciosamente,</strong><br>
                Equipe Tiãozinho Supermercados</p>
            </div>
            <div class='footer'>
                <p>Este é um email automático, por favor não responda.</p>
                <p>© " . date('Y') . " Tiãozinho Supermercados - Todos os direitos reservados</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Gera template HTML para notificação administrativa
 * @param array $data Dados da pesquisa
 * @return string
 */
function gerarTemplateNotificacao($data) {
    $logoUrl = 'https://tiaozinho.com/assets/logo-tiaozinho.png';
    $classificacao = classificarNPS($data['nps']);
    $corNPS = $data['nps'] >= 9 ? 'green' : ($data['nps'] >= 7 ? 'orange' : 'red');
    
    // Formatar dados
    $loja = str_replace('_', ' ', ucfirst($data['loja_preferida']));
    $canais = str_replace(',', ', ', $data['canais_promocao']);
    $dataFormatada = date('d/m/Y H:i', strtotime($data['data_pesquisa']));
    
    return "
    <!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Nova Pesquisa Recebida</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 700px; margin: 0 auto; background-color: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
            .logo { width: 120px; height: auto; margin-bottom: 10px; filter: brightness(0) invert(1); }
            .content { padding: 20px; }
            h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-top: 30px; }
            h3 { color: #667eea; margin-top: 20px; }
            .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
            .info-item { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 3px solid #667eea; }
            .info-label { font-weight: bold; color: #555; margin-bottom: 5px; }
            .info-value { color: #333; }
            .nps-badge { display: inline-block; padding: 8px 15px; border-radius: 20px; font-weight: bold; color: white; background-color: {$corNPS}; }
            .avaliacoes { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 15px 0; }
            .avaliacao-item { background: white; padding: 10px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
            .footer { text-align: center; padding: 15px; background-color: #f8f9fa; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='{$logoUrl}' alt='Tiãozinho Supermercados' class='logo'>
                <h1>Nova Pesquisa Recebida</h1>
                <p>Pesquisa respondida em {$dataFormatada}</p>
            </div>
            <div class='content'>
                <h2>📊 Informações do Cliente</h2>
                <div class='info-grid'>
                    <div class='info-item'>
                        <div class='info-label'>Nome:</div>
                        <div class='info-value'>" . htmlspecialchars($data['nome']) . "</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Email:</div>
                        <div class='info-value'>" . htmlspecialchars($data['email']) . "</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Loja Preferida:</div>
                        <div class='info-value'>{$loja}</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>NPS:</div>
                        <div class='info-value'>
                            <span class='nps-badge'>{$data['nps']}/10 - {$classificacao}</span>
                        </div>
                    </div>
                </div>
                
                <h2>🛒 Detalhes da Visita</h2>
                <div class='info-grid'>
                    <div class='info-item'>
                        <div class='info-label'>Motivo da Visita:</div>
                        <div class='info-value'>" . htmlspecialchars($data['motivo_visita']) . "</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Encontrou Tudo:</div>
                        <div class='info-value'>" . ($data['encontrou_tudo'] == 'sim' ? '✅ Sim' : '❌ Não') . "</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Canais de Comunicação:</div>
                        <div class='info-value'>{$canais}</div>
                    </div>
                </div>
                
                <h2>⭐ Avaliações (1-5)</h2>
                <div class='avaliacoes'>
                    <div class='avaliacao-item'>
                        <strong>Preço</strong><br>
                        {$data['avaliacao_preco']}/5
                    </div>
                    <div class='avaliacao-item'>
                        <strong>Fila</strong><br>
                        {$data['avaliacao_fila']}/5
                    </div>
                    <div class='avaliacao-item'>
                        <strong>Qualidade</strong><br>
                        {$data['avaliacao_qualidade']}/5
                    </div>
                    <div class='avaliacao-item'>
                        <strong>Limpeza</strong><br>
                        {$data['avaliacao_limpeza']}/5
                    </div>
                    <div class='avaliacao-item'>
                        <strong>Atendimento</strong><br>
                        {$data['avaliacao_atendimento']}/5
                    </div>
                    <div class='avaliacao-item'>
                        <strong>Satisfação</strong><br>
                        {$data['avaliacao_satisfacao']}/5
                    </div>
                </div>
                
                " . (!empty($data['voltar_amanha']) ? "
                <h2>💭 Comentário</h2>
                <div class='info-item'>
                    <div class='info-label'>O que faria voltar amanhã:</div>
                    <div class='info-value'>" . htmlspecialchars($data['voltar_amanha']) . "</div>
                </div>
                " : "") . "
                
                " . (!empty($data['o_que_faltou']) ? "
                <h2>❗ O que faltou</h2>
                <div class='info-item'>
                    <div class='info-value'>" . htmlspecialchars($data['o_que_faltou']) . "</div>
                </div>
                " : "") . "
            </div>
            <div class='footer'>
                <p>Sistema de Pesquisa de Satisfação - Tiãozinho Supermercados</p>
                <p>Email gerado automaticamente em " . date('d/m/Y H:i') . "</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Testa a configuração de email enviando um email de teste
 * @param string $emailTeste Email para envio do teste
 * @return bool
 */
function testarConfiguracaoEmail($emailTeste = null) {
    try {
        $mail = configurarPHPMailer();
        
        $emailDestino = $emailTeste ?: EmailConfig::ADMIN_EMAIL;
        
        $mail->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
        $mail->addAddress($emailDestino);
        
        $mail->Subject = 'Teste de Configuração de Email - Sistema Tiãozinho';
        $mail->Body = "
        <h2>Teste de Email</h2>
        <p>Este é um email de teste para verificar se as configurações estão funcionando corretamente.</p>
        <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
        <p><strong>Sistema:</strong> Pesquisa de Satisfação Tiãozinho</p>
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Erro no teste de email: " . $e->getMessage());
        return false;
    }
}
?>
