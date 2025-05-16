<?php
// It's crucial that session_start() is called before any output,
// and ideally dbconfig.php (if needed by session for DB-backed sessions)
// is included before session_start or right after.
// For simple file-based sessions, ensure session.php is included first.
require_once('session.php'); // session_start() is in here
require_once('dbconfig.php'); // For other DB operations if needed in header/menu
$app_config = include(__DIR__ . '/../settings.php');
$appName = $app_config['app']['name'] ?? 'My Application';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars($appName); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-flags.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-payments.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-vendors.min.css">
    <link rel="stylesheet" href="../css/style.css"> <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />


    <style>
      body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }
      .page-wrapper {
        flex-grow: 1;
      }
    </style>
  </head>
  <body>
    <div class="page">
      <?php include('menu.php'); ?>
      <div class="page-wrapper">
        <div class="page-body">
          <div class="container-xl">