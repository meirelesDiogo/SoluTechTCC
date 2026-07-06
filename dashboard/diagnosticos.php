<?php
/**
 * dashboard/diagnosticos.php
 * Lista os diagnósticos gerados pela IA, com visualização detalhada e exclusão.
 */
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../services/EmailService.php';
protegerPagina();

$paginaDash = 'diagnosticos';
$tituloDash = 'Diagnósticos';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if ($id) $pdo->prepare('DELETE FROM diagnosticos WHERE id = ?')->execute([$id]);
    header('Location: diagnosticos.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'enviar_email') {
    $id            = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $assunto       = trim(str_replace(["\r", "\n"], ' ', $_POST['assunto'] ?? ''));
    $mensagem      = trim($_POST['mensagem'] ?? '');
    $mensagemExtra = trim($_POST['mensagem_extra'] ?? '');

    $stmt = $pdo->prepare('
        SELECT c.nome, c.email
        FROM diagnosticos d
        INNER JOIN clientes c ON c.id = d.cliente_id
        WHERE d.id = ?
    ');
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();

    if ($id && $assunto && $mensagem && $cliente && filter_var($cliente['email'], FILTER_VALIDATE_EMAIL)) {
        try {
            EmailService::enviarEmailPersonalizado(
                $cliente['email'],
                $cliente['nome'],
                $assunto,
                $mensagem,
                $mensagemExtra !== '' ? $mensagemExtra : null
            );
            header('Location: diagnosticos.php?email_enviado=1');
            exit;
        } catch (\Throwable $e) {
            error_log('Erro ao enviar e-mail de diagnóstico: ' . $e->getMessage());
            header('Location: diagnosticos.php?email_erro=1');
            exit;
        }
    }

    header('Location: diagnosticos.php?email_erro=1');
    exit;
}

$busca = limpar($_GET['busca'] ?? '');
$where = '';
$params = [];
if ($busca !== '') {
    $where = 'WHERE c.nome LIKE ? OR c.empresa LIKE ? OR d.problema LIKE ?';
    $params = ["%$busca%", "%$busca%", "%$busca%"];
}

$stmt = $pdo->prepare("
    SELECT d.*, c.nome, c.empresa, c.email
    FROM diagnosticos d JOIN clientes c ON c.id = d.cliente_id
    $where
    ORDER BY d.id DESC
");
$stmt->execute($params);
$diagnosticos = $stmt->fetchAll();

include __DIR__ . '/includes_dash_layout_top.php';
?>

<div class="toolbar">
  <div class="busca-box">
    <i class="fa-solid fa-magnifying-glass"></i>
    <form method="GET"><input type="text" name="busca" placeholder="Pesquisar diagnóstico..." value="<?= htmlspecialchars($busca) ?>" onchange="this.form.submit()"></form>
  </div>
</div>

<div class="painel">
  <div class="tabela-wrap">
    <table class="tabela-dash">
      <thead><tr><th>Cliente</th><th>Empresa</th><th>Problema</th><th>Maturidade</th><th>Data</th><th>Ações</th></tr></thead>
      <tbody>
        <?php foreach ($diagnosticos as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['nome']) ?></td>
          <td><?= htmlspecialchars($d['empresa']) ?></td>
          <td><?= htmlspecialchars(mb_substr($d['problema'], 0, 40)) ?>...</td>
          <td><?= htmlspecialchars($d['nivel_maturidade']) ?></td>
          <td><?= date('d/m/Y', strtotime($d['criado_em'])) ?></td>
          <td>
            <div class="acoes-tabela">
              <a href="../resultado.php?id=<?= $d['id'] ?>" target="_blank" title="Ver diagnóstico"><i class="fa-solid fa-eye"></i></a>
              <a href="#" data-abrir-modal="#modal-email-diag-<?= $d['id'] ?>" title="Enviar e-mail"><i class="fa-solid fa-envelope"></i></a>
              <form method="POST" style="display:inline;" onsubmit="return confirmarExclusao(this);">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">
                <button type="submit" title="Excluir"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>

        <div class="modal-overlay" id="modal-email-diag-<?= $d['id'] ?>">
          <div class="modal-box">
            <span class="modal-close" data-fechar-modal>&times;</span>
            <h3>Enviar e-mail para <?= htmlspecialchars($d['nome']) ?></h3>

            <?php if (!filter_var($d['email'] ?? '', FILTER_VALIDATE_EMAIL)): ?>
              <p style="color:#e74c3c; font-size:14px;">
                Este cliente não possui um e-mail válido cadastrado.
              </p>
            <?php else: ?>
              <form method="POST">
                <input type="hidden" name="acao" value="enviar_email">
                <input type="hidden" name="id" value="<?= $d['id'] ?>">

                <div class="form-group full">
                  <label>Assunto</label>
                  <input type="text" name="assunto" required
                         value="Sobre o seu diagnóstico gratuito — SoluTech IA">
                </div>

                <div class="form-group full">
                  <label>Mensagem</label>
                  <textarea name="mensagem" required><?= htmlspecialchars(
"Olá, {$d['nome']}!

Vimos o diagnóstico que você realizou em nosso site sobre o seguinte problema:
\"" . mb_substr($d['problema'], 0, 150) . "\"

Analisamos o resultado gerado (nível de maturidade: {$d['nivel_maturidade']}) e gostaríamos de conversar com você sobre como a SoluTech IA pode ajudar a colocar essa solução em prática.

Podemos agendar uma conversa rápida esta semana?"
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
        <?php if (!$diagnosticos): ?><tr><td colspan="6">Nenhum diagnóstico encontrado.</td></tr><?php endif; ?>
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
