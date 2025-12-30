<div class="repo-container">
    
    <h1 class="repo-header">
        Wyszukaj książkę
    </h1>

    <form action="/repository" method="GET" class="search-wrapper">
        <div class="search-input-group">
            <span class="material-symbols-outlined search-icon">search</span>
            <input type="text" name="search" class="search-input" 
                   value="<?= htmlspecialchars($search ?? '') ?>" 
                   placeholder="Tytuł, autor, ISBN...">
        </div>
        
        <button type="submit" class="search-btn">
            <span class="material-symbols-outlined">search</span>
        </button>

        <?php if (!empty($search)): ?>
            <a href="/repository" class="clear-btn" title="Wyczyść filtry">✕</a>
        <?php endif; ?>
    </form>

    <section class="book-list">
        <?php if (empty($books)): ?>
            <div class="repo-empty-state">
                <span class="material-symbols-outlined repo-empty-icon">search_off</span>
                <p class="repo-empty-text">Nie znaleziono książek pasujących do zapytania.</p>
            </div>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <article class="repo-card">
                    
                    <div class="repo-card-image">
                        <?php if (!empty($book['cover_url'])): ?>
                            <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="Okładka">
                        <?php else: ?>
                            <span class="material-symbols-outlined placeholder-icon">book_2</span>
                        <?php endif; ?>
                    </div>

                    <div class="repo-card-content">
                        <h2 class="repo-book-title">
                            <?= htmlspecialchars($book['title']) ?>
                        </h2>
                        
                        <p class="repo-book-author">
                            <?= htmlspecialchars($book['author'] ?? 'Autor nieznany') ?>
                        </p>
                        
                        <?php 
                            $availableCount = (int)$book['total_available'];
                            $isAvail = $availableCount > 0;
                            
            
                            $statusClass = $isAvail ? 'status-available' : 'status-unavailable';
                            $statusIcon = $isAvail ? 'check_circle' : 'cancel';
                            $statusText = $isAvail ? 'Dostępna' : 'Brak dostępnych egzemplarzy';
                        ?>
                        
                        <div class="repo-book-status <?= $statusClass ?>">
                            <span class="material-symbols-outlined status-icon"><?= $statusIcon ?></span>
                            <?= $statusText ?>
                        </div>
                    </div>

                    <div class="repo-card-actions">
                        <a href="/book?id=<?= $book['id'] ?>" class="btn btn-sm btn-outline">
                            Szczegóły
                        </a>
                    </div>

                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if ($totalPages > 1): ?>
    <div class="pagination-wrapper">
        
        <?php 
            $baseLink = '/repository?page=';
            $searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
        ?>

        <?php if ($currentPage > 1): ?>
            <a href="<?= $baseLink . ($currentPage - 1) . $searchParam ?>" class="btn btn-sm btn-outline">
                ← Poprz.
            </a>
        <?php else: ?>
            <button class="btn btn-sm btn-disabled" disabled>
                ← Poprz.
            </button>
        <?php endif; ?>

        <span class="page-info">
            Strona <?= $currentPage ?> z <?= $totalPages ?>
        </span>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= $baseLink . ($currentPage + 1) . $searchParam ?>" class="btn btn-sm btn-outline">
                Nast. →
            </a>
        <?php else: ?>
             <button class="btn btn-sm btn-disabled" disabled>
                Nast. →
            </button>
        <?php endif; ?>

    </div>
    <?php endif; ?>

</div>