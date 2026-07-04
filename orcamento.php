<?php

/**
 * orcamento.php
 * Formulário de solicitação de orçamento (pode vir vinculado a um diagnóstico).
 */
require_once __DIR__ . '/includes/conexao.php';
require_once __DIR__ . '/services/EmailService.php';
$paginaAtual = 'orcamento';

/**
 * Busca os dados do cliente vinculado a um diagnóstico.
 * Sempre lê do banco — nunca confia em valores vindos do formulário.
 */
function buscarClientePorDiagnostico(PDO $pdo, int $diagnosticoId): ?array
{
  $stmt = $pdo->prepare('
        SELECT c.id, c.nome, c.empresa, c.telefone, c.email, c.cidade
        FROM diagnosticos d
        INNER JOIN clientes c ON c.id = d.cliente_id
        WHERE d.id = ?
    ');
  $stmt->execute([$diagnosticoId]);
  
  $row = $stmt->fetch();
  return $row ?: null;
}

$diagnosticoId = filter_input(INPUT_GET, 'diagnostico_id', FILTER_VALIDATE_INT) ?: null;
$mensagem = '';
$erro = '';
$cliente = null;

// Exibição inicial (GET): se veio de um diagnóstico, carrega os dados do cliente
if ($diagnosticoId) {
  $cliente = buscarClientePorDiagnostico($pdo, $diagnosticoId);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $diagId = filter_input(INPUT_POST, 'diagnostico_id', FILTER_VALIDATE_INT) ?: null;

  // Se o orçamento está vinculado a um diagnóstico, os dados do cliente
  // (nome, empresa, telefone, email, cidade) vêm SEMPRE do banco.
  // Não usamos $_POST para esses campos — o usuário só pode visualizá-los,
  // nunca alterá-los, mesmo manipulando o HTML no navegador.
  $clienteVinculado = $diagId ? buscarClientePorDiagnostico($pdo, $diagId) : null;

  if ($clienteVinculado) {
    $clienteId = $clienteVinculado['id'];
    $nome      = $clienteVinculado['nome'];
    $empresa   = $clienteVinculado['empresa'];
    $telefone  = $clienteVinculado['telefone'];
    $email     = $clienteVinculado['email'];
    $cidade    = $clienteVinculado['cidade'];
  } else {
    // Sem diagnóstico vinculado: acesso direto à página, os campos
    // ficam editáveis e vêm do formulário normalmente.
    $clienteId = null;
    $nome      = limpar($_POST['nome'] ?? '');
    $empresa   = limpar($_POST['empresa'] ?? '');
    $telefone  = limpar($_POST['telefone'] ?? '');
    $email     = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $cidade    = limpar($_POST['cidade'] ?? '');
  }

  $descricao     = limpar($_POST['descricao'] ?? '');
  $urgencia      = limpar($_POST['urgencia'] ?? 'Normal');
  $orcamentoDisp = limpar($_POST['orcamento_disponivel'] ?? '');
  $observacoes   = limpar($_POST['observacoes'] ?? '');

  if (!$nome || !$telefone || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erro = 'Preencha os campos obrigatórios corretamente.';
    $cliente = $clienteVinculado; // mantém os dados exibidos no formulário
    $diagnosticoId = $diagId;
  } else {
    $stmt = $pdo->prepare('
            INSERT INTO orcamentos
            (cliente_id, diagnostico_id, nome, empresa, telefone, email, cidade, descricao, urgencia, orcamento_disponivel, observacoes, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?, "Novo")
        ');
    $stmt->execute([$clienteId, $diagId, $nome, $empresa, $telefone, $email, $cidade, $descricao, $urgencia, $orcamentoDisp, $observacoes]);

    $mensagem = 'Orçamento enviado com sucesso! Nossa equipe entrará em contato em breve.';
    $cliente = $clienteVinculado;
    $diagnosticoId = $diagId;
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar Orçamento — SoluTech</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/animations.css">
</head>

<body>
  <div id="loader">
    <div class="loader-spinner"></div>
  </div>
  <?php include __DIR__ . '/includes/navbar.php'; ?>

  <section class="section" style="padding-top:160px;">
    <div class="container" style="max-width:800px;">
      <div class="section-header" data-animate="fade-in">
        <span class="section-tag">Orçamento</span>
        <h2>Solicite seu orçamento personalizado</h2>
        <p>Preencha os dados abaixo e nossa equipe entrará em contato com uma proposta sob medida.</p>
      </div>

      <?php if ($mensagem): ?>
        <div class="card" style="border-color:rgba(46,204,113,.4); margin-bottom:24px;">
          <p style="color:#2ecc71;"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($mensagem) ?></p>
        </div>
      <?php endif; ?>
      <?php if ($erro): ?>
        <div class="card" style="border-color:rgba(255,75,75,.4); margin-bottom:24px;">
          <p style="color:#FF4B4B;"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?></p>
        </div>
      <?php endif; ?>

      <div class="form-card" data-animate="slide-up">
        <form method="POST">
          <input type="hidden" name="diagnostico_id" value="<?= htmlspecialchars($diagnosticoId ?? '') ?>">
          <div class="form-grid">

            <?php if ($cliente): ?>
              <!-- Cliente identificado pelo diagnóstico: dados só leitura.
                   Não há <input> editável para esses campos — o valor real
                   é sempre buscado de novo no servidor no POST. -->
              <div class="cliente-info form-group full">
                <p><strong>Nome:</strong> <?= htmlspecialchars($cliente['nome']) ?></p>
                <p><strong>Empresa:</strong> <?= htmlspecialchars($cliente['empresa']) ?></p>
                <p><strong>Telefone:</strong> <?= htmlspecialchars($cliente['telefone']) ?></p>
                <p><strong>E-mail:</strong> <?= htmlspecialchars($cliente['email']) ?></p>
                <p><strong>Cidade:</strong> <?= htmlspecialchars($cliente['cidade']) ?></p>
              </div>
            <?php else: ?>
              <!-- Acesso direto, sem diagnóstico vinculado: campos editáveis -->
              <div class="form-group">
                <label>Nome *</label>
                <input type="text" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Empresa</label>
                <input type="text" name="empresa" value="<?= htmlspecialchars($_POST['empresa'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Telefone *</label>
                <input type="text" name="telefone" required value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>E-mail *</label>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Cidade</label>
                <input type="text" name="cidade" value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>">
              </div>
            <?php endif; ?>

            <div class="form-group">
              <label>Urgência</label>
              <select name="urgencia">
                <option>Baixa</option>
                <option selected>Normal</option>
                <option>Alta</option>
                <option>Urgente</option>
              </select>
            </div>
            <div class="form-group full"><label>Descrição do projeto</label><textarea name="descricao"></textarea></div>
            <div class="form-group"><label>Orçamento disponível</label><input type="text" name="orcamento_disponivel" placeholder="Ex: R$ 10.000"></div>
            <div class="form-group full"><label>Observações</label><textarea name="observacoes"></textarea></div>
          </div>
          <button type="submit" class="btn btn-primary btn-analisar glow-hover">
            <i class="fa-solid fa-paper-plane"></i> Enviar Solicitação
          </button>
        </form>
      </div>
    </div>
  </section>

  <?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>