<?php
$branches = $branches ?? [];

$isAvailable = false;
foreach ($branches as $br) {
    if ((int)($br['count'] ?? 0) > 0) { $isAvailable = true; break; }
}

$statusClass = $isAvailable ? 'status-available' : 'status-unavailable';
$statusIcon  = $isAvailable ? 'check_circle' : 'cancel';
$statusText  = $isAvailable ? 'Dostępna w wybranych oddziałach' : 'Brak dostępnych egzemplarzy';

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$bookId = (int)($book['id'] ?? 0);

$defaultBranchId = 0;
foreach ($branches as $br) {
    if ((int)($br['count'] ?? 0) > 0) {
        $defaultBranchId = (int)($br['branch_id'] ?? 0);
        if ($defaultBranchId > 0) break;
    }
}
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
          <img src="<?= h($book['cover_url']) ?>" alt="Okładka" class="book-cover-img">
        <?php else: ?>
          <div class="placeholder-cover">
            <span class="material-symbols-outlined">book_2</span>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="book-info-column">
      <header class="book-header">
        <h1 class="book-title"><?= h($book['title'] ?? '-') ?></h1>
        <p class="book-author"><?= h($book['author'] ?? 'Autor nieznany') ?></p>
      </header>

      <div class="book-description">
        <p><?= nl2br(h($book['description'] ?? 'Brak opisu.')) ?></p>
      </div>

      <div class="book-meta-grid">
        <div class="meta-box">
          <span class="meta-label">Rok wydania</span>
          <span class="meta-value"><?= h($book['publication_year'] ?? '-') ?></span>
        </div>
        <div class="meta-box">
          <span class="meta-label">ISBN</span>
          <span class="meta-value"><?= h($book['isbn13'] ?? '-') ?></span>
        </div>
      </div>

      <div class="availability-box">
        <div class="status-line <?= h($statusClass) ?>">
          <span class="material-symbols-outlined"><?= h($statusIcon) ?></span>
          <span><?= h($statusText) ?></span>
        </div>
      </div>

      <?php if (!empty($branches)): ?>
        <form method="POST" action="/reservation/create">
          <input type="hidden" name="book_id" value="<?= $bookId ?>">
          <input type="hidden" name="branch_id" id="selectedBranchId" value="<?= (int)$defaultBranchId ?>">

          <div class="branches-list" id="branchesList">
            <?php foreach ($branches as $branch): ?>
              <?php
                $count = (int)($branch['count'] ?? 0);
                $isBranchAvailable = $count > 0;

                $branchId = (int)($branch['branch_id'] ?? 0);

                $branchStatusClass = $isBranchAvailable ? 'status-green' : 'status-red';
                $isSelected = $isBranchAvailable && $branchId > 0 && $branchId === $defaultBranchId;

                $rowAttrs = $isBranchAvailable && $branchId > 0
                  ? 'data-branch-id="' . $branchId . '" role="button" tabindex="0" aria-pressed="' . ($isSelected ? 'true' : 'false') . '"'
                  : 'aria-disabled="true"';
              ?>
              <div class="branch-row <?= h($branchStatusClass) ?><?= $isSelected ? ' is-selected' : '' ?>" <?= $rowAttrs ?>>
                <div class="branch-name">
                  <span class="material-symbols-outlined">location_on</span>
                  <?= h($branch['label'] ?? '-') ?>
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

          <div class="action-area">
            <?php if ($isAvailable && $bookId > 0): ?>
              <button class="btn-primary-lg" type="submit" id="reserveBtn" <?= $defaultBranchId > 0 ? '' : 'disabled' ?>>
                Zarezerwuj w oddziale
              </button>
            <?php else: ?>
              <button class="btn-disabled-lg" type="button" disabled>Niedostępna</button>
            <?php endif; ?>
          </div>
        </form>
      <?php else: ?>
        <div class="action-area">
          <button class="btn-disabled-lg" type="button" disabled>Niedostępna</button>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="/js/book-reserve.js" defer></script>
