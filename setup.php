<?php
// =====================================================
// SCRIPT DE SETUP PARA ESTRUTURA ATUAL
// Sistema de Pesquisa Tiãozinho
// =====================================================

// Verificar se já foi configurado
if (file_exists(__DIR__ . '/.configured')) {
    die('Sistema já foi configurado. Para reconfigurar, remova o arquivo .configured');
}

$errors = [];
$success = [];

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Testar conexão com banco usando credenciais corretas
        $dsn = "mysql:host=localhost;dbname=tiaozinh_pesquisa;charset=utf8mb4";
        $pdo = new PDO($dsn, 'tiaozinh_pesquisa', '1234qwer!@#$QWER');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $success[] = 'Conexão com banco de dados estabelecida com sucesso';
        
        // Executar schema do banco
        $schemaPath = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
        } else {
            // Usar schema inline se arquivo não existir
            $schema = "
            USE tiaozinh_pesquisa;
            
            CREATE TABLE IF NOT EXISTS pesquisas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                telefone VARCHAR(20),
                loja_preferida VARCHAR(20) NOT NULL,
                canais_promocao TEXT NOT NULL,
                outros_canais_especificar VARCHAR(255),
                nps INT NOT NULL CHECK (nps >= 0 AND nps <= 10),
                motivo_visita VARCHAR(50) NOT NULL,
                encontrou_tudo ENUM('sim', 'nao') NOT NULL,
                o_que_faltou TEXT,
                avaliacao_preco INT CHECK (avaliacao_preco >= 1 AND avaliacao_preco <= 5),
                avaliacao_fila INT CHECK (avaliacao_fila >= 1 AND avaliacao_fila <= 5),
                avaliacao_qualidade INT CHECK (avaliacao_qualidade >= 1 AND avaliacao_qualidade <= 5),
                avaliacao_limpeza INT CHECK (avaliacao_limpeza >= 1 AND avaliacao_limpeza <= 5),
                avaliacao_atendimento INT CHECK (avaliacao_atendimento >= 1 AND avaliacao_atendimento <= 5),
                avaliacao_satisfacao INT CHECK (avaliacao_satisfacao >= 1 AND avaliacao_satisfacao <= 5),
                voltar_amanha TEXT,
                receber_ofertas BOOLEAN DEFAULT 0,
                aceite_lgpd BOOLEAN DEFAULT 1,
                data_pesquisa DATETIME NOT NULL,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_data_pesquisa (data_pesquisa),
                INDEX idx_nps (nps),
                INDEX idx_email (email),
                INDEX idx_loja_preferida (loja_preferida)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            CREATE TABLE IF NOT EXISTS usuarios_admin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                ativo BOOLEAN DEFAULT 1,
                ultimo_acesso DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            
            INSERT IGNORE INTO usuarios_admin (username, password, nome, email) VALUES 
            ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@tiaozinho.com');
            ";
        }
        
        // Executar comandos SQL
        $commands = explode(';', $schema);
        $executedCommands = 0;
        
        foreach ($commands as $command) {
            $command = trim($command);
            if (!empty($command) && !preg_match('/^(--|\/\*|\*\/|DELIMITER)/', $command)) {
                try {
                    $pdo->exec($command);
                    $executedCommands++;
                } catch (PDOException $e) {
                    // Ignorar erros de comandos que já existem
                    if (strpos($e->getMessage(), 'already exists') === false && 
                        strpos($e->getMessage(), 'Duplicate entry') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        $success[] = "Estrutura do banco atualizada ({$executedCommands} comandos executados)";
        
        // Criar/atualizar arquivo config/database.php
        $configDir = __DIR__ . '/config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $dbConfig = "<?php
class Database {
    private \$host = 'localhost';
    private \$db_name = 'tiaozinh_pesquisa';
    private \$username = 'tiaozinh_pesquisa';
    private \$password = '1234qwer!@#\$QWER';
    public \$conn;

    public function getConnection() {
        \$this->conn = null;
        try {
            \$dsn = \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=utf8mb4\";
            
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\"
            ];
            
            \$this->conn = new PDO(\$dsn, \$this->username, \$this->password, \$options);
            
        } catch(PDOException \$exception) {
            error_log(\"Erro de conexão com banco de dados: \" . \$exception->getMessage());
            if (defined('DEBUG') && DEBUG === true) {
                echo \"Erro de conexão: \" . \$exception->getMessage();
            } else {
                echo \"Erro de conexão com o banco de dados. Tente novamente mais tarde.\";
            }
        }
        return \$this->conn;
    }
    
    public function testConnection() {
        try {
            \$conn = \$this->getConnection();
            if (\$conn !== null) {
                \$stmt = \$conn->query(\"SELECT 1\");
                return \$stmt !== false;
            }
            return false;
        } catch (Exception \$e) {
            error_log(\"Teste de conexão falhou: \" . \$e->getMessage());
            return false;
        }
    }
}
?>";
        
        file_put_contents($configDir . '/database.php', $dbConfig);
        $success[] = 'Arquivo config/database.php criado/atualizado';
        
        // Criar arquivo config/email.php básico se não existir
        if (!file_exists($configDir . '/email.php')) {
            $emailConfig = "<?php
// CONFIGURAÇÕES DE EMAIL - CONFIGURE COM SEUS DADOS REAIS
use PHPMailer\\PHPMailer\\PHPMailer;
use PHPMailer\\PHPMailer\\SMTP;
use PHPMailer\\PHPMailer\\Exception;

// Verificar se vendor existe
if (file_exists('../vendor/autoload.php')) {
    require '../vendor/autoload.php';
} else {
    // Função placeholder se PHPMailer não estiver instalado
    function enviarEmailAgradecimento(\$email, \$nome) {
        error_log(\"Email de agradecimento seria enviado para: \$email (\$nome)\");
        return false; // Retorna false até PHPMailer ser instalado
    }
    
    function enviarNotificacaoAdmin(\$data) {
        error_log(\"Notificação admin seria enviada para nova pesquisa de: \" . \$data['nome']);
        return false; // Retorna false até PHPMailer ser instalado
    }
    
    return; // Sair do arquivo se PHPMailer não estiver disponível
}

// IMPORTANTE: CONFIGURE ESTAS VARIÁVEIS COM SEUS DADOS REAIS
const SMTP_HOST = 'mail.tiaozinho.com';
const SMTP_PORT = 587;
const SMTP_USERNAME = 'pesquisa@tiaozinho.com';
const SMTP_PASSWORD = 'SuaSenhaEmail123!'; // ALTERE ESTA SENHA
const FROM_EMAIL = 'pesquisa@tiaozinho.com';
const FROM_NAME = 'Tiãozinho Supermercados';
const ADMIN_EMAIL = 'admin@tiaozinho.com';

function configurarPHPMailer() {
    \$mail = new PHPMailer(true);
    \$mail->isSMTP();
    \$mail->Host = SMTP_HOST;
    \$mail->SMTPAuth = true;
    \$mail->Username = SMTP_USERNAME;
    \$mail->Password = SMTP_PASSWORD;
    \$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    \$mail->Port = SMTP_PORT;
    \$mail->CharSet = 'UTF-8';
    \$mail->isHTML(true);
    return \$mail;
}

function enviarEmailAgradecimento(\$email, \$nome) {
    try {
        \$mail = configurarPHPMailer();
        \$mail->setFrom(FROM_EMAIL, FROM_NAME);
        \$mail->addAddress(\$email, \$nome);
        \$mail->Subject = 'Obrigado por participar da nossa pesquisa!';
        \$mail->Body = \"<h2>Obrigado, \" . htmlspecialchars(\$nome) . \"!</h2>
                       <p>Agradecemos sua participação em nossa pesquisa de satisfação.</p>
                       <p>Sua opinião é muito importante para nós!</p>
                       <p><strong>Equipe Tiãozinho Supermercados</strong></p>\";
        \$mail->send();
        return true;
    } catch (Exception \$e) {
        error_log(\"Erro ao enviar email: \" . \$e->getMessage());
        return false;
    }
}

function enviarNotificacaoAdmin(\$data) {
    try {
        \$mail = configurarPHPMailer();
        \$mail->setFrom(FROM_EMAIL, 'Sistema de Pesquisa');
        \$mail->addAddress(ADMIN_EMAIL);
        \$mail->Subject = \"Nova pesquisa - NPS: {\$data['nps']}\";
        \$mail->Body = \"<h2>Nova Pesquisa Recebida</h2>
                       <p><strong>Nome:</strong> \" . htmlspecialchars(\$data['nome']) . \"</p>
                       <p><strong>Email:</strong> \" . htmlspecialchars(\$data['email']) . \"</p>
                       <p><strong>NPS:</strong> {\$data['nps']}/10</p>
                       <p><strong>Loja:</strong> {\$data['loja_preferida']}</p>\";
        \$mail->send();
        return true;
    } catch (Exception \$e) {
        error_log(\"Erro ao enviar notificação: \" . \$e->getMessage());
        return false;
    }
}
?>";
            
            file_put_contents($configDir . '/email.php', $emailConfig);
            $success[] = 'Arquivo config/email.php criado (configure as credenciais de email)';
        }
        
        // Verificar se composer.json existe
        if (file_exists(__DIR__ . '/composer.json')) {
            $success[] = 'composer.json encontrado - Execute "composer install" para instalar dependências';
        } else {
            // Criar composer.json básico
            $composerJson = [
                "name" => "tiaozinho/pesquisa-satisfacao",
                "description" => "Sistema de Pesquisa de Satisfação - Tiãozinho Supermercados",
                "type" => "project",
                "require" => [
                    "php" => ">=7.4",
                    "phpmailer/phpmailer" => "^6.8",
                    "tecnickcom/tcpdf" => "^6.6"
                ],
                "autoload" => [
                    "psr-4" => [
                        "Tiaozinho\\Pesquisa\\" => "src/"
                    ]
                ]
            ];
            
            file_put_contents(__DIR__ . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $success[] = 'Arquivo composer.json criado - Execute "composer install"';
        }
        
        // Marcar como configurado
        file_put_contents(__DIR__ . '/.configured', date('Y-m-d H:i:s') . \"\\nBanco: tiaozinh_pesquisa\\nUsuário: tiaozinh_pesquisa\");
        $success[] = 'Configuração concluída com sucesso!';
        
    } catch (Exception $e) {
        $errors[] = 'Erro durante a configuração: ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang=\"pt-BR\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Setup - Sistema de Pesquisa Tiãozinho</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .container { 
            background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%; max-width: 600px; padding: 40px;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #667eea; font-size: 28px; margin-bottom: 10px; }
        .logo p { color: #666; font-size: 16px; }
        .setup-button { 
            width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: all 0.3s ease;
        }
        .setup-button:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info-box { 
            background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; 
            padding: 20px; margin-bottom: 30px;
        }
        .info-box h3 { color: #0c5460; margin-bottom: 15px; }
        .info-box p { color: #0c5460; margin-bottom: 10px; }
        .credentials { 
            background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; 
            padding: 20px; margin-bottom: 30px;
        }
        .credentials h3 { color: #856404; margin-bottom: 15px; }
        .credentials code { 
            background: #f8f9fa; padding: 2px 6px; border-radius: 4px; font-family: monospace;
        }
        .structure { 
            background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; 
            padding: 20px; margin-bottom: 30px; font-family: monospace; font-size: 14px;
        }
        .structure h3 { font-family: inherit; color: #495057; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"logo\">
            <h1>🛒 Tiãozinho Supermercados</h1>
            <p>Setup do Sistema de Pesquisa</p>
        </div>
        
        <div class=\"credentials\">
            <h3>✅ Credenciais Detectadas</h3>
            <p><strong>Banco:</strong> <code>tiaozinh_pesquisa</code></p>
            <p><strong>Usuário:</strong> <code>tiaozinh_pesquisa</code></p>
            <p><strong>Senha:</strong> <code>••••••••••••••••</code></p>
        </div>
        
        <div class=\"structure\">
            <h3>📁 Estrutura Esperada</h3>
            <pre>tiaozinho/
├── admin/
│   ├── api/
│   ├── index.html
│   └── login.php
├── api/
│   └── salvar-pesquisa.php
├── assets/
│   └── logo-tiaozinho.png
├── config/
│   ├── database.php
│   └── email.php
├── database/
│   └── schema.sql
├── index.html
├── politica-privacidade.html
├── .htaccess
└── composer.json</pre>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class=\"alert alert-error\">
                <strong>❌ Erros:</strong>
                <ul style=\"margin-top: 10px; margin-left: 20px;\">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class=\"alert alert-success\">
                <strong>✅ Sucesso:</strong>
                <ul style=\"margin-top: 10px; margin-left: 20px;\">
                    <?php foreach ($success as $msg): ?>
                        <li><?= htmlspecialchars($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (in_array('Configuração concluída com sucesso!', $success)): ?>
                    <div style=\"margin-top: 20px; padding: 15px; background: #155724; color: white; border-radius: 8px;\">
                        <h4>🎉 Sistema Configurado!</h4>
                        <p style=\"margin-top: 10px;\"><strong>Próximos passos:</strong></p>
                        <ol style=\"margin-top: 10px; margin-left: 20px;\">
                            <li>Execute <code style=\"background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px;\">composer install</code></li>
                            <li>Configure as credenciais de email em <code style=\"background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 4px;\">config/email.php</code></li>
                            <li>Acesse <a href=\"admin/\" style=\"color: #fff; text-decoration: underline;\">o painel administrativo</a></li>
                            <li>Login: <strong>admin</strong> / Senha: <strong>Admin123!</strong></li>
                        </ol>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!in_array('Configuração concluída com sucesso!', $success)): ?>
            <div class=\"info-box\">
                <h3>ℹ️ Sobre este Setup</h3>
                <p>Este script irá configurar automaticamente o sistema usando suas credenciais existentes.</p>
                <p>Ele criará as tabelas necessárias e os arquivos de configuração.</p>
            </div>
            
            <form method=\"POST\">
                <button type=\"submit\" class=\"setup-button\">🚀 Configurar Sistema</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
