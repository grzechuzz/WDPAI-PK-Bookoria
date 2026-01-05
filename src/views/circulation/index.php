<?php
$branches = $branches ?? [];
$readyReservations = $readyReservations ?? [];
$activeLoans = $activeLoans ?? [];
$hasBranches = $hasBranches ?? false;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function formatDateTime(?string $ts, string $tz = 'Europe/Warsaw'): string {
    if (!$ts) return '-';
    try {
        $dt = new DateTimeImmutable($ts);
        $dt = $dt->setTimezone(new DateTimeZone($tz));
        return $dt->format('Y-m-d H:i');
    } catch (Throwable $e) {
        return h($ts);
    }
}
?>

<div class="repo-container circulation-page">

    <h1 class="repo-header">Wydania i zwroty</h1>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="error-msg" style="color:#155724;background:rgba(40,167,69,0.12);" role="status">
            <?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="error-msg" role="alert">
            <?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <?php if (!$hasBranches): ?>
        <div class="repo-empty-state">
            <span class="material-symbols-outlined repo-empty-icon">warning</span>
            <p class="repo-empty-text">
                Nie jesteś przypisany do żadnego oddziału.<br>
                Skontaktuj się z administratorem, aby uzyskać dostęp.
            </p>
        </div>
    <?php else: ?>

        <section class="circulation-section">
            <h2 class="section-title">
                <span class="material-symbols-outlined">notifications_active</span>
                Rezerwacje gotowe do odbioru
            </h2>

            <?php if (empty($readyReservations)): ?>
                <div class="repo-empty-state" style="padding: 2rem;">
                    <span class="material-symbols-outlined repo-empty-icon">check_circle</span>
                    <p class="repo-empty-text">Brak rezerwacji do wydania.</p>
                </div>
            <?php else: ?>
                <div class="book-list">
                    <?php foreach ($readyReservations as $r): ?>
                        <?php
                            $reservationId = (int)($r['id'] ?? 0);
                            $readyUntil = $r['ready_until'] ?? null;
                            $isExpiringSoon = false;
                            if ($readyUntil) {
                                try {
                                    $until = new DateTimeImmutable($readyUntil);
                                    $now = new DateTimeImmutable();
                                    $diff = $until->getTimestamp() - $now->getTimestamp();
                                    $isExpiringSoon = $diff < 3600 * 6; 
                                } catch (Throwable $e) {}
                            }
                        ?>
                        <article class="repo-card <?= $isExpiringSoon ? 'card-warning' : '' ?>">
                            <div class="repo-card-content">
                                <h3 class="repo-book-title"><?= h($r['title'] ?? '-') ?></h3>
                                <p class="repo-book-author">
                                    <strong>Czytelnik:</strong> <?= h($r['user_email'] ?? '-') ?>
                                </p>
                                <p class="repo-book-author">
                                    <strong>Oddział:</strong> <?= h($r['branch_label'] ?? '-') ?>
                                    • <strong>Egzemplarz:</strong> <?= h($r['copy_code'] ?? '-') ?>
                                </p>
                                <div class="repo-book-status <?= $isExpiringSoon ? 'status-unavailable' : 'status-available' ?>">
                                    <span class="material-symbols-outlined status-icon">
                                        <?= $isExpiringSoon ? 'schedule' : 'check_circle' ?>
                                    </span>
                                    Odbierz do: <?= h(formatDateTime($readyUntil)) ?>
                                    <?php if ($isExpiringSoon): ?>
                                        <span style="margin-left:0.5rem;font-weight:700;color:var(--danger);">
                                            (wkrótce wygaśnie!)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="repo-card-actions">
                                <form method="POST" action="/circulation/issue">
                                    <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">
                                    <button class="btn btn-sm" type="submit">
                                        Wydaj książkę
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="circulation-section" style="margin-top: 3rem;">
            <h2 class="section-title">
                <span class="material-symbols-outlined">assignment</span>
                Aktywne wypożyczenia
            </h2>

            <?php if (empty($activeLoans)): ?>
                <div class="repo-empty-state" style="padding: 2rem;">
                    <span class="material-symbols-outlined repo-empty-icon">inventory_2</span>
                    <p class="repo-empty-text">Brak aktywnych wypożyczeń w Twoich oddziałach.</p>
                </div>
            <?php else: ?>
                <div class="book-list">
                    <?php foreach ($activeLoans as $loan): ?>
                        <?php
                            $loanId = (int)($loan['loan_id'] ?? 0);
                            $isOverdue = (bool)($loan['is_overdue'] ?? false);
                            $daysOverdue = (int)($loan['days_overdue'] ?? 0);
                        ?>
                        <article class="repo-card <?= $isOverdue ? 'card-danger' : '' ?>">
                            <div class="repo-card-content">
                                <h3 class="repo-book-title"><?= h($loan['title'] ?? '-') ?></h3>
                                <p class="repo-book-author">
                                    <strong>Czytelnik:</strong> <?= h($loan['user_email'] ?? '-') ?>
                                </p>
                                <p class="repo-book-author">
                                    <strong>Oddział:</strong> <?= h($loan['branch_label'] ?? '-') ?>
                                    <strong>Egzemplarz:</strong> <?= h($loan['copy_code'] ?? '-') ?>
                                </p>
                                <div class="repo-book-status <?= $isOverdue ? 'status-unavailable' : 'status-available' ?>">
                                    <span class="material-symbols-outlined status-icon">
                                        <?= $isOverdue ? 'error' : 'schedule' ?>
                                    </span>
                                    Termin: <?= h(formatDateTime($loan['due_at'] ?? null)) ?>
                                    <?php if ($isOverdue): ?>
                                        <span style="margin-left:0.5rem;font-weight:700;color:var(--danger);">
                                            (zaległość: <?= $daysOverdue ?> dni)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="repo-card-actions">
                                <form method="POST" action="/circulation/return">
                                    <input type="hidden" name="loan_id" value="<?= $loanId ?>">
                                    <button class="btn btn-sm btn-outline" type="submit">
                                        Zarejestruj zwrot
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    <?php endif; ?>

</div>