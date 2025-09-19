<?php
// =====================================================
// admin/api/diagnostico.php
// Diagnóstico do sistema para identificar problemas
// =====================================================
header("Content-Type: application/json; charset=UTF-8");

$diagnostico = array();

// 1. Verificar versão do PHP
$diagnostico['php_version'] = phpversion();
$diagnostico['php_ok'] = version_compare(phpversion(), '7.0', '>=');

// 2. Verificar se sessões estão funcionando
session_start();
$_SESSION['teste'] = 'ok';
$diagnostico['sessions_ok'] = isset($_SESSION['teste']);

// 3. Verificar conexão com banco de dados
try {
    require_once '../../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    $diagnostico['database_ok'] = ($db !== null);
    $diagnostico['database_error'] = null;
} catch (Exception $e) {
    $diagnostico['database_ok'] = false;
    $diagnostico['database_error'] = $e->getMessage();
}

// 4. Verificar se PHPMailer existe
$diagnostico['phpmailer_exists'] = class_exists('PHPMailer\PHPMailer\PHPMailer');

// 5. Verificar se Composer autoload existe
$diagnostico['composer_autoload'] = file_exists('../../vendor/autoload.php');

// 6. Verificar permissões de escrita
$diagnostico['write_permissions'] = is_writable('.');

// 7. Verificar função mail()
$diagnostico['mail_function'] = function_exists('mail');

// 8. Verificar configurações importantes do PHP
$diagnostico['display_errors'] = ini_get('display_errors');
$diagnostico['log_errors'] = ini_get('log_errors');
$diagnostico['error_log'] = ini_get('error_log');

// 9. Verificar extensões necessárias
$diagnostico['extensions'] = array(
    'curl' => extension_loaded('curl'),
    'openssl' => extension_loaded('openssl'),
    'mbstring' => extension_loaded('mbstring'),
    'json' => extension_loaded('json')
);

// 10. Verificar se arquivos existem
$arquivos_importantes = [
    '../../config/database.php',
    '../index.html',
    './login.php',
    './check-session.php'
];

$diagnostico['arquivos'] = array();
foreach ($arquivos_importantes as $arquivo) {
    $diagnostico['arquivos'][$arquivo] = file_exists($arquivo);
}

echo json_encode($diagnostico, JSON_PRETTY_PRINT);
?>