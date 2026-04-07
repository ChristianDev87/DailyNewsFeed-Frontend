<?php
$session   = $session   ?? [];
$csrfToken = $csrfToken ?? '';
$title     = $title     ?? 'Daily News';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">
    <title><?= htmlspecialchars($title, ENT_QUOTES) ?> — Daily News</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<?php if (!empty($session)): ?>
<nav>
    <a href="/dashboard" class="logo">📰 Daily News</a>
    <div class="nav-right">
        <span class="user"><?= htmlspecialchars($session['discord_username'] ?? '', ENT_QUOTES) ?></span>
        <a href="/logout" class="btn btn-ghost btn-sm">Abmelden</a>
    </div>
</nav>
<?php endif; ?>
<main>
    <?= $content ?>
</main>
<script src="/assets/app.js"></script>
<?php if (!empty($extraJs)) echo $extraJs; ?>
</body>
</html>
