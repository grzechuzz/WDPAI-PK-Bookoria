<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookoria</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

    <link rel="stylesheet" href="/styles/main.css">
    <script src="/js/profile-tabs.js" defer></script>
</head>
<body>
<header class="navbar">
    <div class="navbar-inner">
        <a href="<?= isset($_SESSION['user_id']) ? '/dashboard' : '/login' ?>" class="nav-brand">
            <img src="/images/bookoria-logo.png" alt="Bookoria Logo" class="nav-logo-img">
            <span>Bookoria</span>
        </a>

        <nav class="nav-actions">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php $roleId = (int)($_SESSION['role_id'] ?? 0); ?>

                <?php if ($roleId === 3): ?>
                    <a href="/profile" class="nav-link">Profil</a>
                <?php endif; ?>

                <a href="/repository" class="nav-link">Repozytorium</a>
                <a href="/help" class="nav-link">Pomoc</a>
                <a href="/logout" class="nav-link">Wyloguj się</a>

            <?php else: ?>
                <a href="/login" class="nav-link">Zaloguj</a>
                <a href="/register" class="btn btn-sm">Rejestracja</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="app-container">
    <?= $content ?? '' ?>
</main>

</body>
</html>
