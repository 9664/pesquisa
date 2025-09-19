<?php
$senha = 'minhasenha123';  // ← Mude para sua senha desejada
$hash = password_hash($senha, PASSWORD_DEFAULT);

echo "Senha: $senha<br>";
echo "Hash: $hash<br><br>";

echo "Execute este SQL:<br>";
echo "<pre>UPDATE usuarios_admin SET password = '$hash' WHERE username = 'admin';</pre>";
?>