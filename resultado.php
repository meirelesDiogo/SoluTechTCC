<?php
/**
 * resultado.php
 * Exibe o resultado do diagnóstico gerado pela IA, salvo no banco.
 */
require_once __DIR__ . '/includes/conexao.php';
$paginaAtual = 'diagnostico';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: diagnostico.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT d.*, c.nome, c.empresa
    FROM diagnosticos d
    JOIN clientes c ON c.id = d.cliente_id
    WHERE d.id = ?
    LIMIT 1
');
$stmt->execute([$id]);
$diag = $stmt->fetch();

if (!$diag) {
    header('Location: diagnostico.php');
    exit;
}

$pontuacao = max(0, min(100, (int)$diag['pontuacao']));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resultado do Diagnóstico — SoluTech</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">
  <link rel="stylesheet" href="css/animations.css">
</head>
<body>
<div id="loader"><div class="loader-spinner"></div></div>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="section" style="padding-top:150px;">
  <div class="container">
    <div class="section-header" data-animate="fade-in">
      <span class="section-tag">Resultado da Análise</span>
      <h2>Diagnóstico de <?= htmlspecialchars($diag['empresa']) ?></h2>
      <p>Olá <?= htmlspecialchars($diag['nome']) ?>, veja abaixo o que nossa IA identificou sobre o seu negócio.</p>
    </div>

    <!-- Velocímetro de maturidade digital -->
    <div class="card velocimetro-wrap" style="max-width:480px; margin:0 auto 50px;" data-animate="fade-in">
      <h3 style="margin-bottom:10px;"><i class="fa-solid fa-gauge-high"></i> Maturidade Digital: <?= htmlspecialchars($diag['nivel_maturidade']) ?></h3>
      <canvas id="grafico-velocimetro" height="180"></canvas>
      <div class="barra-progresso">
        <div class="barra-progresso-fill" id="barra-fill" style="width:0%;"></div>
      </div>
      <p style="margin-top:10px; font-weight:700; color:var(--amarelo);"><?= $pontuacao ?>/100 pontos</p>
    </div>

    <!-- Cards do diagnóstico -->
    <div class="resultado-grid" data-animate="fade-in">
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-stethoscope"></i> Diagnóstico</h3>
        <p><?= nl2br(htmlspecialchars($diag['diagnostico'])) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-lightbulb"></i> Solução Recomendada</h3>
        <p><?= nl2br(htmlspecialchars($diag['solucao'])) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-star"></i> Benefícios</h3>
        <p><?= nl2br(htmlspecialchars($diag['beneficios'])) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-layer-group"></i> Tecnologias</h3>
        <p><?= nl2br(htmlspecialchars($diag['tecnologias'])) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-diagram-project"></i> Complexidade</h3>
        <p><?= htmlspecialchars($diag['complexidade']) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-clock"></i> Tempo Estimado</h3>
        <p><?= htmlspecialchars($diag['tempo']) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-flag"></i> Prioridade</h3>
        <p><?= htmlspecialchars($diag['prioridade']) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-sack-dollar"></i> Investimento Estimado</h3>
        <p><?= htmlspecialchars($diag['orcamento_estimado']) ?></p>
      </div>
      <div class="card resultado-card">
        <h3><i class="fa-solid fa-clipboard-list"></i> Recomendações</h3>
        <p><?= nl2br(htmlspecialchars($diag['recomendacoes'])) ?></p>
      </div>
    </div>

    <!-- Gráfico complementar -->
    <div class="card" style="margin-top:40px;" data-animate="fade-in">
      <h3 style="margin-bottom:20px;"><i class="fa-solid fa-chart-column"></i> Visão Geral do Diagnóstico</h3>
      <canvas id="grafico-diagnostico" height="90"></canvas>
    </div>

    <div style="text-align:center; margin-top:50px;" data-animate="fade-in">
      <a href="orcamento.php?diagnostico_id=<?= $diag['id'] ?>" class="btn btn-primary glow-hover">
        <i class="fa-solid fa-file-invoice-dollar"></i> Solicitar Orçamento
      </a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
  const pontuacao = <?= $pontuacao ?>;

  // Barra de progresso animada
  window.addEventListener('load', () => {
    setTimeout(() => {
      document.getElementById('barra-fill').style.width = pontuacao + '%';
    }, 300);
  });

  // Velocímetro (doughnut semicircular)
  new Chart(document.getElementById('grafico-velocimetro'), {
    type: 'doughnut',
    data: {
      labels: ['Maturidade', 'Restante'],
      datasets: [{
        data: [pontuacao, 100 - pontuacao],
        backgroundColor: ['#2563EB', 'rgba(15,23,42,.08)'],
        borderWidth: 0,
      }]
    },
    options: {
      circumference: 180,
      rotation: 270,
      cutout: '75%',
      plugins: { legend: { display: false }, tooltip: { enabled: false } }
    }
  });

  // Gráfico de barras com os principais indicadores
  new Chart(document.getElementById('grafico-diagnostico'), {
    type: 'bar',
    data: {
      labels: ['Maturidade', 'Prioridade', 'Complexidade'],
      datasets: [{
        label: 'Nível (escala 0-3)',
        data: [
          <?= $diag['nivel_maturidade'] === 'Alto' ? 3 : ($diag['nivel_maturidade'] === 'Médio' ? 2 : 1) ?>,
          <?= $diag['prioridade'] === 'Alta' ? 3 : ($diag['prioridade'] === 'Média' ? 2 : 1) ?>,
          <?= $diag['complexidade'] === 'Alta' ? 3 : ($diag['complexidade'] === 'Média' ? 2 : 1) ?>
        ],
        backgroundColor: ['#2563EB', '#06B6D4', '#64748B'],
        borderRadius: 8,
      }]
    },
    options: {
      scales: {
        x: { ticks: { color: '#64748B' }, grid: { display: false } },
        y: { beginAtZero: true, max: 3, ticks: { color: '#64748B' }, grid: { color: 'rgba(15,23,42,.06)' } }
      },
      plugins: { legend: { display: false } }
    }
  });
</script>
</body>
</html>
