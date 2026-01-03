<?php
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

function reservationStatusLabel(string $status): string {
    return match ($status) {
        'QUEUED' => 'W kolejce',
        'READY_FOR_PICKUP' => 'Gotowa do odbioru',
        'CANCELLED' => 'Anulowana',
        'EXPIRED' => 'Wygasła',
        'FULFILLED' => 'Zrealizowana',
        default => $status,
    };
}
?>

<div class="repo-container profile-page">

    <h1 class="repo-header profile-title">Twój profil</h1>

    <?php if ($limitReached): ?>
        <div class="error-msg" role="alert">
            Osiągnięto limit wypożyczeń (<?= $activeLoansCount ?> / <?= $maxLoans ?>).
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
                        $canRenew = !$isOverdue && $renewals === 0;
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
                            <?php if ($canRenew): ?>
                                <button class="btn btn-sm btn-outline" type="button" disabled title="Akcja do wdrożenia">
                                    Przedłuż (1x)
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-disabled" type="button" disabled>
                                    Przedłużono / niedostępne
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
                        $isReady = $status === 'READY_FOR_PICKUP';
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
                                <?= h(reservationStatusLabel($status)) ?>
                                <?php if ($isReady): ?>
                                    • Odbierz do: <?= h(formatDateTime($r['ready_until'] ?? null)) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="repo-card-actions profile-actions">
                            <button class="btn btn-sm btn-outline" type="button" disabled title="Akcja do wdrożenia">
                                Anuluj
                            </button>
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
                                <?= h(reservationStatusLabel((string)($r['status'] ?? ''))) ?>
                                • Utworzono: <?= h(formatDateTime($r['created_at'] ?? null)) ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>
