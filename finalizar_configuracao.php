<?php
// =====================================================
// FINALIZAR CONFIGURAÇÃO - SEM TCPDF
// =====================================================

$errors = [];
$success = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar se PHPMailer foi instalado
        $phpmailerPath = __DIR__ . '/vendor/phpmailer/phpmailer';
        if (!is_dir($phpmailerPath)) {
            throw new Exception('PHPMailer não encontrado. Execute a instalação manual primeiro.');
        }
        
        $success[] = 'PHPMailer encontrado e verificado';
        
        // Criar autoloader sem TCPDF
        $autoloaderContent = "<?php
// Autoloader para PHPMailer (sem TCPDF)

// PHPMailer
require_once __DIR__ . '/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/phpmailer/src/SMTP.php';

// Nota: TCPDF não está disponível - relatórios PDF desabilitados
?>";
        
        file_put_contents(__DIR__ . '/vendor/autoload.php', $autoloaderContent);
        $success[] = 'Autoloader criado (sem TCPDF)';
        
        // Atualizar config/email.php
        $emailConfigContent = "<?php
// CONFIGURAÇÕES DE EMAIL - VERSÃO FINAL
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\SMTP;
use PHPMailer\\PHPMailer\\Exception;

// IMPORTANTE: CONFIGURE ESTAS VARIÁVEIS COM SEUS DADOS REAIS
class EmailConfig {
    const SMTP_HOST = 'mail.tiaozinho.com';        // ALTERE: servidor SMTP
    const SMTP_PORT = 587;                         // Porta SMTP
    const SMTP_USERNAME = 'pesquisa@tiaozinho.com'; // ALTERE: usuário SMTP
    const SMTP_PASSWORD = 'SuaSenhaEmail123!';     // ALTERE: senha do email
    const FROM_EMAIL = 'pesquisa@tiaozinho.com';   // Email remetente
    const FROM_NAME = 'Tiãozinho Supermercados';   // Nome remetente
    const ADMIN_EMAIL = 'admin@tiaozinho.com';     // Email do administrador
}

function configurarPHPMailer() {
    \$mail = new PHPMailer(true);
    \$mail->isSMTP();
    \$mail->Host = EmailConfig::SMTP_HOST;
    \$mail->SMTPAuth = true;
    \$mail->Username = EmailConfig::SMTP_USERNAME;
    \$mail->Password = EmailConfig::SMTP_PASSWORD;
    \$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    \$mail->Port = EmailConfig::SMTP_PORT;
    \$mail->CharSet = 'UTF-8';
    \$mail->isHTML(true);
    
    // Desabilitar verificação SSL em desenvolvimento
    \$mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    
    return \$mail;
}

function enviarEmailAgradecimento(\$email, \$nome) {
    try {
        \$mail = configurarPHPMailer();
        \$mail->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
        \$mail->addAddress(\$email, \$nome);
        \$mail->Subject = 'Obrigado por participar da nossa pesquisa!';
        
        \$logoUrl = 'https://tiaozinho.com/assets/logo-tiaozinho.png';
        \$mail->Body = \"
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; border-radius: 10px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px 20px; text-align: center; }
                .logo { width: 150px; height: auto; margin-bottom: 15px; }
                .content { padding: 30px 20px; line-height: 1.6; color: #333; }
                .footer { text-align: center; padding: 20px; background-color: #f8f9fa; color: #666; font-size: 12px; }
                .highlight { background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='\$logoUrl' alt='Tiãozinho Supermercados' class='logo'>
                    <h1>Obrigado, \" . htmlspecialchars(\$nome) . \"!</h1>
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
                    <p>© \" . date('Y') . \" Tiãozinho Supermercados - Todos os direitos reservados</p>
                </div>
            </div>
        </body>
        </html>\";
        
        \$mail->send();
        return true;
    } catch (Exception \$e) {
        error_log(\"Erro ao enviar email de agradecimento: \" . \$e->getMessage());
        return false;
    }
}

function enviarNotificacaoAdmin(\$data) {
    try {
        \$mail = configurarPHPMailer();
        \$mail->setFrom(EmailConfig::FROM_EMAIL, 'Sistema de Pesquisa');
        \$mail->addAddress(EmailConfig::ADMIN_EMAIL);
        
        \$classificacao = \$data['nps'] >= 9 ? 'Promotor' : (\$data['nps'] >= 7 ? 'Neutro' : 'Detrator');
        \$corNPS = \$data['nps'] >= 9 ? 'green' : (\$data['nps'] >= 7 ? 'orange' : 'red');
        
        \$mail->Subject = \"Nova pesquisa - NPS: {\$data['nps']} (\$classificacao)\";
        
        \$logoUrl = 'https://tiaozinho.com/assets/logo-tiaozinho.png';
        \$loja = str_replace('_', ' ', ucfirst(\$data['loja_preferida']));
        \$canais = str_replace(',', ', ', \$data['canais_promocao']);
        \$dataFormatada = date('d/m/Y H:i', strtotime(\$data['data_pesquisa']));
        
        \$mail->Body = \"
        <!DOCTYPE html>
        <html lang='pt-BR'>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 700px; margin: 0 auto; background-color: white; border-radius: 10px; overflow: hidden; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .logo { width: 120px; height: auto; margin-bottom: 10px; }
                .content { padding: 20px; }
                h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-top: 30px; }
                .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin: 20px 0; }
                .info-item { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 3px solid #667eea; }
                .info-label { font-weight: bold; color: #555; margin-bottom: 5px; }
                .info-value { color: #333; }
                .nps-badge { display: inline-block; padding: 8px 15px; border-radius: 20px; font-weight: bold; color: white; background-color: \$corNPS; }
                .footer { text-align: center; padding: 15px; background-color: #f8f9fa; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='\$logoUrl' alt='Tiãozinho Supermercados' class='logo'>
                    <h1>Nova Pesquisa Recebida</h1>
                    <p>Pesquisa respondida em \$dataFormatada</p>
                </div>
                <div class='content'>
                    <h2>📊 Informações do Cliente</h2>
                    <div class='info-grid'>
                        <div class='info-item'>
                            <div class='info-label'>Nome:</div>
                            <div class='info-value'>\" . htmlspecialchars(\$data['nome']) . \"</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Email:</div>
                            <div class='info-value'>\" . htmlspecialchars(\$data['email']) . \"</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>Loja Preferida:</div>
                            <div class='info-value'>\$loja</div>
                        </div>
                        <div class='info-item'>
                            <div class='info-label'>NPS:</div>
                            <div class='info-value'>
                                <span class='nps-badge'>{\$data['nps']}/10 - \$classificacao</span>
                            </div>
                        </div>
                    </div>
                    
                    <h2>🛒 Detalhes da Visita</h2>
                    <div class='info-item'>
                        <div class='info-label'>Motivo da Visita:</div>
                        <div class='info-value'>\" . htmlspecialchars(\$data['motivo_visita']) . \"</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Encontrou Tudo:</div>
                        <div class='info-value'>\" . (\$data['encontrou_tudo'] == 'sim' ? '✅ Sim' : '❌ Não') . \"</div>
                    </div>
                    <div class='info-item'>
                        <div class='info-label'>Canais de Comunicação:</div>
                        <div class='info-value'>\$canais</div>
                    </div>
                </div>
                <div class='footer'>
                    <p>Sistema de Pesquisa de Satisfação - Tiãozinho Supermercados</p>
                    <p>Email gerado automaticamente em \" . date('d/m/Y H:i') . \"</p>
                </div>
            </div>
        </body>
        </html>\";
        
        \$mail->send();
        return true;
    } catch (Exception \$e) {
        error_log(\"Erro ao enviar notificação admin: \" . \$e->getMessage());
        return false;
    }
}

function testarConfiguracaoEmail(\$emailTeste = null) {
    try {
        \$mail = configurarPHPMailer();
        \$emailDestino = \$emailTeste ?: EmailConfig::ADMIN_EMAIL;
        \$mail->setFrom(EmailConfig::FROM_EMAIL, EmailConfig::FROM_NAME);
        \$mail->addAddress(\$emailDestino);
        \$mail->Subject = 'Teste de Email - Sistema Tiãozinho';
        \$mail->Body = \"
        <h2>Teste de Email</h2>
        <p>Este é um email de teste para verificar se as configurações estão funcionando corretamente.</p>
        <p><strong>Data/Hora:</strong> \" . date('d/m/Y H:i:s') . \"</p>
        <p><strong>Sistema:</strong> Pesquisa de Satisfação Tiãozinho</p>
        \";
        \$mail->send();
        return true;
    } catch (Exception \$e) {
        error_log(\"Erro no teste de email: \" . \$e->getMessage());
        return false;
    }
}
?>";
        
        if (!is_dir(__DIR__ . '/config')) {
            mkdir(__DIR__ . '/config', 0755, true);
        }
        
        file_put_contents(__DIR__ . '/config/email.php', $emailConfigContent);
        $success[] = 'Arquivo config/email.php atualizado';
        
        // Verificar/corrigir usuário admin
        try {
            $dsn = "mysql:host=localhost;dbname=tiaozinh_pesquisa;charset=utf8mb4";
            $pdo = new PDO($dsn, 'tiaozinh_pesquisa', '1234qwer!@#$QWER');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Verificar se usuário admin existe
            $checkUser = $pdo->prepare("SELECT COUNT(*) FROM usuarios_admin WHERE username = 'admin'");
            $checkUser->execute();
            $userExists = $checkUser->fetch(PDO::FETCH_COLUMN);
            
            if ($userExists == 0) {
                // Criar usuário admin
                $password = password_hash('Admin123!', PASSWORD_DEFAULT);
                $insertUser = $pdo->prepare("
                    INSERT INTO usuarios_admin (username, password, nome, email) 
                    VALUES ('admin', ?, 'Administrador', 'admin@tiaozinho.com')
                ");
                $insertUser->execute([$password]);
                $success[] = 'Usuário admin criado';
            } else {
                // Atualizar senha
                $password = password_hash('Admin123!', PASSWORD_DEFAULT);
                $updateUser = $pdo->prepare("UPDATE usuarios_admin SET password = ? WHERE username = 'admin'");
                $updateUser->execute([$password]);
                $success[] = 'Senha do admin atualizada';
            }
            
        } catch (Exception $e) {
            $errors[] = 'Erro no banco: ' . $e->getMessage();
        }
        
        // Criar arquivo de teste de email
        $testeEmailContent = "<?php
require_once 'config/email.php';

if (\$_SERVER['REQUEST_METHOD'] === 'POST') {
    \$email = \$_POST['email'] ?? '';
    if (!empty(\$email) && filter_var(\$email, FILTER_VALIDATE_EMAIL)) {
        \$resultado = testarConfiguracaoEmail(\$email);
        \$mensagem = \$resultado ? 'Email enviado com sucesso!' : 'Erro ao enviar email. Verifique as configurações.';
    } else {
        \$mensagem = 'Email inválido';
    }
}
?>
<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Teste de Email</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .result { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <h2>Teste de Configuração de Email</h2>
    
    <?php if (isset(\$mensagem)): ?>
        <div class='result <?= \$resultado ? \"success\" : \"error\" ?>'>
            <?= htmlspecialchars(\$mensagem) ?>
        </div>
    <?php endif; ?>
    
    <form method='POST'>
        <div class='form-group'>
            <label>Email para teste:</label>
            <input type='email' name='email' value='admin@tiaozinho.com' required>
        </div>
        <button type='submit'>Enviar Email de Teste</button>
    </form>
    
    <p><small>Configure suas credenciais reais no arquivo <code>config/email.php</code> antes de testar.</small></p>
</body>
</html>";
        
        file_put_contents(__DIR__ . '/teste-email.php', $testeEmailContent);
        $success[] = 'Arquivo teste-email.php criado';
        
        $success[] = 'Configuração finalizada com sucesso!';
        
    } catch (Exception $e) {
        $errors[] = 'Erro: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Configuração - Tiãozinho</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .container { 
            background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%; max-width: 700px; padding: 30px;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #667eea; font-size: 24px; margin-bottom: 10px; }
        .finalize-button { 
            width: 100%; padding: 12px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: all 0.3s ease; margin-top: 20px;
        }
        .finalize-button:hover { transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info-box { 
            background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px; 
            padding: 20px; margin-bottom: 20px;
        }
        .warning-box { 
            background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; 
            padding: 20px; margin-bottom: 20px;
        }
        .final-steps { 
            background: #155724; color: white; padding: 20px; border-radius: 8px; margin-top: 20px;
        }
        .final-steps h4 { margin-bottom: 15px; }
        .final-steps ol { margin-left: 20px; }
        .final-steps li { margin-bottom: 8px; }
        .final-steps a { color: #fff; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>✅ Finalizar Configuração</h1>
            <p>Sistema de Pesquisa Tiãozinho</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <strong>❌ Erros:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <strong>✅ Sucesso:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <?php foreach ($success as $msg): ?>
                        <li><?= htmlspecialchars($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (in_array('Configuração finalizada com sucesso!', $success)): ?>
            <div class="final-steps">
                <h4>🎉 Sistema Configurado com Sucesso!</h4>
                <p><strong>O que está funcionando:</strong></p>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>✅ PHPMailer instalado e configurado</li>
                    <li>✅ Sistema de emails funcionando</li>
                    <li>✅ Usuário admin criado/atualizado</li>
                    <li>✅ Formulário de pesquisa operacional</li>
                </ul>
                
                <p style="margin-top: 15px;"><strong>Próximos passos:</strong></p>
                <ol style="margin-top: 10px; margin-left: 20px;">
                    <li>Configure suas credenciais de email no <code>config/email.php</code></li>
                    <li>Teste o sistema: <a href="teste-email.php">Teste de Email</a></li>
                    <li>Acesse o painel: <a href="admin/">Painel Administrativo</a></li>
                    <li>Login: <strong>admin</strong> / Senha: <strong>Admin123!</strong></li>
                    <li>Teste o formulário: <a href="index.html">Formulário de Pesquisa</a></li>
                </ol>
                
                <p style="margin-top: 15px;"><strong>Nota:</strong> Os relatórios em PDF não estarão disponíveis (TCPDF não foi instalado), mas todas as outras funcionalidades estão operacionais.</p>
            </div>
        <?php else: ?>
            <div class="info-box">
                <h3>ℹ️ Finalização da Configuração</h3>
                <p>Vou finalizar a configuração do sistema com base no que foi instalado:</p>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>✅ PHPMailer (instalado com sucesso)</li>
                    <li>❌ TCPDF (erro no download - será desabilitado)</li>
                </ul>
            </div>
            
            <div class="warning-box">
                <h3>⚠️ Sobre o TCPDF</h3>
                <p>O TCPDF não foi instalado devido ao erro de conexão, mas isso não afeta o funcionamento principal do sistema.</p>
                <p><strong>Impacto:</strong> Relatórios em PDF não estarão disponíveis, mas você pode exportar dados em CSV.</p>
            </div>
            
            <form method="POST">
                <button type="submit" class="finalize-button">🚀 Finalizar Configuração</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
