<?php
$form = $form ?? ['title'=>'', 'authors'=>'', 'isbn13'=>'', 'publication_year'=>'', 'description'=>''];
$error = $error ?? null;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<section class="login-wrapper">
  <article class="card">
    <header style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
      <h1 class="card-title" style="margin:0;">Dodaj nową książkę</h1>
      <a href="/dashboard" class="text-link" style="font-weight:700; text-decoration:none;">✕</a>
    </header>

    <?php if (!empty($error)): ?>
      <div class="error-msg" role="alert">
        <?= h($error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="error-msg" style="color:#155724;background:rgba(40,167,69,0.12);" role="status">
        <?= h($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/add-book" enctype="multipart/form-data">
      <div class="form-group">
        <label for="title">Tytuł</label>
        <input id="title" name="title" type="text" placeholder="np. Diuna" required value="<?= h($form['title'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="authors">Autorzy</label>
        <input id="authors" name="authors" type="text" placeholder="np. Frank Herbert, Drugi Autor" required value="<?= h($form['authors'] ?? '') ?>">
        <small style="color: var(--text-muted); display:block; margin-top:0.35rem;">
          Wpisz autorów po przecinku.
        </small>
      </div>

      <div class="form-group">
        <label for="isbn13">ISBN</label>
        <input id="isbn13" name="isbn13" type="text" placeholder="9788373017238" required value="<?= h($form['isbn13'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="publication_year">Rok wydania</label>
        <input id="publication_year" name="publication_year" type="text" placeholder="np. 1965" value="<?= h($form['publication_year'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="description">Opis</label>
        <textarea
          id="description"
          name="description"
          rows="5"
          placeholder="Krótki opis książki..."
        ><?= h($form['description'] ?? '') ?></textarea>
      </div>

      <div class="form-group">
        <label for="cover">Okładka</label>
        <input id="cover" name="cover" type="file" accept="image/png,image/jpeg,image/webp">
        <small style="color: var(--text-muted); display:block; margin-top:0.35rem;">
          JPG/PNG, max 5MB.
        </small>
      </div>

      <button type="submit" class="btn">Dodaj książkę</button>
    </form>
  </article>
</section>
