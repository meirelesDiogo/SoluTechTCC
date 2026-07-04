<?php
require_once __DIR__ . '/includes/conexao.php';
$paginaAtual = 'contato';
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $assunto = trim($_POST['assunto']);
    $mensagem = trim($_POST['mensagem']);

    try {

        $mail = require __DIR__ . '/config/mail.php';

$mail->addAddress($email, $nome);

$mail->isHTML(true);

$mail->Subject = 'Recebemos sua mensagem!';

$mail->Body = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>

    <h2 style='color:#f1c40f;'>Olá, {$nome}!</h2>

    <p>Recebemos sua mensagem com sucesso.</p>

    <p>Nossa equipe da <strong>SoluTech IA</strong> responderá o mais breve possível.</p>

    <hr>

    <h3>Sua mensagem</h3>

    <p>{$mensagem}</p>

    <br>

    <p>Atenciosamente,</p>

    <h3>SoluTech IA</h3>

</div>";

$mail->send();

        $enviado = true;

    } catch (Exception $e) {

        die($e->getMessage());

    }

}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contato — SoluTech</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/animations.css">
</head>
<body>
<div id="loader"><div class="loader-spinner"></div></div>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<section class="section" style="padding-top:160px;">
  <div class="container" style="max-width:760px;">
    <div class="section-header" data-animate="fade-in">
      <span class="section-tag">Fale Conosco</span>
      <h2>Entre em contato</h2>
      <p>Tem dúvidas ou quer conversar com nossa equipe? Envie sua mensagem.</p>
    </div>

    <?php if ($enviado): ?>
      <div class="card" style="border-color:rgba(46,204,113,.4); margin-bottom:24px;">
        <p style="color:#2ecc71;"><i class="fa-solid fa-circle-check"></i> Mensagem enviada com sucesso! Retornaremos em breve.</p>
      </div>
    <?php endif; ?>

    <div class="form-card" data-animate="slide-up">
      <form method="POST">
        <div class="form-grid">
          <div class="form-group"><label>Nome</label><input type="text" name="nome" required></div>
          <div class="form-group"><label>E-mail</label><input type="email" name="email" required></div>
          <div class="form-group full"><label>Assunto</label><input type="text" name="assunto"></div>
          <div class="form-group full"><label>Mensagem</label><textarea name="mensagem" required></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary btn-analisar glow-hover">
          <i class="fa-solid fa-paper-plane"></i> Enviar Mensagem
        </button>
      </form>
    </div>

    <div class="grid grid-3" style="margin-top:50px;">
      <div class="card" data-animate="fade-in"><i class="fa-solid fa-envelope icon-pulse" style="color:var(--amarelo); font-size:22px;"></i><p style="margin-top:12px;">contato@solutech.com</p></div>
      <div class="card" data-animate="fade-in" data-delay="1"><i class="fa-solid fa-phone icon-pulse" style="color:var(--amarelo); font-size:22px;"></i><p style="margin-top:12px;">(31) 99999-9999</p></div>
      <div class="card" data-animate="fade-in" data-delay="2"><i class="fa-solid fa-location-dot icon-pulse" style="color:var(--amarelo); font-size:22px;"></i><p style="margin-top:12px;">Belo Horizonte, MG</p></div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
