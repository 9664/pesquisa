<?php
// =====================================================
// LOGIN FUNCIONAL PARA ADMIN/LOGIN.PHP
// =====================================================
session_start();

// Incluir conexão com banco
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Log para debug
    error_log("Tentativa de login - Usuário: $username");
    
    if (empty($username) || empty($password)) {
        $error = 'Usuário e senha são obrigatórios';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if (!$db) {
                throw new Exception('Erro de conexão com banco');
            }
            
            // Buscar usuário
            $stmt = $db->prepare("SELECT id, username, password, nome, email FROM usuarios_admin WHERE username = ? AND ativo = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log para debug
            error_log("Usuário encontrado: " . ($user ? 'SIM' : 'NÃO'));
            
            if ($user) {
                // Log do hash para debug
                error_log("Hash no banco: " . $user['password']);
                error_log("Senha digitada: $password");
                
                // Verificar senha
                if (password_verify($password, $user['password'])) {
                    // Login válido
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_nome'] = $user['nome'];
                    
                    // Atualizar último acesso
                    $updateStmt = $db->prepare("UPDATE usuarios_admin SET ultimo_acesso = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Log de sucesso
                    error_log("Login bem-sucedido para: $username");
                    
                    // Redirecionar para dashboard
                    header('Location: index.html');
                    exit;
                } else {
                    $error = 'Senha incorreta';
                    error_log("Senha incorreta para usuário: $username");
                }
            } else {
                $error = 'Usuário não encontrado';
                error_log("Usuário não encontrado: $username");
            }
        } catch (Exception $e) {
            $error = 'Erro interno: ' . $e->getMessage();
            error_log("Erro no login: " . $e->getMessage());
        }
    }
}

// Teste direto da senha (para debug)
if (isset($_GET['debug']) && $_GET['debug'] === 'senha') {
    $senhaCorreta = 'Admin123!';
    $hashBanco = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    $testeVerify = password_verify($senhaCorreta, $hashBanco);
    
    echo "<h3>Debug da Senha:</h3>";
    echo "Senha: $senhaCorreta<br>";
    echo "Hash: $hashBanco<br>";
    echo "Verificação: " . ($testeVerify ? 'OK' : 'FALHOU') . "<br>";
    echo "<hr>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Administrativo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .login-container { 
            background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            width: 100%; max-width: 400px; padding: 40px;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo img { width: 150px; height: auto; margin-bottom: 15px; }
        .logo h1 { color: #333; font-size: 24px; margin-bottom: 5px; }
        .logo p { color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: bold; }
        .form-group input { 
            width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 8px; 
            font-size: 16px; transition: border-color 0.3s;
        }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .login-button { 
            width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold;
            cursor: pointer; transition: all 0.3s ease;
        }
        .login-button:hover { transform: translateY(-2px); }
        .error { 
            background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; 
            margin-bottom: 20px; text-align: center;
        }
        .credentials { 
            background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 8px; 
            margin-bottom: 20px; text-align: center; font-size: 14px;
        }
        .debug { 
            background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; 
            margin-bottom: 15px; font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="../assets/logo-tiaozinho.png" alt="Tiãozinho Supermercados" onerror="this.style.display='none'">
            <h1>Tiãozinho Supermercados</h1>
            <p>Painel Administrativo</p>
        </div>
        
        <div class="credentials">
            <strong>Credenciais de Teste:</strong><br>
            Usuário: <code>admin</code><br>
            Senha: <code>Admin123!</code>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
                <br><small>Verifique os logs para mais detalhes</small>
            </div>
        <?php endif; ?>
        
        <div class="debug">
            <strong>Debug:</strong> <a href="?debug=senha">Testar Hash da Senha</a>
        </div>
        
        <form method="POST">
            <div class="form-group">
                <label>Usuário</label>
                <input type="text" name="username" value="admin" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" placeholder="Digite: Admin123!" required>
            </div>
            <button type="submit" class="login-button">Entrar</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
            <p>Problemas? Verifique:</p>
            <p>1. Se o arquivo config/database.php existe</p>
            <p>2. Se a tabela usuarios_admin tem dados</p>
            <p>3. Os logs de erro do servidor</p>
        </div>
    </div>
</body>
</html>
