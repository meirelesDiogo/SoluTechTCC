EmailService::enviarContato();

EmailService::enviarCadastro();

EmailService::enviarRecuperacaoSenha();

EmailService::enviarChamado();

EmailService::enviarResposta();

public static function enviarOrcamento(array $dados)
{
    $mail = require __DIR__ . '/../config/mail.php';

    $mail->addAddress($dados['email'], $dados['nome']);

    $mail->Subject = 'Recebemos sua solicitação de orçamento!';

    $mail->Body = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>

        <h2 style='color:#f1c40f;'>Olá, {$dados['nome']}!</h2>

        <p>Recebemos sua solicitação de orçamento com sucesso.</p>

        <hr>

        <h3>Resumo da solicitação</h3>

        <p><b>Empresa:</b> {$dados['empresa']}</p>
        <p><b>Cidade:</b> {$dados['cidade']}</p>
        <p><b>Telefone:</b> {$dados['telefone']}</p>
        <p><b>Urgência:</b> {$dados['urgencia']}</p>
        <p><b>Orçamento disponível:</b> {$dados['orcamento']}</p>

        <hr>

        <h3>Descrição</h3>
        <p>{$dados['descricao']}</p>

        <br>

        <p>Em breve nossa equipe entrará em contato.</p>

        <p><b>SoluTech IA</b></p>

    </div>";

    $mail->send();
}