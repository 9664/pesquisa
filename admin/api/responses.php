<?php
// =====================================================
 // admin/api/responses.php
// =====================================================
session_start();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("error" => "Não autorizado"));
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Parâmetros de filtro
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

$dateStart = $_GET['date_start'] ?? null;
$dateEnd = $_GET['date_end'] ?? null;
$npsFilter = $_GET['nps_filter'] ?? null;
$lojaFilter = $_GET['loja_filter'] ?? null;
$search = $_GET['search'] ?? null;

// Construir query com filtros
$query = "SELECT 
          id, nome, email, telefone, loja_preferida, canais_promocao, 
          outros_canais_especificar, nps, motivo_visita,
          encontrou_tudo, o_que_faltou, avaliacao_preco, avaliacao_fila,
          avaliacao_qualidade, avaliacao_limpeza, avaliacao_atendimento,
          avaliacao_satisfacao, voltar_amanha, receber_ofertas,
          DATE_FORMAT(data_pesquisa, '%d/%m/%Y %H:%i') as data_formatada
          FROM pesquisas
          WHERE 1=1";

$params = array();

if ($dateStart) {
    $query .= " AND DATE(data_pesquisa) >= :date_start";
    $params[':date_start'] = $dateStart;
}

if ($dateEnd) {
    $query .= " AND DATE(data_pesquisa) <= :date_end";
    $params[':date_end'] = $dateEnd;
}

if ($npsFilter === 'promotor') {
    $query .= " AND nps >= 9";
} elseif ($npsFilter === 'neutro') {
    $query .= " AND nps >= 7 AND nps <= 8";
} elseif ($npsFilter === 'detrator') {
    $query .= " AND nps <= 6";
}

if ($lojaFilter) {
    $query .= " AND loja_preferida = :loja_filter";
    $params[':loja_filter'] = $lojaFilter;
}

if ($search) {
    $query .= " AND (nome LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

// Count total para paginação
$countQuery = str_replace("SELECT id, nome, email, telefone, loja_preferida, canais_promocao, 
          outros_canais_especificar, nps, motivo_visita,
          encontrou_tudo, o_que_faltou, avaliacao_preco, avaliacao_fila,
          avaliacao_qualidade, avaliacao_limpeza, avaliacao_atendimento,
          avaliacao_satisfacao, voltar_amanha, receber_ofertas,
          DATE_FORMAT(data_pesquisa, '%d/%m/%Y %H:%i') as data_formatada", 
          "SELECT COUNT(*)", $query);

$stmt = $db->prepare($countQuery);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_COLUMN);

// Query com limit e offset
$query .= " ORDER BY data_pesquisa DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Adicionar classificação NPS e formatar loja
foreach ($responses as &$response) {
    if ($response['nps'] >= 9) {
        $response['nps_class'] = 'promotor';
        $response['nps_label'] = 'Promotor';
    } elseif ($response['nps'] >= 7) {
        $response['nps_class'] = 'neutro';
        $response['nps_label'] = 'Neutro';
    } else {
        $response['nps_class'] = 'detrator';
        $response['nps_label'] = 'Detrator';
    }
    
    // Formatar nome da loja
    $response['loja_nome'] = str_replace('_', ' ', ucfirst($response['loja_preferida']));
}

echo json_encode(array(
    'data' => $responses,
    'total' => $total,
    'page' => $page,
    'pages' => ceil($total / $limit)
));
?>