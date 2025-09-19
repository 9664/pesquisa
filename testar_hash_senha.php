<?php
// =====================================================
// TESTE SIMPLES DO HASH DA SENHA
// =====================================================

echo "<h2>🔍 Teste do Hash da Senha</h2>";

// Dados do teste
$senhaCorreta = 'Admin123!';
$hashAtual = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "<h3>📋 Informações:</h3>";
echo "<p><strong>Senha esperada:</strong> <code>$senhaCorreta</code></p>";
echo "<p><strong>Hash no banco:</strong> <code>$hashAtual</code></p>";

// Testar verificação
$testeVerify = password_verify($senhaCorreta, $hashAtual);
echo "<p><strong>Resultado do password_verify():</strong> " . ($testeVerify ? '✅ SUCESSO' : '❌ FALHOU') . "</p>";

// Gerar novo hash para comparação
$novoHash = password_hash($senhaCorreta, PASSWORD_DEFAULT);
echo "<p><strong>Novo hash gerado:</strong> <code>$novoHash</code></p>";

// Testar o novo hash
$testeNovoHash = password_verify($senhaCorreta, $novoHash);
echo "<p><strong>Teste do novo hash:</strong> " . ($testeNovoHash ? '✅ SUCESSO' : '❌ FALHOU') . "</p>";

echo "<hr>";

// Testar conexão com banco
echo "<h3>🗄️ Teste de Conexão com Banco:</h3>";
try {
    $dsn = "mysql:host=localhost;dbname=tiaozinh_pesquisa;charset=utf8mb4";
    $pdo = new PDO($dsn, 'tiaozinh_pesquisa', '1234qwer!@#$QWER');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Conexão com banco: <strong>OK</strong></p>";
    
    // Verificar usuário admin
    $stmt = $pdo->prepare("SELECT username, password FROM usuarios_admin WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p>✅ Usuário admin encontrado</p>";
        echo "<p><strong>Hash no banco:</strong> <code>{$user['password']}</code></p>";
        
        // Testar senha com hash do banco
        $testeBanco = password_verify($senhaCorreta, $user['password']);
        echo "<p><strong>Teste com hash do banco:</strong> " . ($testeBanco ? '✅ SUCESSO' : '❌ FALHOU') . "</p>";
        
        if (!$testeBanco) {
            echo "<p style='color: red;'>⚠️ <strong>PROBLEMA ENCONTRADO:</strong> O hash no banco não confere com a senha!</p>";
            echo "<p><strong>Solução:</strong> Execute este comando SQL no phpMyAdmin:</p>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
            echo "UPDATE usuarios_admin SET password = '$novoHash' WHERE username = 'admin';";
            echo "</pre>";
        }
    } else {
        echo "<p>❌ Usuário admin não encontrado no banco</p>";
        echo "<p><strong>Solução:</strong> Execute este comando SQL no phpMyAdmin:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
        echo "INSERT INTO usuarios_admin (username, password, nome, email) VALUES ('admin', '$novoHash', 'Administrador', 'admin@tiaozinho.com');";
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro de conexão: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Verificar arquivo config/database.php
echo "<h3>📁 Verificação de Arquivos:</h3>";
$arquivos = [
    'config/database.php' => '../config/database.php',
    'admin/login.php' => '../admin/login.php',
    'admin/index.html' => '../admin/index.html'
];

foreach ($arquivos as $nome => $caminho) {
    if (file_exists($caminho)) {
        echo "<p>✅ $nome: <strong>Existe</strong></p>";
    } else {
        echo "<p>❌ $nome: <strong>Não encontrado</strong></p>";
    }
}

echo "<hr>";
echo "<h3>🚀 Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Se o hash não confere, execute o comando SQL mostrado acima</li>";
echo "<li>Substitua o arquivo admin/login.php pelo conteúdo corrigido</li>";
echo "<li>Teste o login novamente</li>";
echo "</ol>";

echo "<p style='margin-top: 20px;'><a href='admin/'>← Voltar ao Login</a></p>";
?>
