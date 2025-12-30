<?php
$branches = $branches ?? [];

// dostępność książki: jeśli jakikolwiek oddział ma > 0
$isAvailable = false;
foreach ($branches as $br) {
    if ((int)($br['count'] ?? 0) > 0) {
        $isAvailable = true;
        break;
    }
}

$statusClass = $isAvailable ? 'status-available' : 'status-unavailable';
$statusIcon  = $isAvailable ? 'check_circle' : 'cancel';
$statusText  = $isAvailable ? 'Dostępna w wybranych oddziałach' : 'Brak dostępnych egzemplarzy';
?>

<div class="book-details-wrapper">

    <div class="details-top-bar">
        <a href="/repository" class="back-link">
            <span class="material-symbols-outlined">arrow_back</span>
            Wróć do listy
        </a>
    </div>

    <div class="book-grid">

        <div class="book-cover-column">
            <div class="cover-container">
                <?php if (!empty($book['cover_url'])): ?>
                    <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="Okładka" class="book-cover-img">
                <?php else: ?>
                    <div class="placeholder-cover">
                        <span class="material-symbols-outlined">book_2</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="book-info-column">

            <header class="book-header">
                <h1 class="book-title"><?= htmlspecialchars($book['title']) ?></h1>
                <p class="book-author"><?= htmlspecialchars($book['author'] ?? 'Autor nieznany') ?></p>
            </header>

            <div class="book-description">
                <p><?= nl2br(htmlspecialchars($book['description'] ?? 'Brak opisu.')) ?></p>
            </div>

            <div class="book-meta-grid">
                <div class="meta-box">
                    <span class="meta-label">Rok wydania</span>
                    <span class="meta-value"><?= htmlspecialchars($book['publication_year'] ?? '-') ?></span>
                </div>
                <div class="meta-box">
                    <span class="meta-label">ISBN</span>
                    <span class="meta-value"><?= htmlspecialchars($book['isbn13'] ?? '-') ?></span>
                </div>
            </div>

            <div class="availability-box">
                <div class="status-line <?= $statusClass ?>">
                    <span class="material-symbols-outlined"><?= $statusIcon ?></span>
                    <span><?= $statusText ?></span>
                </div>
            </div>

            <?php if (!empty($branches)): ?>
                <div class="branches-list">
                    <?php foreach ($branches as $branch): ?>
                        <?php
                            $count = (int)($branch['count'] ?? 0);
                            $isBranchAvailable = $count > 0;
                            $branchStatusClass = $isBranchAvailable ? 'status-green' : 'status-red';
                        ?>
                        <div class="branch-row <?= $branchStatusClass ?>">
                            <div class="branch-name">
                                <span class="material-symbols-outlined">location_on</span>
                                <?= htmlspecialchars($branch['label'] ?? '-') ?>
                            </div>

                            <div class="branch-right">
                                <?php if ($isBranchAvailable): ?>
                                    <span class="branch-available">Dostępne <?= $count ?></span>
                                <?php else: ?>
                                    <span class="branch-unavailable">Brak</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="action-area">
                <?php if ($isAvailable): ?>
                    <button class="btn-primary-lg" type="button">Zarezerwuj w oddziale</button>
                <?php else: ?>
                    <button class="btn-disabled-lg" type="button" disabled>Niedostępna</button>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
