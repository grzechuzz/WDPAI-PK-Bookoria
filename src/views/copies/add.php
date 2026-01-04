<?php
$form = $form ?? ['book_id' => '', 'branch_id' => '', 'inventory_code' => ''];
$error = $error ?? null;
$branches = $branches ?? [];

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$selectedBranchId = (string)($form['branch_id'] ?? '');
?>

<section class="login-wrapper">
  <article class="card">
    <header style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
      <h1 class="card-title" style="margin:0;">Dodaj egzemplarz</h1>
      <a href="/dashboard" class="text-link" style="font-weight:700; text-decoration:none;">✕</a>
    </header>

    <?php if (!empty($error)): ?>
      <div class="error-msg" role="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="error-msg" style="color:#155724;background:rgba(40,167,69,0.12);" role="status">
        <?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/copy/add">
      <div class="form-group">
        <label for="book_id">ID książki</label>
        <input
          id="book_id"
          name="book_id"
          type="text"
          required
          value="<?= h($form['book_id'] ?? '') ?>"
        >
      </div>

      <div class="form-group">
        <label for="branch_id">Oddział</label>
        <select id="branch_id" name="branch_id" required>
          <option value="" <?= $selectedBranchId === '' ? 'selected' : '' ?> disabled>Wybierz oddział…</option>

          <?php foreach ($branches as $br): ?>
            <?php
              $id = (int)($br['id'] ?? 0);
              $label = (string)($br['label'] ?? ($br['city'] ?? '') . ', ' . ($br['name'] ?? ''));
              if ($id < 1) continue;

              $sel = ((string)$id === $selectedBranchId) ? 'selected' : '';
            ?>
            <option value="<?= $id ?>" <?= $sel ?>>
              <?= h($label) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <?php if (empty($branches)): ?>
          <small style="color: var(--text-muted); display:block; margin-top:0.35rem;">
            Brak oddziałów w bazie danych.
          </small>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label for="inventory_code">Kod inwentarzowy</label>
        <input
          id="inventory_code"
          name="inventory_code"
          type="text"
          placeholder="np. 4572452331"
          required
          value="<?= h($form['inventory_code'] ?? '') ?>"
        >
      </div>

      <button type="submit" class="btn">Dodaj egzemplarz</button>
    </form>
  </article>
</section>
