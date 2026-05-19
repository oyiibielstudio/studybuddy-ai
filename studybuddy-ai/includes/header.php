<?php
require_once __DIR__ . '/helpers.php';

$pageTitle = $pageTitle ?? 'StudyBuddy AI';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?> - StudyBuddy AI</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a class="brand" href="index.php" aria-label="StudyBuddy AI Dashboard">
                <span class="brand-logo" aria-hidden="true">
                    <span class="logo-chat">SB</span>
                    <span class="logo-check">&#10003;</span>
                </span>
                <span>
                    <strong>StudyBuddy AI</strong>
                    <small>Your cozy AI study companion</small>
                </span>
            </a>

            <nav class="nav-menu" aria-label="Menu utama">
                <a class="<?= isActive('index.php'); ?>" href="index.php"><span class="nav-icon" aria-hidden="true">&#8962;</span><span>Dashboard</span></a>
                <a class="<?= isActive('todo.php'); ?>" href="todo.php"><span class="nav-icon" aria-hidden="true">&#10003;</span><span>To-do</span></a>
                <a class="<?= isActive('mood.php'); ?>" href="mood.php"><span class="nav-icon" aria-hidden="true">&#9728;</span><span>Mood Tracker</span></a>
                <a class="<?= isActive('study-plan.php'); ?>" href="study-plan.php"><span class="nav-icon" aria-hidden="true">&#9889;</span><span>Study Plan</span></a>
                <a class="<?= isActive('schedule.php'); ?>" href="schedule.php"><span class="nav-icon" aria-hidden="true">&#128197;</span><span>Manual Plan</span></a>
                <a class="<?= isActive('ai-chat.php'); ?>" href="ai-chat.php"><span class="nav-icon" aria-hidden="true">&#128172;</span><span>Curhat AI</span></a>
            </nav>
        </aside>

        <div class="content-area">
            <header class="topbar">
                <button class="menu-toggle" type="button" id="menuToggle" aria-label="Buka menu">&#9776;</button>
                <div>
                    <span class="eyebrow">Vibe Coding Campus</span>
                    <h1><?= e($pageTitle); ?></h1>
                </div>
            </header>

            <main class="main-content">
