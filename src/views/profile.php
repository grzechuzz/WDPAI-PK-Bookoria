<?php
require_once __DIR__ . '/../core/Config.php';

$activeLoans = $activeLoans ?? [];
$activeReservations = $activeReservations ?? [];
$historyLoans = $historyLoans ?? [];
$historyReservations = $historyReservations ?? [];

$limitReached = (bool)($limitReached ?? false);
$activeLoansCount = (int)($activeLoansCount ?? 0);
$maxLoans = (int)($maxLoans ?? 0);

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function formatDateTime(?string $ts, string $tz = 'Europe/Warsaw'): string {
    if (!$ts) return '-';
    try {
        $dt = new DateTimeImmutable($ts);
        $dt = $dt->setTimezone(new DateTimeZone($tz));
        return $dt->format('Y-m-d H:i');
    } catch (Throwable $e) {
        $t = strtotime($ts);
        if ($t === false) return h($ts);
        $dt = (new DateTimeImmutable('@' . $t))->setTimezone(new DateTimeZone($tz));
        return $dt->format('Y-m-d H:i');
    }
}

function loanStatusLabel(array $l): string {
    $returnedAt = $l['returned_at'] ?? null;
    if ($returnedAt) return 'Zwrócone';

    $isOverdue = (bool)($l['is_overdue'] ?? false);
    return $isOverdue ? 'Po terminie' : 'Aktywne';
}
?>

<div class="repo-container profile-page">

    <h1 class="repo-header profile-title">Twój profil</h1>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="error-msg" style="color:#155724;background:rgba(40,167,69,0.12);">
            <?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="error-msg">
            <?= h($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
    <?php endif; ?>

    <?php if ($limitReached): ?>
        <div class="error-msg" role="alert" style="background:rgba(255,193,7,0.15);color:#856404;">
            <span class="material-symbols-outlined" style="vertical-align:middle;margin-right:0.5rem;">warning</span>
            Osiągnięto limit wypożyczeń (<?= $activeLoansCount ?> / <?= $maxLoans ?>). 
            Nie możesz wypożyczyć więcej książek.
        </div>
    <?php endif; ?>

    <div class="profile-tabs" role="tablist" aria-label="Sekcje profilu">
        <button class="profile-tab tab-btn is-active"
                type="button"
                data-tab="loans"
                role="tab"
                aria-selected="true">
            Wypożyczenia
        </button>

        <button class="profile-tab tab-btn"
                type="button"
                data-tab="reservations"
                role="tab"
                aria-selected="false">
            Rezerwacje
        </button>

        <button class="profile-tab tab-btn"
                type="button"
                data-tab="history"
                role="tab"
                aria-selected="false">
            Historia
        </button>
    </div>

    <section class="profile-panel" data-tab-panel="loans">
        <h2 class="profile-section-title">Wypożyczenia</h2>

        <?php if (empty($activeLoans)): ?>
            <div class="repo-empty-state">
                <span class="material-symbols-outlined repo-empty-icon">inventory_2</span>
                <p class="repo-empty-text">Nie masz aktywnych wypożyczeń.</p>
            </div>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($activeLoans as $loan): ?>
                    <?php
                        $status = loanStatusLabel($loan);
                        $isOverdue = (bool)($loan['is_overdue'] ?? false);
                        $renewals = (int)($loan['renewals_count'] ?? 0);
                        $canRenew = !$isOverdue && $renewals < Config::MAX_RENEWALS;
                        $loanId = (int)($loan['loan_id'] ?? $loan['id'] ?? 0);
                    ?>

                    <article class="repo-card">
                        <div class="repo-card-content">
                            <h3 class="repo-book-title"><?= h($loan['title'] ?? '-') ?></h3>

                            <p class="repo-book-author">
                                Oddział: <?= h($loan['branch_label'] ?? '-') ?>
                                • Egzemplarz: <?= h($loan['copy_code'] ?? $loan['copy_id'] ?? '-') ?>
                            </p>

                            <div class="repo-book-status <?= $isOverdue ? 'status-unavailable' : 'status-available' ?>">
                                <span class="material-symbols-outlined status-icon">
                                    <?= $isOverdue ? 'error' : 'check_circle' ?>
                                </span>
                                <?= h($status) ?>
                                • Termin: <?= h(formatDateTime($loan['due_at'] ?? null)) ?>
                                <?php if ($isOverdue): ?>
                                    • Zaległość: <?= (int)($loan['days_overdue'] ?? 0) ?> dni
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="repo-card-actions profile-actions">
                            <?php if ($canRenew && $loanId > 0): ?>
                                <form method="POST" action="/loan/renew">
                                    <input type="hidden" name="loan_id" value="<?= $loanId ?>">
                                    <button class="btn btn-sm btn-outline" type="submit">
                                        Przedłuż
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-disabled" type="button" disabled title="Już przedłużone lub po terminie">
                                    <?= $renewals >= Config::MAX_RENEWALS ? 'Przedłużono' : 'Niedostępne' ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    
    <section class="profile-panel" data-tab-panel="reservations" hidden>
        <h2 class="profile-section-title">Rezerwacje</h2>

        <?php if (empty($activeReservations)): ?>
            <div class="repo-empty-state">
                <span class="material-symbols-outlined repo-empty-icon">bookmark</span>
                <p class="repo-empty-text">Nie masz aktywnych rezerwacji.</p>
            </div>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($activeReservations as $r): ?>
                    <?php
                        $status = (string)($r['status'] ?? '');
                        $isReady = $status === Config::RES_READY;
                        $reservationId = (int)($r['id'] ?? $r['reservation_id'] ?? 0);
                    ?>

                    <article class="repo-card">
                        <div class="repo-card-content">
                            <h3 class="repo-book-title"><?= h($r['title'] ?? '-') ?></h3>

                            <p class="repo-book-author">
                                Oddział: <?= h($r['branch_label'] ?? '-') ?>
                            </p>

                            <div class="repo-book-status <?= $isReady ? 'status-available' : '' ?>">
                                <span class="material-symbols-outlined status-icon">
                                    <?= $isReady ? 'notifications_active' : 'schedule' ?>
                                </span>
                                <?= h(Config::reservationStatusLabel($status)) ?>
                                <?php if ($isReady): ?>
                                    • Odbierz do: <?= h(formatDateTime($r['ready_until'] ?? null)) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="repo-card-actions profile-actions">
                            <?php if ($reservationId > 0): ?>
                                <form method="POST" action="/reservation/cancel" class="js-cancel-reservation">
                                    <input type="hidden" name="reservation_id" value="<?= $reservationId ?>">
                                    <button class="btn btn-sm btn-outline" type="submit">
                                        Anuluj
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-sm btn-disabled" type="button" disabled>
                                    Anuluj
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="profile-panel" data-tab-panel="history" hidden>
        <h2 class="profile-section-title">Historia</h2>

        <h3 class="profile-subtitle">Wypożyczenia</h3>
        <?php if (empty($historyLoans)): ?>
            <p class="profile-muted">Brak historii wypożyczeń.</p>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($historyLoans as $loan): ?>
                    <article class="repo-card">
                        <div class="repo-card-content">
                            <h3 class="repo-book-title"><?= h($loan['title'] ?? '-') ?></h3>
                            <p class="repo-book-author">
                                Oddział: <?= h($loan['branch_label'] ?? '-') ?>
                                • Egzemplarz: <?= h($loan['copy_code'] ?? $loan['copy_id'] ?? '-') ?>
                            </p>
                            <div class="repo-book-status">
                                <span class="material-symbols-outlined status-icon">history</span>
                                Zwrócono: <?= h(formatDateTime($loan['returned_at'] ?? null)) ?>
                                • Termin: <?= h(formatDateTime($loan['due_at'] ?? null)) ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <h3 class="profile-subtitle">Rezerwacje</h3>
        <?php if (empty($historyReservations)): ?>
            <p class="profile-muted">Brak historii rezerwacji.</p>
        <?php else: ?>
            <div class="book-list">
                <?php foreach ($historyReservations as $r): ?>
                    <article class="repo-card">
                        <div class="repo-card-content">
                            <h3 class="repo-book-title"><?= h($r['title'] ?? '-') ?></h3>
                            <p class="repo-book-author">
                                Oddział: <?= h($r['branch_label'] ?? '-') ?>
                            </p>
                            <div class="repo-book-status">
                                <span class="material-symbols-outlined status-icon">history</span>
                                <?= h(Config::reservationStatusLabel((string)($r['status'] ?? ''))) ?>
                                • Utworzono: <?= h(formatDateTime($r['created_at'] ?? null)) ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>