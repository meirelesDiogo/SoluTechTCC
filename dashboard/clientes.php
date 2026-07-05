<?php
/**
 * dashboard/clientes.php
 * CRUD completo de clientes (cadastrar, editar, excluir, pesquisar, paginação).
 */
require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/auth.php';
protegerPagina();

$paginaDash = 'clientes';
$tituloDash = 'Clientes';

// ---- Ações (criar / editar / excluir) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
if ($acao === 'enviar_email') {

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    if ($id) {
        $stmt = $pdo->prepare("SELECT nome, email FROM clientes WHERE id = ?");
        $stmt->execute([$id]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            $assunto = $_POST['assunto'] ?? 'Mensagem da SoluTech';
            $mensagem = $_POST['mensagem'] ?? '';

            $mail = require __DIR__ . '/../config/mail.php';

            $mail->addAddress($cliente['email'], $cliente['nome']);
            $mail->isHTML(true);
            $mail->Subject = $assunto;

            $mail->Body = "
            <div style='font-family:Arial;max-width:600px;margin:auto'>
                <h2 style='color:#1e90ff;'>Olá {$cliente['nome']}</h2>
                <p>{$mensagem}</p>
                <br>
                <p><b>SoluTech IA</b></p>
            </div>";

            $mail->send();
        }
    }

    header("Location: clientes.php");
    exit;
}
    if ($acao === 'salvar') {
        $id       = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
        $nome     = limpar($_POST['nome']);
        $empresa  = limpar($_POST['empresa']);
        $email    = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $telefone = limpar($_POST['telefone']);
        $cidade   = limpar($_POST['cidade']);
        $segmento = limpar($_POST['segmento']);

        if ($id) {
            $stmt = $pdo->prepare('UPDATE clientes SET nome=?, empresa=?, email=?, telefone=?, cidade=?, segmento=? WHERE id=?');
            $stmt->execute([$nome, $empresa, $email, $telefone, $cidade, $segmento, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO clientes (nome, empresa, email, telefone, cidade, segmento) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$nome, $empresa, $email, $telefone, $cidade, $segmento]);
        }
        header('Location: clientes.php');
        exit;
    }

    if ($acao === 'excluir') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $pdo->prepare('DELETE FROM clientes WHERE id = ?')->execute([$id]);
        }
        header('Location: clientes.php');
        exit;
    }
}

// ---- Pesquisa + Paginação ----
$busca = limpar($_GET['busca'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$porPagina = 10;
$offset = ($pagina - 1) * $porPagina;

$where = '';
$params = [];
if ($busca !== '') {
    $where = 'WHERE nome LIKE ? OR empresa LIKE ? OR email LIKE ?';
    $params = ["%$busca%", "%$busca%", "%$busca%"];
}

$totalRegistros = $pdo->prepare("SELECT COUNT(*) FROM clientes $where");
$totalRegistros->execute($params);
$totalRegistros = (int)$totalRegistros->fetchColumn();
$totalPaginas = max(1, (int)ceil($totalRegistros / $porPagina));

$sql = "SELECT * FROM clientes $where ORDER BY id DESC LIMIT $porPagina OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

include __DIR__ . '/includes_dash_layout_top.php';
?>

<div class="toolbar">
  <div class="busca-box">
    <i class="fa-solid fa-magnifying-glass"></i>
    <form method="GET"><input type="text" name="busca" placeholder="Pesquisar cliente..." value="<?= htmlspecialchars($busca) ?>" onchange="this.form.submit()"></form>
  </div>
  <button class="btn btn-primary" data-abrir-modal="#modal-cliente" onclick="prepararNovoCliente()">
    <i class="fa-solid fa-plus"></i> Novo Cliente
  </button>
</div>

<div class="painel">
  <div class="tabela-wrap">
    <table class="tabela-dash" id="tabela-clientes">
      <thead>
        <tr><th>Nome</th><th>Empresa</th><th>E-mail</th><th>Telefone</th><th>Cidade</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($clientes as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['nome']) ?></td>
          <td><?= htmlspecialchars($c['empresa']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td><?= htmlspecialchars($c['telefone']) ?></td>
          <td><?= htmlspecialchars($c['cidade']) ?></td>
          <td>
  <div class="acoes-tabela">

    <!-- visualizar -->
    <a href="#" onclick='abrirEdicao(<?= json_encode($c) ?>); return false;'>
      <i class="fa-solid fa-eye"></i>
    </a>

    <!-- editar -->
    <a href="#" onclick='abrirEdicao(<?= json_encode($c) ?>); return false;'>
      <i class="fa-solid fa-pen"></i>
    </a>

    <!-- enviar email -->
   <a href="#" onclick='abrirEmail(<?= $c["id"] ?>, "<?= htmlspecialchars($c["nome"], ENT_QUOTES) ?>", "<?= htmlspecialchars($c["email"], ENT_QUOTES) ?>")'>
  <i class="fa-solid fa-paper-plane" style="color:#1e90ff;"></i>
</a>
    <!-- deletar -->
    <form method="POST" style="display:inline;" onsubmit="return confirmarExclusao(this);">
      <input type="hidden" name="acao" value="excluir">
      <input type="hidden" name="id" value="<?= $c['id'] ?>">
      <button type="submit">
        <i class="fa-solid fa-trash"></i>
      </button>
    </form>

  </div>
</td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$clientes): ?><tr><td colspan="6">Nenhum cliente encontrado.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="paginacao">
    <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
      <a href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>" class="<?= $p === $pagina ? 'ativo' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
</div>

<!-- Modal Cadastro/Edição -->
<div class="modal-overlay" id="modal-cliente">
  <div class="modal-box">
    <span class="modal-close" data-fechar-modal>&times;</span>
    <h3 id="modal-titulo">Novo Cliente</h3>
    <form method="POST" id="form-cliente">
      <input type="hidden" name="acao" value="salvar">
      <input type="hidden" name="id" id="cliente-id">
      <div class="form-group"><label>Nome</label><input type="text" name="nome" id="cliente-nome" required></div>
      <div class="form-group"><label>Empresa</label><input type="text" name="empresa" id="cliente-empresa"></div>
      <div class="form-group"><label>E-mail</label><input type="email" name="email" id="cliente-email" required></div>
      <div class="form-group"><label>Telefone</label><input type="text" name="telefone" id="cliente-telefone"></div>
      <div class="form-group"><label>Cidade</label><input type="text" name="cidade" id="cliente-cidade"></div>
      <div class="form-group"><label>Segmento</label><input type="text" name="segmento" id="cliente-segmento"></div>
      <button type="submit" class="btn btn-primary" style="width:100%;">Salvar</button>
    </form>
  </div>
</div>

<script>
  function prepararNovoCliente() {
    document.getElementById('modal-titulo').textContent = 'Novo Cliente';
    document.getElementById('form-cliente').reset();
    document.getElementById('cliente-id').value = '';
  }
  function abrirEdicao(c) {
    document.getElementById('modal-titulo').textContent = 'Editar Cliente';
    document.getElementById('cliente-id').value = c.id;
    document.getElementById('cliente-nome').value = c.nome;
    document.getElementById('cliente-empresa').value = c.empresa || '';
    document.getElementById('cliente-email').value = c.email;
    document.getElementById('cliente-telefone').value = c.telefone || '';
    document.getElementById('cliente-cidade').value = c.cidade || '';
    document.getElementById('cliente-segmento').value = c.segmento || '';
    document.getElementById('modal-cliente').classList.add('ativo');
  }
</script><div class="modal-overlay" id="modal-email">
  <div class="modal-box">
    <span class="modal-close" data-fechar-modal>&times;</span>

    <h3>Enviar e-mail para <span id="email-nome"></span></h3>

    <form method="POST">
      <input type="hidden" name="acao" value="enviar_email">
      <input type="hidden" name="id" id="email-id">

      <div class="form-group">
        <label>Assunto</label>
        <input type="text" name="assunto" id="email-assunto" required>
      </div>

      <div class="form-group">
        <label>Mensagem</label>
        <textarea name="mensagem" id="email-mensagem" required></textarea>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">
        Enviar
      </button>
    </form>

  </div>
</div>
<script>
function abrirEmail(id, nome) {
    document.getElementById('email-id').value = id;
    document.getElementById('email-nome').textContent = nome;

    document.getElementById('modal-email').classList.add('ativo');
}
</script>
<script>
function abrirEmail(id, nome, email) {
    document.getElementById('email-id').value = id;
    document.getElementById('email-nome').textContent = nome;

    // TEMPLATE PRONTO
    document.getElementById('email-assunto').value = "Olá " + nome + " — Mensagem da SoluTech";

    document.getElementById('email-mensagem').value =
`Olá ${nome},

Espero que esteja bem!

Aqui é da SoluTech IA. Estamos entrando em contato para dar continuidade ao seu atendimento e entender melhor suas necessidades.

Se precisar, pode responder este e-mail diretamente.

Atenciosamente,
Equipe SoluTech IA`;

    document.getElementById('modal-email').classList.add('ativo');
}
</script>
<?php include __DIR__ . '/includes_dash_layout_bottom.php'; ?>
