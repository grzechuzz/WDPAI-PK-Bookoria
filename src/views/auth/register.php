<section class="login-wrapper">
    <article class="card">
        
        <header>
            <h1 class="card-title">
                Rejestracja
            </h1>
        </header>

        <?php if (!empty($error)): ?>
            <div class="error-msg" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/register" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="email@example.com" required>
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" placeholder="Minimum 8 znaków" required>
            </div>

            <button type="submit" class="btn">Zarejestruj się</button>
        </form>

        <footer class="card-footer">
            <p>Masz już konto? <a href="/login" class="text-link">Zaloguj się</a></p>
        </footer>

    </article>
</section>