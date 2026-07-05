<?php
require_once __DIR__ . '/includes/conexao.php';
$paginaAtual = 'contato';
$enviado = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nome     = limpar($_POST['nome'] ?? '');
    $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $assunto  = limpar($_POST['assunto'] ?? '');
    $mensagem = limpar($_POST['mensagem'] ?? '');

    if (!$nome || !$mensagem || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Preencha os campos obrigatórios corretamente.';
    } else {
        try {
            $mail = require __DIR__ . '/config/mail.php';

            $mail->addAddress($email, $nome);
            $mail->addCC('contato@solutech.com', 'SoluTech'); // equipe também recebe uma cópia da mensagem

            // Escapa o conteúdo vindo do visitante antes de colocar no HTML do e-mail
            $nomeSeguro     = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
            $assuntoSeguro  = htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8');
            $mensagemSegura = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));

            $mail->isHTML(true);
            $mail->Subject = 'Recebemos sua mensagem!';
            $mail->Body = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;background:#ffffff;padding:20px;border-radius:10px;border:1px solid #eee;'>

    <h2 style='color:#2563EB;margin-bottom:10px;'>Olá, {$nomeSeguro}!</h2>

    <p style='font-size:14px;color:#334155;'>
        Recebemos sua mensagem com sucesso.
        Nossa equipe da <strong>SoluTech IA</strong> responderá o mais breve possível.
    </p>";

            if ($assuntoSeguro !== '') {
                $mail->Body .= "
    <p style='font-size:14px;color:#334155;'><strong>Assunto:</strong> {$assuntoSeguro}</p>";
            }

            $mail->Body .= "
    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>

    <h3 style='color:#2563EB;margin-bottom:10px;'>Sua mensagem</h3>

    <div style='font-size:14px;color:#334155;line-height:1.6;background:#f1f5f9;padding:12px;border-radius:6px;'>
        {$mensagemSegura}
    </div>

    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>

    <p style='font-size:13px;color:#777;'>
        Em breve retornaremos seu contato.
    </p>

    <p style='margin-top:20px;font-weight:bold;color:#2563EB;'>
        SoluTech IA
    </p>

</div>
";

            $mail->send();

            $enviado = true;

        } catch (Exception $e) {
            // Não exponha detalhes internos do servidor de e-mail ao visitante
            $erro = 'Não foi possível enviar sua mensagem agora. Tente novamente em instantes.';
        }
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
    <?php if ($erro): ?>
      <div class="card" style="border-color:rgba(255,75,75,.4); margin-bottom:24px;">
        <p style="color:#FF4B4B;"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($erro) ?></p>
      </div>
    <?php endif; ?>

    <div class="form-card" data-animate="slide-up">
      <form method="POST">
        <div class="form-grid">
          <div class="form-group">
            <label>Nome</label>
            <input type="text" name="nome" placeholder="Seu nome completo" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>E-mail</label>
            <input type="email" name="email" placeholder="seuemail@exemplo.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="form-group full">
            <label>Assunto</label>
            <input type="text" name="assunto" placeholder="Sobre o que você quer falar?" value="<?= htmlspecialchars($_POST['assunto'] ?? '') ?>">
          </div>
          <div class="form-group full">
            <label>Mensagem</label>
            <textarea name="mensagem" placeholder="Escreva sua mensagem aqui..." required><?= htmlspecialchars($_POST['mensagem'] ?? '') ?></textarea>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-analisar glow-hover">
          <i class="fa-solid fa-paper-plane"></i> Enviar Mensagem
        </button>
      </form>
    </div>

    <div class="grid grid-3" style="margin-top:50px;">
      <div class="card" data-animate="fade-in"><i class="fa-solid fa-envelope icon-pulse" style="color:var(--azul-medio); font-size:22px;"></i><p style="margin-top:12px;">contato@solutech.com</p></div>
      <div class="card" data-animate="fade-in" data-delay="1"><i class="fa-solid fa-phone icon-pulse" style="color:var(--azul-medio); font-size:22px;"></i><p style="margin-top:12px;">(31) 99999-9999</p></div>
      <div class="card" data-animate="fade-in" data-delay="2"><i class="fa-solid fa-location-dot icon-pulse" style="color:var(--azul-medio); font-size:22px;"></i><p style="margin-top:12px;">Belo Horizonte, MG</p></div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>