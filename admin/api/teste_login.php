<?php
require_once '../../config/database.php';

echo "<h2>Teste de Login</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Conexão com banco OK<br>";
        
        // Verificar usuários
        $stmt = $db->query("SELECT id, username, nome, ativo FROM usuarios_admin");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Usuários encontrados:<br>";
        foreach ($users as $user) {
            echo "- ID: {$user['id']}, User: {$user['username']}, Nome: {$user['nome']}, Ativo: {$user['ativo']}<br>";
        }
        
        // Testar senha
        echo "<br>Testando senha 'secret':<br>";
        $testHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        if (password_verify('secret', $testHash)) {
            echo "✅ Senha 'secret' é válida<br>";
        } else {
            echo "❌ Senha 'secret' inválida<br>";
        }
        
        // Formulário de teste
        echo '<br><form method="POST" action="login.php">';
        echo '<input type="text" name="username" placeholder="admin" required><br><br>';
        echo '<input type="password" name="password" placeholder="secret" required><br><br>';
        echo '<button type="submit">Testar Login</button>';
        echo '</form>';
        
    } else {
        echo "❌ Erro de conexão com banco";
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>