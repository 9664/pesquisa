<?php
// =====================================================
// API SALVAR PESQUISA - VERSÃO CORRIGIDA
// Caminho: api/salvar-pesquisa.php
// =====================================================

// Headers de segurança e CORS
header("Access-Control-Allow-Origin: https://tiaozinho.com");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Método não permitido. Use POST."
    ]);
    exit();
}

// Incluir dependências (ajustado para estrutura de pastas)
require_once '../config/database.php';
require_once '../config/email.php';

/**
 * Função para validar e sanitizar dados de entrada
 */
function validarDados($data) {
    $erros = [];
    
    // Campos obrigatórios
    $camposObrigatorios = [
        'nome' => 'Nome completo',
        'email' => 'E-mail',
        'loja_preferida' => 'Loja preferida',
        'canais_promocao' => 'Canais de promoção',
        'nps' => 'NPS',
        'motivo_visita' => 'Motivo da visita',
        'encontrou_tudo' => 'Encontrou tudo',
        'aceite_lgpd' => 'Aceite LGPD'
    ];
    
    foreach ($camposObrigatorios as $campo => $nome) {
        if (empty($data[$campo])) {
            $erros[] = "Campo '{$nome}' é obrigatório";
        }
    }
    
    // Validações específicas
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido";
    }
    
    if (!empty($data['nps']) && (!is_numeric($data['nps']) || $data['nps'] < 0 || $data['nps'] > 10)) {
        $erros[] = "NPS deve ser um número entre 0 e 10";
    }
    
    // Validar avaliações (1-5)
    $avaliacoes = ['avaliacao_preco', 'avaliacao_fila', 'avaliacao_qualidade', 
                   'avaliacao_limpeza', 'avaliacao_atendimento', 'avaliacao_satisfacao'];
    
    foreach ($avaliacoes as $avaliacao) {
        if (!empty($data[$avaliacao]) && (!is_numeric($data[$avaliacao]) || $data[$avaliacao] < 1 || $data[$avaliacao] > 5)) {
            $erros[] = "Avaliação deve ser um número entre 1 e 5";
            break;
        }
    }
    
    return $erros;
}

/**
 * Função para verificar limite de pesquisas por IP
 */
function verificarLimitePorIP($db, $ip) {
    $query = "SELECT COUNT(*) FROM pesquisas 
              WHERE ip_address = :ip AND DATE(data_pesquisa) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':ip', $ip);
    $stmt->execute();
    
    $count = $stmt->fetch(PDO::FETCH_COLUMN);
    return $count < 5; // Limite de 5 pesquisas por IP por dia
}

/**
 * Função para registrar log
 */
function registrarLog($db, $tipo, $mensagem, $dados = null) {
    try {
        // Verificar se tabela de logs existe
        $checkTable = $db->query("SHOW TABLES LIKE 'logs_sistema'");
        if ($checkTable->rowCount() == 0) {
            // Se não existe, apenas fazer log no arquivo
            error_log("[{$tipo}] {$mensagem} - " . ($dados ? json_encode($dados) : ''));
            return;
        }
        
        $query = "INSERT INTO logs_sistema (tipo, modulo, mensagem, dados_extras, ip_address, user_agent) 
                  VALUES (:tipo, 'pesquisa', :mensagem, :dados, :ip, :user_agent)";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':tipo' => $tipo,
            ':mensagem' => $mensagem,
            ':dados' => $dados ? json_encode($dados) : null,
            ':ip' => $_SERVER['REMOTE_ADDR'],
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

try {
    // Receber e sanitizar dados
    $data = [
        'nome' => trim($_POST['nome'] ?? ''),
        'email' => trim(strtolower($_POST['email'] ?? '')),
        'telefone' => trim($_POST['telefone'] ?? ''),
        'loja_preferida' => $_POST['loja_preferida'] ?? '',
        'canais_promocao' => isset($_POST['canais_promocao']) ? 
            (is_array($_POST['canais_promocao']) ? implode(',', $_POST['canais_promocao']) : $_POST['canais_promocao']) : '',
        'outros_canais_especificar' => trim($_POST['outros_canais_especificar'] ?? ''),
        'nps' => isset($_POST['nps']) ? (int)$_POST['nps'] : null,
        'motivo_visita' => $_POST['motivo_visita'] ?? '',
        'encontrou_tudo' => $_POST['encontrou_tudo'] ?? '',
        'o_que_faltou' => trim($_POST['o_que_faltou'] ?? ''),
        'avaliacao_preco' => isset($_POST['avaliacao_preco']) ? (int)$_POST['avaliacao_preco'] : null,
        'avaliacao_fila' => isset($_POST['avaliacao_fila']) ? (int)$_POST['avaliacao_fila'] : null,
        'avaliacao_qualidade' => isset($_POST['avaliacao_qualidade']) ? (int)$_POST['avaliacao_qualidade'] : null,
        'avaliacao_limpeza' => isset($_POST['avaliacao_limpeza']) ? (int)$_POST['avaliacao_limpeza'] : null,
        'avaliacao_atendimento' => isset($_POST['avaliacao_atendimento']) ? (int)$_POST['avaliacao_atendimento'] : null,
        'avaliacao_satisfacao' => isset($_POST['avaliacao_satisfacao']) ? (int)$_POST['avaliacao_satisfacao'] : null,
        'voltar_amanha' => trim($_POST['voltar_amanha'] ?? ''),
        'receber_ofertas' => isset($_POST['receber_ofertas']) ? 1 : 0,
        'aceite_lgpd' => isset($_POST['aceite_lgpd']) ? 1 : 0,
        'data_pesquisa' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR']
    ];
    
    // Validar dados
    $erros = validarDados($data);
    if (!empty($erros)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "Dados inválidos",
            "errors" => $erros
        ]);
        exit();
    }
    
    // Conectar ao banco
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("Erro de conexão com banco de dados");
    }
    
    // Verificar limite por IP
    if (!verificarLimitePorIP($db, $data['ip_address'])) {
        registrarLog($db, 'warning', 'Limite de pesquisas por IP excedido', ['ip' => $data['ip_address']]);
        
        http_response_code(429);
        echo json_encode([
            "success" => false,
            "message" => "Limite de pesquisas por dia excedido. Tente novamente amanhã."
        ]);
        exit();
    }
    
    // Verificar se o sistema está em manutenção (se tabela configuracoes existir)
    try {
        $configQuery = "SELECT valor FROM configuracoes WHERE chave = 'manutencao_mode'";
        $configStmt = $db->prepare($configQuery);
        $configStmt->execute();
        $manutencao = $configStmt->fetch(PDO::FETCH_COLUMN);
        
        if ($manutencao === 'true') {
            http_response_code(503);
            echo json_encode([
                "success" => false,
                "message" => "Sistema em manutenção. Tente novamente mais tarde."
            ]);
            exit();
        }
    } catch (Exception $e) {
        // Se tabela configuracoes não existir, continuar normalmente
    }
    
    // Iniciar transação
    $db->beginTransaction();
    
    // Preparar query de inserção
    $query = "INSERT INTO pesquisas 
              (nome, email, telefone, loja_preferida, canais_promocao, outros_canais_especificar,
               nps, motivo_visita, encontrou_tudo, o_que_faltou,
               avaliacao_preco, avaliacao_fila, avaliacao_qualidade, avaliacao_limpeza,
               avaliacao_atendimento, avaliacao_satisfacao, voltar_amanha, receber_ofertas,
               aceite_lgpd, data_pesquisa, ip_address)
              VALUES 
              (:nome, :email, :telefone, :loja_preferida, :canais_promocao, :outros_canais_especificar,
               :nps, :motivo_visita, :encontrou_tudo, :o_que_faltou,
               :avaliacao_preco, :avaliacao_fila, :avaliacao_qualidade, :avaliacao_limpeza,
               :avaliacao_atendimento, :avaliacao_satisfacao, :voltar_amanha, :receber_ofertas,
               :aceite_lgpd, :data_pesquisa, :ip_address)";
    
    $stmt = $db->prepare($query);
    
    // Executar inserção
    $resultado = $stmt->execute($data);
    
    if (!$resultado) {
        throw new Exception("Erro ao salvar pesquisa no banco de dados");
    }
    
    $pesquisaId = $db->lastInsertId();
    
    // Commit da transação
    $db->commit();
    
    // Registrar log de sucesso
    registrarLog($db, 'info', 'Pesquisa salva com sucesso', [
        'pesquisa_id' => $pesquisaId,
        'nome' => $data['nome'],
        'email' => $data['email'],
        'nps' => $data['nps']
    ]);
    
    // Verificar configurações de email (se tabela existir)
    $emailConfig = [
        'enviar_email_agradecimento' => 'true',
        'enviar_notificacao_admin' => 'true'
    ];
    
    try {
        $emailConfigQuery = "SELECT chave, valor FROM configuracoes WHERE categoria = 'email' OR chave IN ('enviar_email_agradecimento', 'enviar_notificacao_admin')";
        $emailConfigStmt = $db->prepare($emailConfigQuery);
        $emailConfigStmt->execute();
        
        while ($row = $emailConfigStmt->fetch(PDO::FETCH_ASSOC)) {
            $emailConfig[$row['chave']] = $row['valor'];
        }
    } catch (Exception $e) {
        // Se tabela não existir, usar configurações padrão
    }
    
    // Enviar emails se configurado
    $emailResults = [
        'agradecimento' => false,
        'notificacao' => false
    ];
    
    // Email de agradecimento
    if (($emailConfig['enviar_email_agradecimento'] ?? 'true') === 'true') {
        try {
            $emailResults['agradecimento'] = enviarEmailAgradecimento($data['email'], $data['nome']);
            if ($emailResults['agradecimento']) {
                registrarLog($db, 'info', 'Email de agradecimento enviado', ['email' => $data['email']]);
            }
        } catch (Exception $e) {
            registrarLog($db, 'error', 'Erro ao enviar email de agradecimento', [
                'email' => $data['email'],
                'erro' => $e->getMessage()
            ]);
        }
    }
    
    // Notificação para admin
    if (($emailConfig['enviar_notificacao_admin'] ?? 'true') === 'true') {
        try {
            $emailResults['notificacao'] = enviarNotificacaoAdmin($data);
            if ($emailResults['notificacao']) {
                registrarLog($db, 'info', 'Notificação admin enviada');
            }
        } catch (Exception $e) {
            registrarLog($db, 'error', 'Erro ao enviar notificação admin', ['erro' => $e->getMessage()]);
        }
    }
    
    // Resposta de sucesso
    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Pesquisa salva com sucesso! Obrigado pela sua participação.",
        "data" => [
            "id" => $pesquisaId,
            "emails_enviados" => $emailResults
        ]
    ]);
    
} catch (PDOException $e) {
    // Rollback em caso de erro de banco
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    if (isset($db)) {
        registrarLog($db, 'error', 'Erro de banco de dados ao salvar pesquisa', [
            'erro' => $e->getMessage(),
            'codigo' => $e->getCode()
        ]);
    }
    
    error_log("Erro PDO ao salvar pesquisa: " . $e->getMessage());
    
    http_response_code(503);
    echo json_encode([
        "success" => false,
        "message" => "Erro interno do servidor. Tente novamente mais tarde."
    ]);
    
} catch (Exception $e) {
    // Rollback em caso de erro geral
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    if (isset($db)) {
        registrarLog($db, 'error', 'Erro geral ao salvar pesquisa', [
            'erro' => $e->getMessage()
        ]);
    }
    
    error_log("Erro ao processar pesquisa: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao processar pesquisa. Tente novamente mais tarde."
    ]);
}
?>
