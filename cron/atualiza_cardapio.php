<?php
/**
 * Cron semanal — Atualiza cardápio dos Restaurantes Populares de BH
 * Fonte: https://prefeitura.pbh.gov.br/seguranca-alimentar-nutricional/equipamentos/restaurantes-populares
 * Agendamento: toda segunda-feira às 6h (ver docker-compose.yml)
 *
 * Execução manual: php /var/www/html/cron/atualiza_cardapio.php
 */

define('CRON_ROOT', dirname(__DIR__));
require_once CRON_ROOT . '/conexao.php';

const URL_CARDAPIO = 'https://prefeitura.pbh.gov.br/seguranca-alimentar-nutricional/equipamentos/restaurantes-populares';
const CIDADE       = 'Belo Horizonte';
const ESTADO       = 'MG';

$log_dir = CRON_ROOT . '/cron/logs';
if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

function log_msg(string $msg): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents(CRON_ROOT . '/cron/logs/cardapio.log', $line, FILE_APPEND);
    echo $line;
}

// ── 1. Fetch da página ────────────────────────────────────────────────────────
log_msg('Iniciando atualização do cardápio — ' . URL_CARDAPIO);

$ctx = stream_context_create(['http' => [
    'timeout'     => 20,
    'method'      => 'GET',
    'header'      => "User-Agent: OndeAjudar-Bot/1.0 (cardapio-sync)\r\n" .
                     "Accept: text/html,application/xhtml+xml\r\n" .
                     "Accept-Language: pt-BR,pt;q=0.9\r\n",
]]);

$html = @file_get_contents(URL_CARDAPIO, false, $ctx);
if (!$html) {
    log_msg('ERRO: não foi possível buscar a página. Abortando.');
    exit(1);
}
log_msg('Página obtida (' . strlen($html) . ' bytes).');

// ── 2. Parse do HTML ──────────────────────────────────────────────────────────
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
libxml_clear_errors();
$xpath = new DOMXPath($dom);

// Extrai texto completo relevante (body sem scripts/styles)
foreach ($xpath->query('//script | //style | //nav | //footer | //header') as $node) {
    $node->parentNode->removeChild($node);
}
$body_text = $dom->textContent;

// Normaliza espaços
$body_text = preg_replace('/[ \t]+/', ' ', $body_text);
$body_text = preg_replace('/\n{3,}/', "\n\n", $body_text);

// ── 3. Identifica seção do cardápio ──────────────────────────────────────────
$pos = mb_stripos($body_text, 'Cardápio');
if ($pos === false) $pos = mb_stripos($body_text, 'card');
if ($pos !== false) {
    $body_text = mb_substr($body_text, $pos);
}
log_msg('Seção do cardápio localizada. Iniciando parse de dias...');

// ── 4. Mapeamentos ────────────────────────────────────────────────────────────
$dias_semana = [
    'Segunda'  => 'Monday',
    'Terça'    => 'Tuesday',
    'Quarta'   => 'Wednesday',
    'Quinta'   => 'Thursday',
    'Sexta'    => 'Friday',
    'Sábado'   => 'Saturday',
    'Domingo'  => 'Sunday',
];
$meses_pt = [
    'janeiro'=>1,'fevereiro'=>2,'março'=>3,'abril'=>4,'maio'=>5,'junho'=>6,
    'julho'=>7,'agosto'=>8,'setembro'=>9,'outubro'=>10,'novembro'=>11,'dezembro'=>12,
];

// Indicadores de atendimento apenas para pop. em situação de rua
$indicadores_pop_rua = [
    'apenas para população em situação de rua',
    'exclusivo para a população em situação de rua',
    'atendimento exclusivo',
    'apenas pop',
];

// ── 5. Divide o texto em blocos por dia ───────────────────────────────────────
$pattern_dia = '/(' . implode('|', array_keys($dias_semana)) . ')-?feira[,\s]+(\d+)[°ºo]?\s+de\s+([a-záéíóúâêîôûãõç]+)/iu';

preg_match_all($pattern_dia, $body_text, $matches, PREG_OFFSET_CAPTURE);

if (empty($matches[0])) {
    log_msg('AVISO: Nenhum dia da semana encontrado na página. Estrutura pode ter mudado.');
    exit(0);
}

log_msg('Encontrados ' . count($matches[0]) . ' dias no cardápio.');

$dias_encontrados = [];
foreach ($matches[0] as $i => $match) {
    $dias_encontrados[] = [
        'raw'    => $match[0],
        'offset' => $match[1],
        'dia_pt' => $matches[1][$i][0],
        'dia'    => (int)$matches[2][$i][0],
        'mes_pt' => mb_strtolower($matches[3][$i][0]),
    ];
}

// Determina o ano de cada data
$ano_atual = (int)date('Y');
$entradas  = [];

foreach ($dias_encontrados as $idx => $dia_info) {
    $mes = $meses_pt[$dia_info['mes_pt']] ?? null;
    if (!$mes) {
        log_msg("AVISO: Mês não reconhecido: {$dia_info['mes_pt']}");
        continue;
    }

    // Determina o ano: se a data já passou há mais de 30 dias, pode ser ano que vem
    $data_tentativa = sprintf('%04d-%02d-%02d', $ano_atual, $mes, $dia_info['dia']);
    $ts = strtotime($data_tentativa);
    if ($ts < strtotime('-30 days')) {
        $data_tentativa = sprintf('%04d-%02d-%02d', $ano_atual + 1, $mes, $dia_info['dia']);
    }

    // Extrai o bloco de texto deste dia até o próximo
    $start = $dia_info['offset'];
    $end   = isset($dias_encontrados[$idx + 1]) ? $dias_encontrados[$idx + 1]['offset'] : strlen($body_text);
    $bloco = substr($body_text, $start, $end - $start);

    // Detecta apenas_pop_rua no bloco inteiro
    $apenas_pop_rua = false;
    foreach ($indicadores_pop_rua as $ind) {
        if (mb_stripos($bloco, $ind) !== false) {
            $apenas_pop_rua = true;
            break;
        }
    }

    // Extrai refeições
    $refeicoes = extrair_refeicoes($bloco);

    foreach ($refeicoes as $tipo => $itens) {
        if (empty(trim($itens))) continue;
        $entradas[] = [
            'data'          => $data_tentativa,
            'refeicao'      => $tipo,
            'itens'         => normalizar_itens($itens),
            'apenas_pop_rua'=> $apenas_pop_rua,
        ];
    }

    log_msg("  {$data_tentativa} ({$dia_info['raw']}): " . count($refeicoes) . " refeição(ões)" . ($apenas_pop_rua ? ' [só pop. rua]' : ''));
}

// ── 6. Extrai refeições de um bloco de texto ──────────────────────────────────
function extrair_refeicoes(string $bloco): array {
    $refeicoes = [];

    $padroes = [
        'cafe'   => '/Caf[eé]\s+da\s+manh[aã]\s*:?\s*(.+?)(?=Almo[cç]o|Jantar|$)/isu',
        'almoco' => '/Almo[cç]o\s*(?:\([^)]*\))?\s*:?\s*(.+?)(?=Jantar|Caf[eé]|$)/isu',
        'jantar' => '/Jantar\s*(?:\([^)]*\))?\s*:?\s*(.+?)(?=Almo[cç]o|Caf[eé]|$)/isu',
    ];

    foreach ($padroes as $tipo => $pattern) {
        if (preg_match($pattern, $bloco, $m)) {
            $texto = trim(preg_replace('/\s+/', ' ', $m[1]));
            // Remove frases genéricas de fim de bloco
            $texto = preg_replace('/(Atendimento|Funcionamento|Durante|Os usu[aá]rios).*/isu', '', $texto);
            $texto = trim($texto);
            if (strlen($texto) > 3) {
                $refeicoes[$tipo] = $texto;
            }
        }
    }

    return $refeicoes;
}

// ── 7. Normaliza a string de itens ────────────────────────────────────────────
function normalizar_itens(string $itens): string {
    // Remove marcadores HTML residuais
    $itens = strip_tags($itens);
    // Substitui separadores comuns por " · "
    $itens = preg_replace('/\s*[\/,]\s*/', ' · ', $itens);
    // Remove espaços duplos
    $itens = preg_replace('/\s{2,}/', ' ', $itens);
    // Remove " · " duplicados
    $itens = preg_replace('/(\s*·\s*){2,}/', ' · ', $itens);
    return trim($itens, " ·\n\r\t");
}

// ── 8. Persiste no banco ─────────────────────────────────────────────────────
if (empty($entradas)) {
    log_msg('AVISO: Nenhuma entrada extraída. Banco não será alterado.');
    exit(0);
}

$sql_upsert = "INSERT INTO cardapio_semanal
                   (cidade, estado, data_cardapio, refeicao, itens, apenas_pop_rua)
               VALUES
                   (:cidade, :estado, :data, :refeicao, :itens, :pop_rua)
               ON CONFLICT (cidade, estado, data_cardapio, refeicao)
               DO UPDATE SET
                   itens          = EXCLUDED.itens,
                   apenas_pop_rua = EXCLUDED.apenas_pop_rua";

$stmt    = $conn->prepare($sql_upsert);
$salvos  = 0;
$erros   = 0;

foreach ($entradas as $e) {
    try {
        $stmt->execute([
            ':cidade'   => CIDADE,
            ':estado'   => ESTADO,
            ':data'     => $e['data'],
            ':refeicao' => $e['refeicao'],
            ':itens'    => $e['itens'],
            ':pop_rua'  => $e['apenas_pop_rua'] ? 'true' : 'false',
        ]);
        $salvos++;
    } catch (PDOException $ex) {
        log_msg("ERRO ao salvar {$e['data']} / {$e['refeicao']}: " . $ex->getMessage());
        $erros++;
    }
}

log_msg("Concluído: {$salvos} entradas salvas, {$erros} erros.");
