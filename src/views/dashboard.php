<div class="dashboard-container">

    <div class="dashboard-hero">
        <h1 class="hero-title">
            Witaj w Bookoria!
        </h1>
        <p class="hero-subtitle">
            <?php if ($role_id == 1): ?>
                Panel Administratora - pełna kontrola nad systemem.
            <?php elseif ($role_id == 2): ?>
                Panel Bibliotekarza - zarządzaj księgozbiorem.
            <?php else: ?>
                Zarządzaj swoimi wypożyczeniami w prosty sposób.
            <?php endif; ?>
        </p>
    </div>

    <div class="dashboard-grid">

        <?php if ($role_id == 1): ?>

            <a href="/users" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-warning">
                        <span class="material-symbols-outlined">admin_panel_settings</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Użytkownicy</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Zarządzaj kontami, rolami i dostępem do systemu.
                    </p>
                </article>
            </a>

            <a href="/repository" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-primary">
                        <span class="material-symbols-outlined">library_books</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Repozytorium</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Podgląd całego księgozbioru.
                    </p>
                </article>
            </a>

        <?php elseif ($role_id == 2): ?>

            <a href="/add-book" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-success">
                        <span class="material-symbols-outlined">add_circle</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Dodaj książkę</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Wprowadź nową pozycję do katalogu.
                    </p>
                </article>
            </a>

            <a href="/repository" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-primary">
                        <span class="material-symbols-outlined">library_books</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Księgozbiór</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Przeglądaj katalog i zarządzaj egzemplarzami.
                    </p>
                </article>
            </a>

            <a href="/circulation" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-warning">
                        <span class="material-symbols-outlined">assignment</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Wydania i zwroty</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Obsłuż rezerwacje, wydawaj książki i przyjmuj zwroty.
                    </p>
                </article>
            </a>

        <?php else: ?>

            <a href="/repository" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-primary">
                        <span class="material-symbols-outlined">library_books</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Repozytorium</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Przeglądaj katalog książek, sprawdzaj dostępność i szukaj nowości.
                    </p>
                </article>
            </a>

            <a href="/profile" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-profile">
                        <span class="material-symbols-outlined">person</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Twój Profil</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Sprawdź historię wypożyczeń i swoje dane.
                    </p>
                </article>
            </a>

        <?php endif; ?>

    </div>
</div>
