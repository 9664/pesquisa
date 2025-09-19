<?php
// =====================================================
 // admin/api/report-pdf.php
// =====================================================
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo "Não autorizado";
    exit();
}

require_once '../../config/database.php';
require_once '../../vendor/autoload.php'; // TCPDF via Composer

use TCPDF;

$database = new Database();
$db = $database->getConnection();

// Criar PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurações do documento
$pdf->SetCreator('Sistema de Pesquisa Tiãozinho');
$pdf->SetAuthor('Tiãozinho Supermercados');
$pdf->SetTitle('Relatório de Pesquisa de Satisfação');
$pdf->SetSubject('Relatório Mensal');

// Remover header e footer padrão
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Adicionar página
$pdf->AddPage();

// Logo
$logo_path = $_SERVER['DOCUMENT_ROOT'] . '/assets/logo-tiaozinho.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 85, 10, 40, 0, 'PNG');
    $pdf->Ln(25);
}

// Título
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 15, 'Tiãozinho Supermercados', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 14);
$pdf->Cell(0, 10, 'Relatório de Pesquisa de Satisfação', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i'), 0, 1, 'C');

$pdf->Ln(10);

// Estatísticas gerais
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Estatísticas Gerais', 0, 1);
$pdf->SetFont('helvetica', '', 11);

// Total de respostas
$query = "SELECT COUNT(*) as total FROM pesquisas";
$stmt = $db->prepare($query);
$stmt->execute();
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$pdf->Cell(0, 8, 'Total de Respostas: ' . $total, 0, 1);

// NPS
$query = "SELECT 
          SUM(CASE WHEN nps >= 9 THEN 1 ELSE 0 END) as promotores,
          SUM(CASE WHEN nps >= 7 AND nps <= 8 THEN 1 ELSE 0 END) as neutros,
          SUM(CASE WHEN nps <= 6 THEN 1 ELSE 0 END) as detratores,
          COUNT(*) as total
          FROM pesquisas";
$stmt = $db->prepare($query);
$stmt->execute();
$npsData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($npsData['total'] > 0) {
    $npsScore = (($npsData['promotores'] / $npsData['total']) - ($npsData['detratores'] / $npsData['total'])) * 100;
    $pdf->Cell(0, 8, 'NPS Score: ' . round($npsScore), 0, 1);
    $pdf->Cell(0, 8, '  - Promotores: ' . $npsData['promotores'] . ' (' . round($npsData['promotores']/$npsData['total']*100) . '%)', 0, 1);
    $pdf->Cell(0, 8, '  - Neutros: ' . $npsData['neutros'] . ' (' . round($npsData['neutros']/$npsData['total']*100) . '%)', 0, 1);
    $pdf->Cell(0, 8, '  - Detratores: ' . $npsData['detratores'] . ' (' . round($npsData['detratores']/$npsData['total']*100) . '%)', 0, 1);
}

$pdf->Ln(10);

// Médias de avaliação
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Médias de Avaliação', 0, 1);
$pdf->SetFont('helvetica', '', 11);

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
$avaliacoes = $stmt->fetch(PDO::FETCH_ASSOC);

$pdf->Cell(0, 8, 'Preço: ' . number_format($avaliacoes['preco'], 1) . '/5', 0, 1);
$pdf->Cell(0, 8, 'Tempo de Fila: ' . number_format($avaliacoes['fila'], 1) . '/5', 0, 1);
$pdf->Cell(0, 8, 'Qualidade dos Produtos: ' . number_format($avaliacoes['qualidade'], 1) . '/5', 0, 1);
$pdf->Cell(0, 8, 'Limpeza e Sinalização: ' . number_format($avaliacoes['limpeza'], 1) . '/5', 0, 1);
$pdf->Cell(0, 8, 'Atendimento: ' . number_format($avaliacoes['atendimento'], 1) . '/5', 0, 1);
$pdf->Cell(0, 8, 'Satisfação Geral: ' . number_format($avaliacoes['satisfacao'], 1) . '/5', 0, 1);

// Salvar PDF
$pdf->Output('relatorio_pesquisa_' . date('Y-m-d') . '.pdf', 'D');
?>