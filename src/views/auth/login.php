<section class="login-wrapper">
    <article class="card">
        
        <header>
            <h1 class="card-title">
                Zaloguj się
            </h1>
        </header>

        <?php if (!empty($error)): ?>
            <div class="error-msg" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="email@example.com" required>
            </div>

            <div class="form-group">
                <label for="password">Hasło</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn">Zaloguj się</button>
        </form>

        <footer class="card-footer">
            <p>Nie masz konta? <a href="/register" class="text-link">Zarejestruj się</a></p>
        </footer>

    </article>
</section>