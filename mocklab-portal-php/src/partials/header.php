<?php
// Shared page header. Expects $pageTitle, $heroEyebrow, $heroTitle and $heroSubtitle.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | MockLab Patient Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/styles/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="/orders/" aria-label="MockLab">mocklab<sup>+</sup></a>
        <?php if (isset($_SESSION['user'])): ?>
            <div class="header-user">
                <span><?= e($_SESSION['user']) ?></span>
                <a class="btn btn-outline" href="/logout/">Log out</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<section class="hero">
    <div class="container">
        <div class="eyebrow eyebrow-light"><?= e($heroEyebrow) ?></div>
        <h1><?= e($heroTitle) ?></h1>
        <?php if ($heroSubtitle): ?>
            <p><?= e($heroSubtitle) ?></p>
        <?php endif; ?>
    </div>
</section>

<main class="container main">
