<?php
/**
 * dashboard/orcamentos.php
 * Tabela de orçamentos com alteração de status, exclusão, pesquisa e filtro.
 */
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/EmailService.php';
protegerPagina();

$paginaDash = 'orcamentos';
$tituloDash = 'Orçamentos';

$statusValidos = ['Novo', 'Em análise', 'Em negociação', 'Aprovado', 'Recusado', 'Concluído'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($acao === 'status' && $id && in_array($_POST['status'] ?? '', $statusValidos, true)) {
        $novoStatus = $_POST['status'];

        $stmt = $pdo->prepare('SELECT nome, email, status FROM orcamentos WHERE id = ?');
        $stmt->execute([$id]);
        $orcamentoAtual = $stmt->fetch();

        $pdo->prepare('UPDATE orcamentos SET status = ? WHERE id = ?')->execute([$novoStatus, $id]);

        if ($orcamentoAtual && $orcamentoAtual['status'] !== $novoStatus && filter_var($orcamentoAtual['email'], FILTER_VALIDATE_EMAIL)) {
            try {
                EmailService::enviarMudancaStatus([
                    'nome'            => $orcamentoAtual['nome'],
                    'email'           => $orcamentoAtual['email'],
                    'status_anterior' => $orcamentoAtual['status'],
                    'status_novo'     => $novoStatus,
                ]);
            } catch (\Throwable $e) {
                error_log('Erro ao enviar e-mail de mudança de status: ' . $e->getMessage());
            }
        }

        header('Location: orcamentos.php');
        exit;
    }

    if ($acao === 'excluir' && $id) {
        $pdo->prepare('DELETE FROM orcamentos WHERE id = ?')->execute([$id]);
        header('Location: orcamentos.php');
        exit;
    }

    if ($acao === 'enviar_email' && $id) {
        $assunto       = trim(str_replace(["\r", "\n"], ' ', $_POST['assunto'] ?? ''));
        $mensagem      = trim($_POST['mensagem'] ?? '');
        $mensagemExtra = trim($_POST['mensagem_extra'] ?? '');

        $stmt = $pdo->prepare('SELECT nome, email FROM orcamentos WHERE id = ?');
        $stmt->execute([$id]);
        $orcamentoCliente = $stmt->fetch();

        if ($assunto && $mensagem && $orcamentoCliente && filter_var($orcamentoCliente['email'], FILTER_VALIDATE_EMAIL)) {
            try {
                EmailService::enviarEmailPersonalizado(
                    $orcamentoCliente['email'],
                    $orcamentoCliente['nome'],
                    $assunto,
                    $mensagem,
                    $mensagemExtra !== '' ? $mensagemExtra : null
                );
                header('Location: orcamentos.php?email_enviado=1');
                exit;
            } catch (\Throwable $e) {
                error_log('Erro ao enviar e-mail de orçamento: ' . $e->getMessage());
                header('Location: orcamentos.php?email_erro=1');
                exit;
            }
        }

        header('Location: orcamentos.php?email_erro=1');
        exit;
    }

    header('Location: orcamentos.php');
    exit;
}

$busca = limpar($_GET['busca'] ?? '');
$filtroStatus = limpar($_GET['status'] ?? '');

$condicoes = [];
$params = [];
if ($busca !== '') {
    $condicoes[] = '(nome LIKE ? OR empresa LIKE ? OR telefone LIKE ?)';
    array_push($params, "%$busca%", "%$busca%", "%$busca%");
}
if ($filtroStatus !== '') {
    $condicoes[] = 'status = ?';
    $params[] = $filtroStatus;
}
$where = $condicoes ? 'WHERE ' . implode(' AND ', $condicoes) : '';

$stmt = $pdo->prepare("SELECT * FROM orcamentos $where ORDER BY id DESC");
$stmt->execute($params);
$orcamentos = $stmt->fetchAll();

function classeBadge(string $status): string {
    $map = [
        'Novo' => 'badge-novo', 'Em análise' => 'badge-analise', 'Em negociação' => 'badge-negociacao',
        'Aprovado' => 'badge-aprovado', 'Recusado' => 'badge-recusado', 'Concluído' => 'badge-concluido',
    ];
    return $map[$status] ?? 'badge-novo';
}

include __DIR__ . '/includes_dash_layout_top.php';
?>

<div class="toolbar">
  <div class="busca-box">
    <i class="fa-solid fa-magnifying-glass"></i>
    <form method="GET"><input type="text" name="busca" placeholder="Pesquisar orçamento..." value="<?= htmlspecialchars($busca) ?>" onchange="this.form.submit()"></form>
  </div>
  <form method="GET" style="display:flex; gap:10px;">
    <input type="hidden" name="busca" value="<?= htmlspecialchars($busca) ?>">
    <select name="status" onchange="this.form.submit()" style="background:rgba(255,255,255,.04); color:#fff; border:1px solid var(--borda); border-radius:10px; padding:10px 14px;">
      <option value="">Todos os status</option>
      <?php foreach ($statusValidos as $s): ?>
        <option value="<?= $s ?>" <?= $filtroStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<div class="painel">
  <div class="tabela-wrap">
    <table class="tabela-dash">
      <thead><tr><th>Nome</th><th>Empresa</th><th>Telefone</th><th>Urgência</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($orcamentos as $o): ?>
        <tr>
          <td><?= htmlspecialchars($o['nome']) ?></td>
          <td><?= htmlspecialchars($o['empresa']) ?></td>
          <td><?= htmlspecialchars($o['telefone']) ?></td>
          <td><?= htmlspecialchars($o['urgencia']) ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="acao" value="status">
              <input type="hidden" name="id" value="<?= $o['id'] ?>">
              <select name="status" onchange="this.form.submit()" class="badge <?= classeBadge($o['status']) ?>" style="border:none; cursor:pointer;">
                <?php foreach ($statusValidos as $s): ?>
                  <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <td><?= date('d/m/Y', strtotime($o['criado_em'])) ?></td>
          <td>
            <div class="acoes-tabela">
              <a href="#" data-abrir-modal="#modal-orc-<?= $o['id'] ?>" title="Ver detalhes"><i class="fa-solid fa-eye"></i></a>
              <a href="#" data-abrir-modal="#modal-email-orc-<?= $o['id'] ?>" title="Enviar e-mail"><i class="fa-solid fa-envelope"></i></a>
              <form method="POST" style="display:inline;" onsubmit="return confirmarExclusao(this);">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                <button type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>

        <div class="modal-overlay" id="modal-orc-<?= $o['id'] ?>">
          <div class="modal-box">
            <span class="modal-close" data-fechar-modal>&times;</span>
            <h3>Orçamento de <?= htmlspecialchars($o['nome']) ?></h3>
            <p style="color:var(--texto-secundario); font-size:14px; margin-bottom:8px;"><strong>Empresa:</strong> <?= htmlspecialchars($o['empresa']) ?></p>
            <p style="color:var(--texto-secundario); font-size:14px; margin-bottom:8px;"><strong>E-mail:</strong> <?= htmlspecialchars($o['email']) ?></p>
            <p style="color:var(--texto-secundario); font-size:14px; margin-bottom:8px;"><strong>Cidade:</strong> <?= htmlspecialchars($o['cidade']) ?></p>
            <p style="color:var(--texto-secundario); font-size:14px; margin-bottom:8px;"><strong>Orçamento disponível:</strong> <?= htmlspecialchars($o['orcamento_disponivel']) ?></p>
            <p style="color:var(--texto-secundario); font-size:14px; margin-bottom:8px;"><strong>Descrição:</strong> <?= nl2br(htmlspecialchars($o['descricao'])) ?></p>
            <p style="color:var(--texto-secundario); font-size:14px;"><strong>Observações:</strong> <?= nl2br(htmlspecialchars($o['observacoes'])) ?></p>
          </div>
        </div>

        <div class="modal-overlay" id="modal-email-orc-<?= $o['id'] ?>">
          <div class="modal-box">
            <span class="modal-close" data-fechar-modal>&times;</span>
            <h3>Enviar e-mail para <?= htmlspecialchars($o['nome']) ?></h3>

            <?php if (!filter_var($o['email'] ?? '', FILTER_VALIDATE_EMAIL)): ?>
              <p style="color:#e74c3c; font-size:14px;">
                Este cliente não possui um e-mail válido cadastrado.
              </p>
            <?php else: ?>
              <form method="POST">
                <input type="hidden" name="acao" value="enviar_email">
                <input type="hidden" name="id" value="<?= $o['id'] ?>">

                <div class="form-group full">
                  <label>Assunto</label>
                  <input type="text" name="assunto" required
                         value="Sobre o seu orçamento — SoluTech IA">
                </div>

                <div class="form-group full">
                  <label>Mensagem</label>
                  <textarea name="mensagem" required><?= htmlspecialchars(
"Olá, {$o['nome']}!

Estamos entrando em contato sobre o orçamento solicitado (status atual: {$o['status']}).

Nossa equipe está acompanhando de perto a sua solicitação e queremos alinhar os próximos passos com você."
                  ) ?></textarea>
                </div>

                <div class="form-group full">
                  <label>Mensagem adicional (opcional)</label>
                  <textarea name="mensagem_extra" placeholder="Escreva algo a mais, se quiser complementar..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-paper-plane"></i> Enviar e-mail
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$orcamentos): ?><tr><td colspan="7">Nenhum orçamento encontrado.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes_dash_layout_bottom.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  <?php if (isset($_GET['email_enviado'])): ?>
    Swal.fire({ icon: 'success', title: 'E-mail enviado com sucesso!', timer: 2500, showConfirmButton: false });
  <?php elseif (isset($_GET['email_erro'])): ?>
    Swal.fire({ icon: 'error', title: 'Não foi possível enviar o e-mail', text: 'Verifique os dados e tente novamente.' });
  <?php endif; ?>
});
</script>
