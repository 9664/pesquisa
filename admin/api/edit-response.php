<?php
<?php
// =====================================================
// admin/api/edit-response.php
// =====================================================
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("success" => false, "message" => "Não autorizado"));
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// GET: Buscar dados de uma pesquisa
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        echo json_encode(array("success" => false, "message" => "ID não fornecido"));
        exit();
    }
    
    $query = "SELECT * FROM pesquisas WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(array("success" => true, "data" => $data));
    } else {
        echo json_encode(array("success" => false, "message" => "Pesquisa não encontrada"));
    }
    exit();
}

// POST: Atualizar dados de uma pesquisa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $loja_preferida = $_POST['loja_preferida'] ?? '';
    $nps = $_POST['nps'] ?? '';
    $motivo_visita = $_POST['motivo_visita'] ?? '';
    $encontrou_tudo = $_POST['encontrou_tudo'] ?? '';
    $o_que_faltou = $_POST['o_que_faltou'] ?? '';
    $avaliacao_preco = $_POST['avaliacao_preco'] ?? null;
    $avaliacao_fila = $_POST['avaliacao_fila'] ?? null;
    $avaliacao_qualidade = $_POST['avaliacao_qualidade'] ?? null;
    $avaliacao_limpeza = $_POST['avaliacao_limpeza'] ?? null;
    $avaliacao_atendimento = $_POST['avaliacao_atendimento'] ?? null;
    $avaliacao_satisfacao = $_POST['avaliacao_satisfacao'] ?? null;
    $canais_promocao = $_POST['canais_promocao'] ?? '';
    $outros_canais_especificar = $_POST['outros_canais_especificar'] ?? '';
    $voltar_amanha = $_POST['voltar_amanha'] ?? '';
    $receber_ofertas = isset($_POST['receber_ofertas']) ? 1 : 0;
    
    if (empty($id) || empty($nome) || empty($email) || empty($nps)) {
        echo json_encode(array("success" => false, "message" => "Campos obrigatórios faltando"));
        exit();
    }
    
    // Verificar se a pesquisa existe
    $checkQuery = "SELECT id FROM pesquisas WHERE id = :id";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':id', $id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(array("success" => false, "message" => "Pesquisa não encontrada"));
        exit();
    }
    
    try {
        $query = "UPDATE pesquisas SET
                  nome = :nome,
                  email = :email,
                  telefone = :telefone,
                  loja_preferida = :loja_preferida,
                  nps = :nps,
                  motivo_visita = :motivo_visita,
                  encontrou_tudo = :encontrou_tudo,
                  o_que_faltou = :o_que_faltou,
                  avaliacao_preco = :avaliacao_preco,
                  avaliacao_fila = :avaliacao_fila,
                  avaliacao_qualidade = :avaliacao_qualidade,
                  avaliacao_limpeza = :avaliacao_limpeza,
                  avaliacao_atendimento = :avaliacao_atendimento,
                  avaliacao_satisfacao = :avaliacao_satisfacao,
                  canais_promocao = :canais_promocao,
                  outros_canais_especificar = :outros_canais_especificar,
                  voltar_amanha = :voltar_amanha,
                  receber_ofertas = :receber_ofertas
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':nome', $nome);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':telefone', $telefone);
        $stmt->bindParam(':loja_preferida', $loja_preferida);
        $stmt->bindParam(':nps', $nps);
        $stmt->bindParam(':motivo_visita', $motivo_visita);
        $stmt->bindParam(':encontrou_tudo', $encontrou_tudo);
        $stmt->bindParam(':o_que_faltou', $o_que_faltou);
        $stmt->bindParam(':avaliacao_preco', $avaliacao_preco);
        $stmt->bindParam(':avaliacao_fila', $avaliacao_fila);
        $stmt->bindParam(':avaliacao_qualidade', $avaliacao_qualidade);
        $stmt->bindParam(':avaliacao_limpeza', $avaliacao_limpeza);
        $stmt->bindParam(':avaliacao_atendimento', $avaliacao_atendimento);
        $stmt->bindParam(':avaliacao_satisfacao', $avaliacao_satisfacao);
        $stmt->bindParam(':canais_promocao', $canais_promocao);
        $stmt->bindParam(':outros_canais_especificar', $outros_canais_especificar);
        $stmt->bindParam(':voltar_amanha', $voltar_amanha);
        $stmt->bindParam(':receber_ofertas', $receber_ofertas);
        
        $stmt->execute();
        
        // Log da ação
        $logQuery = "INSERT INTO log_acoes (usuario_id, acao, detalhes, created_at) 
                    VALUES (:usuario_id, 'EDIT_RESPONSE', :detalhes, NOW())";
        $logStmt = $db->prepare($logQuery);
        $logStmt->bindParam(':usuario_id', $_SESSION['admin_id']);
        $detalhes = "Editou pesquisa ID: {$id} - {$nome} ({$email})";
        $logStmt->bindParam(':detalhes', $detalhes);
        $logStmt->execute();
        
        echo json_encode(array(
            "success" => true,
            "message" => "Pesquisa atualizada com sucesso"
        ));
        
    } catch (Exception $e) {
        error_log("Erro ao atualizar pesquisa: " . $e->getMessage());
        echo json_encode(array(
            "success" => false,
            "message" => "Erro ao atualizar pesquisa: " . $e->getMessage()
        ));
    }
    exit();
}

http_response_code(405);
echo json_encode(array("success" => false, "message" => "Método não permitido"));
?>