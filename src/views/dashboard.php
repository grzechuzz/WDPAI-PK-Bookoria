<?php
$role_id = $role_id ?? 0;
$is_admin = $is_admin ?? false;
$is_librarian = $is_librarian ?? false;
$is_reader = $is_reader ?? false;
?>

<div class="dashboard-container">

    <div class="dashboard-hero">
        <h1 class="hero-title">
            Witaj w Bookoria!
        </h1>
        <p class="hero-subtitle">
            <?php if ($is_admin): ?>
                Panel Administratora - zarządzaj katalogiem i użytkownikami.
            <?php elseif ($is_librarian): ?>
                Panel Bibliotekarza - obsługuj wypożyczenia i zarządzaj egzemplarzami.
            <?php else: ?>
                Zarządzaj swoimi wypożyczeniami w prosty sposób.
            <?php endif; ?>
        </p>
    </div>

    <div class="dashboard-grid">

        <?php if ($is_admin): ?>
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
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Repozytorium</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Przeglądaj katalog książek.
                    </p>
                </article>
            </a>

        <?php elseif ($is_librarian): ?>

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
                        Wydawaj książki i przyjmuj zwroty.
                    </p>
                </article>
            </a>

            <a href="/logout" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-danger">
                        <span class="material-symbols-outlined">logout</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Wyloguj się</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Zakończ sesję i wróć do strony logowania.
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
                        Przeglądaj katalog książek, sprawdzaj dostępność i rezerwuj.
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
                        Sprawdź wypożyczenia, rezerwacje i historię.
                    </p>
                </article>
            </a>

            <a href="/logout" class="dashboard-card">
                <article class="card">
                    <div class="card-icon icon-danger">
                        <span class="material-symbols-outlined">logout</span>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">Wyloguj się</h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Zakończ sesję i wróć do strony logowania.
                    </p>
                </article>
            </a>

        <?php endif; ?>

    </div>
</div>