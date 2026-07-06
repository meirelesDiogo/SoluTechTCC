<?php

class EmailService
{
    public static function enviarOrcamento(array $dados)
    {
        $mail = require __DIR__ . '/../config/mail.php';

        $mail->addAddress($dados['email'], $dados['nome']);
        $mail->isHTML(true);

        $mail->Subject = 'Recebemos sua solicitação de orçamento!';

        $mail->Body = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;background:#ffffff;padding:20px;border-radius:10px;border:1px solid #eee;'>

    <h2 style='color:#1e66ff;margin-bottom:10px;'>Olá, {$dados['nome']}!</h2>

    <p style='font-size:14px;color:#333;'>
        Recebemos sua solicitação de orçamento com sucesso.
        Nossa equipe da <strong>SoluTech IA</strong> já está analisando.
    </p>

    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>

    <h3 style='color:#1e66ff;margin-bottom:10px;'>Resumo da solicitação</h3>

    <div style='font-size:14px;color:#444;line-height:1.6;'>
        <p><strong>Empresa:</strong> {$dados['empresa']}</p>
        <p><strong>Cidade:</strong> {$dados['cidade']}</p>
        <p><strong>Telefone:</strong> {$dados['telefone']}</p>
        <p><strong>Urgência:</strong> {$dados['urgencia']}</p>
        <p><strong>Orçamento disponível:</strong> {$dados['orcamento']}</p>
    </div>

    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>

    <h3 style='color:#1e66ff;margin-bottom:10px;'>Descrição do projeto</h3>

    <p style='font-size:14px;color:#444;line-height:1.6;'>
        {$dados['descricao']}
    </p>

    <br>

    <p style='font-size:13px;color:#777;'>
        Em breve nossa equipe entrará em contato.
    </p>

    <p style='margin-top:20px;font-weight:bold;color:#1e66ff;'>
        SoluTech IA
    </p>

</div>
";

        $mail->send();
    }

    /**
     * Envia um e-mail avulso disparado pelo admin do dashboard,
     * a partir de uma mensagem pré-pronta que pode ser personalizada
     * e complementada com um texto adicional.
     */
    public static function enviarEmailPersonalizado(
        string $email,
        string $nome,
        string $assunto,
        string $mensagem,
        ?string $mensagemExtra = null
    ) {
        $mail = require __DIR__ . '/../config/mail.php';

        $mail->addAddress($email, $nome);
        $mail->isHTML(true);

        $mail->Subject = $assunto;

        $blocoExtra = '';
        if ($mensagemExtra !== null && trim($mensagemExtra) !== '') {
            $blocoExtra = "
    <hr style='border:none;border-top:1px solid #eee;margin:20px 0;'>
    <div style='font-size:14px;color:#444;line-height:1.6;'>" . nl2br(htmlspecialchars($mensagemExtra)) . "</div>";
        }

        $mail->Body = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;background:#ffffff;padding:20px;border-radius:10px;border:1px solid #eee;'>

    <h2 style='color:#1e66ff;margin-bottom:10px;'>Olá, " . htmlspecialchars($nome) . "!</h2>

    <div style='font-size:14px;color:#333;line-height:1.6;'>" . nl2br(htmlspecialchars($mensagem)) . "</div>
    {$blocoExtra}

    <p style='margin-top:24px;font-weight:bold;color:#1e66ff;'>
        SoluTech IA
    </p>

</div>
";

        $mail->send();
    }

    /**
     * Notifica o cliente automaticamente quando o status do orçamento muda.
     * $dados: nome, email, status_anterior, status_novo
     */
    public static function enviarMudancaStatus(array $dados)
    {
        $mail = require __DIR__ . '/../config/mail.php';

        $mail->addAddress($dados['email'], $dados['nome']);
        $mail->isHTML(true);

        $mail->Subject = 'Atualização no status do seu orçamento — SoluTech IA';

        $mail->Body = "
<div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;background:#ffffff;padding:20px;border-radius:10px;border:1px solid #eee;'>

    <h2 style='color:#1e66ff;margin-bottom:10px;'>Olá, " . htmlspecialchars($dados['nome']) . "!</h2>

    <p style='font-size:14px;color:#333;line-height:1.6;'>
        O status da sua solicitação de orçamento na <strong>SoluTech IA</strong> foi atualizado.
    </p>

    <div style='background:#f4f7ff;border-radius:10px;padding:16px;margin:20px 0;'>
        <p style='font-size:14px;color:#666;margin:0 0 6px 0;'><strong>Status anterior:</strong> " . htmlspecialchars($dados['status_anterior']) . "</p>
        <p style='font-size:15px;color:#1e66ff;margin:0;'><strong>Novo status:</strong> " . htmlspecialchars($dados['status_novo']) . "</p>
    </div>

    <p style='font-size:13px;color:#777;'>
        Caso tenha alguma dúvida, basta responder este e-mail que nossa equipe irá te ajudar.
    </p>

    <p style='margin-top:24px;font-weight:bold;color:#1e66ff;'>
        SoluTech IA
    </p>

</div>
";

        $mail->send();
    }

    public static function enviarContato() {}
    public static function enviarCadastro() {}
    public static function enviarRecuperacaoSenha() {}
    public static function enviarChamado() {}
    public static function enviarResposta() {}
}