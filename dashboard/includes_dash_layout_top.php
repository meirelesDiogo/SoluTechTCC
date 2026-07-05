<?php
/**
 * dashboard/includes_dash_layout_top.php
 * Cabeçalho de layout (sidebar + topbar) reutilizado por todas as páginas
 * do painel administrativo. Espera $paginaDash (slug) e $tituloDash definidos
 * antes do include.
 */
$paginaDash = $paginaDash ?? '';
$tituloDash = $tituloDash ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($tituloDash) ?> — Painel SoluTech</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/dashboard.css">
  <link rel="stylesheet" href="../css/animations.css">
</head>
<body>
<div class="dash-wrapper">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <i class="fa-solid fa-bolt"></i>
      <span class="sidebar-label">Solu<span style="color:var(--amarelo);">Tech</span></span>
    </div>

    <ul class="sidebar-menu">
      <li><a href="index.php" class="<?= $paginaDash === 'inicio' ? 'ativo' : '' ?>"><i class="fa-solid fa-gauge"></i> <span class="sidebar-label">Visão Geral</span></a></li>
      <li><a href="clientes.php" class="<?= $paginaDash === 'clientes' ? 'ativo' : '' ?>"><i class="fa-solid fa-users"></i> <span class="sidebar-label">Clientes</span></a></li>
      <li><a href="diagnosticos.php" class="<?= $paginaDash === 'diagnosticos' ? 'ativo' : '' ?>"><i class="fa-solid fa-stethoscope"></i> <span class="sidebar-label">Diagnósticos</span></a></li>
      <li><a href="orcamentos.php" class="<?= $paginaDash === 'orcamentos' ? 'ativo' : '' ?>"><i class="fa-solid fa-file-invoice-dollar"></i> <span class="sidebar-label">Orçamentos</span></a></li>
      <li><a href="relatorios.php" class="<?= $paginaDash === 'relatorios' ? 'ativo' : '' ?>"><i class="fa-solid fa-chart-pie"></i> <span class="sidebar-label">Relatórios</span></a></li>
      <li><a href="configuracoes.php" class="<?= $paginaDash === 'configuracoes' ? 'ativo' : '' ?>"><i class="fa-solid fa-gear"></i> <span class="sidebar-label">Configurações</span></a></li>
    </ul>

    <div class="sidebar-footer">
      <a href="../index.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> <span class="sidebar-label">Ver site</span></a>
      <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> <span class="sidebar-label">Sair</span></a>
    </div>
  </aside>

  <main class="dash-main">
    <header class="dash-topbar">
      <div style="display:flex; align-items:center; gap:16px;">
        <button class="btn-icon-toggle" id="btn-toggle-sidebar"><i class="fa-solid fa-bars"></i></button>
        <span class="titulo-pagina"><?= htmlspecialchars($tituloDash) ?></span>
      </div>
      <div class="dash-topbar-right">
        <i class="fa-regular fa-bell" style="color:var(--texto-secundario);"></i>
        <div class="dash-user">
          <div class="dash-user-avatar"><?= strtoupper(substr($_SESSION['usuario_nome'] ?? 'A', 0, 1)) ?></div>
          <span style="font-size:14px;"><?= htmlspecialchars($_SESSION['usuario_nome'] ?? 'Administrador') ?></span>
        </div>
      </div>
    </header>

    <div class="dash-content">
