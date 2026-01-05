<?php
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Config.php';

$users = $users ?? [];
$branches = $branches ?? [];
$roles = $roles ?? [];
$currentPage = $currentPage ?? 1;
$totalPages = $totalPages ?? 1;
$totalUsers = $totalUsers ?? 0;
$emailSearch = $emailSearch ?? null;
$roleFilter = $roleFilter ?? null;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$baseUrl = '/users?';
$filterParams = [];
if ($emailSearch) $filterParams['email'] = $emailSearch;
if ($roleFilter) $filterParams['role'] = $roleFilter;
$filterQuery = http_build_query($filterParams);
?>

<div class="repo-container">

    <h1 class="repo-header">Zarządzanie użytkownikami</h1>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-success">
            <?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="error-msg">
            <?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <form action="/users" method="GET" class="users-filters">
        <div class="search-input-group">
            <span class="material-symbols-outlined search-icon">search</span>
            <input type="text" name="email" class="search-input" 
                   value="<?= h($emailSearch ?? '') ?>" 
                   placeholder="Szukaj po emailu...">
        </div>
        
        <select name="role" class="filter-select">
            <option value="">Wszystkie role</option>
            <?php foreach ($roles as $rid => $rname): ?>
                <option value="<?= $rid ?>" <?= $roleFilter === $rid ? 'selected' : '' ?>>
                    <?= h($rname) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="search-btn">
            <span class="material-symbols-outlined">filter_list</span>
        </button>

        <?php if ($emailSearch || $roleFilter): ?>
            <a href="/users" class="clear-btn">✕</a>
        <?php endif; ?>
    </form>

    <p class="users-count">Znaleziono: <?= $totalUsers ?> użytkowników</p>

    <?php if (empty($users)): ?>
        <div class="repo-empty-state">
            <span class="material-symbols-outlined repo-empty-icon">group</span>
            <p class="repo-empty-text">Brak użytkowników spełniających kryteria.</p>
        </div>
    <?php else: ?>
        <div class="book-list">
            <?php foreach ($users as $user): ?>
                <?php
                    $userId = (int)$user['id'];
                    $userEmail = (string)$user['email'];
                    $userRoleId = (int)$user['role_id'];
                    $userRoleName = (string)($user['role_name'] ?? 'Nieznana');
                    $userBranches = $user['branches'] ?? [];
                    $isLibrarian = $userRoleId === Config::ROLE_LIBRARIAN;
                ?>
                <article class="repo-card user-card">
                    <div class="repo-card-content user-card-header">
                        <h3 class="repo-book-title"><?= h($userEmail) ?></h3>
                        <p class="repo-book-author">
                            <strong>Rola:</strong> <?= h($userRoleName) ?>
                            &nbsp;•&nbsp;
                            <strong>ID:</strong> <?= $userId ?>
                        </p>

                        <?php if ($isLibrarian && !empty($userBranches)): ?>
                            <p class="repo-book-author user-branches">
                                <strong>Oddziały:</strong>
                                <?php foreach ($userBranches as $i => $b): ?>
                                    <?= $i > 0 ? ', ' : '' ?><?= h($b['label'] ?? '') ?>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="user-card-actions">
                        <form method="POST" action="/users/role" class="user-action-form">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="user_id" value="<?= $userId ?>">
                            <select name="role_id" class="filter-select">
                                <?php foreach ($roles as $rid => $rname): ?>
                                    <option value="<?= $rid ?>" <?= $rid === $userRoleId ? 'selected' : '' ?>>
                                        <?= h($rname) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm">Zmień rolę</button>
                        </form>

                        <?php if ($isLibrarian): ?>
                            <form method="POST" action="/users/assign-branch" class="user-action-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="user_id" value="<?= $userId ?>">
                                <select name="branch_id" class="filter-select">
                                    <option value="">-- Wybierz oddział --</option>
                                    <?php foreach ($branches as $b): ?>
                                        <option value="<?= (int)$b['id'] ?>">
                                            <?= h($b['label'] ?? ($b['city'] . ', ' . $b['name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline">Dodaj oddział</button>
                            </form>

                            <?php if (!empty($userBranches)): ?>
                                <form method="POST" action="/users/remove-branch" class="user-action-form">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="user_id" value="<?= $userId ?>">
                                    <select name="branch_id" class="filter-select">
                                        <?php foreach ($userBranches as $b): ?>
                                            <option value="<?= (int)$b['id'] ?>">
                                                <?= h($b['label'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-danger">Usuń oddział</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-wrapper">
            <?php if ($currentPage > 1): ?>
                <a href="<?= $baseUrl ?>page=<?= $currentPage - 1 ?><?= $filterQuery ? '&' . $filterQuery : '' ?>" class="btn btn-sm btn-outline">
                    ← Poprz.
                </a>
            <?php else: ?>
                <button class="btn btn-sm btn-disabled" disabled>← Poprz.</button>
            <?php endif; ?>

            <span class="page-info">Strona <?= $currentPage ?> z <?= $totalPages ?></span>

            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= $baseUrl ?>page=<?= $currentPage + 1 ?><?= $filterQuery ? '&' . $filterQuery : '' ?>" class="btn btn-sm btn-outline">
                    Nast. →
                </a>
            <?php else: ?>
                <button class="btn btn-sm btn-disabled" disabled>Nast. →</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>

</div>