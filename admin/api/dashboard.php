<?php
// =====================================================
 // admin/api/dashboard.php
// =====================================================
session_start();
header("Content-Type: application/json; charset=UTF-8");

// Verificar se está logado
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(array("error" => "Não autorizado"));
    exit();
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Função para calcular NPS
function calcularNPS($db) {
    $query = "SELECT 
              SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) as promotores,
              SUM(CASE WHEN nps >= 7 AND nps <= 8 THEN 1 ELSE 0 END) as neutros,
              SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END) as detratores,
              COUNT(*) as total
              FROM pesquisas
              WHERE data_pesquisa >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['total'] > 0) {
        $nps = (($result['promotores'] / $result['total']) - ($result['detratores'] / $result['total'])) * 100;
        return array(
            'score' => round($nps),
            'promotores' => $result['promotores'],
            'neutros' => $result['neutros'],
            'detratores' => $result['detratores'],
            'total' => $result['total']
        );
    }
    
    return array('score' => 0, 'promotores' => 0, 'neutros' => 0, 'detratores' => 0, 'total' => 0);
}

// Estatísticas gerais
$stats = array();

// Total de respostas
$query = "SELECT COUNT(*) as total FROM pesquisas";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['totalResponses'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// NPS
$npsData = calcularNPS($db);
$stats['nps'] = $npsData;

// Satisfação média
$query = "SELECT AVG(avaliacao_satisfacao) as media FROM pesquisas WHERE avaliacao_satisfacao IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['avgSatisfaction'] = round($stmt->fetch(PDO::FETCH_ASSOC)['media'], 1);

// Taxa de conversão (encontrou tudo)
$query = "SELECT 
          SUM(CASE WHEN encontrou_tudo = 'sim' THEN 1 ELSE 0 END) as sim,
          COUNT(*) as total
          FROM pesquisas";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$stats['conversionRate'] = $result['total'] > 0 ? round(($result['sim'] / $result['total']) * 100) : 0;

// NPS ao longo do tempo (últimos 6 meses)
$query = "SELECT 
          DATE_FORMAT(data_pesquisa, '%Y-%m') as mes,
          AVG(nps) as nps_medio
          FROM pesquisas
          WHERE data_pesquisa >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY DATE_FORMAT(data_pesquisa, '%Y-%m')
          ORDER BY mes";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['npsOverTime'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Avaliações por categoria
$query = "SELECT 
          AVG(avaliacao_preco) as preco,
          AVG(avaliacao_fila) as fila,
          AVG(avaliacao_qualidade) as qualidade,
          AVG(avaliacao_limpeza) as limpeza,
          AVG(avaliacao_atendimento) as atendimento,
          AVG(avaliacao_satisfacao) as satisfacao
          FROM pesquisas";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['ratings'] = $stmt->fetch(PDO::FETCH_ASSOC);

// Motivos de visita
$query = "SELECT 
          motivo_visita,
          COUNT(*) as total
          FROM pesquisas
          WHERE motivo_visita IS NOT NULL
          GROUP BY motivo_visita
          ORDER BY total DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['visitReasons'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Distribuição por loja
$query = "SELECT 
          loja_preferida,
          COUNT(*) as total
          FROM pesquisas
          WHERE loja_preferida IS NOT NULL
          GROUP BY loja_preferida
          ORDER BY loja_preferida";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['storeDistribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Canais de comunicação (precisa processar as strings separadas por vírgula)
$query = "SELECT canais_promocao FROM pesquisas WHERE canais_promocao IS NOT NULL AND canais_promocao != ''";
$stmt = $db->prepare($query);
$stmt->execute();
$allChannels = $stmt->fetchAll(PDO::FETCH_COLUMN);

$channelCount = array();
foreach ($allChannels as $channels) {
    $channelArray = explode(',', $channels);
    foreach ($channelArray as $channel) {
        $channel = trim($channel);
        if (!empty($channel)) {
            if (!isset($channelCount[$channel])) {
                $channelCount[$channel] = 0;
            }
            $channelCount[$channel]++;
        }
    }
}

$stats['communicationChannels'] = array();
foreach ($channelCount as $channel => $count) {
    $stats['communicationChannels'][] = array(
        'channel' => $channel,
        'total' => $count
    );
}

echo json_encode($stats);
?>