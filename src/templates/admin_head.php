<?php
// src/templates/admin_head.php
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = $pageTitle ?? "Admin";
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css?v=1">
  <link rel="shortcut icon" href="../assets/icons/favicon.ico" type="image/x-icon">
</head>
<body class="admin-body">