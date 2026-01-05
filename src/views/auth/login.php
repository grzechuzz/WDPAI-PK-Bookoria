<?php require_once __DIR__ . '/../../core/Csrf.php'; ?>
<section class="login-wrapper">
    <article class="card">
        
        <header>
            <h1 class="card-title">
                Zaloguj się
            </h1>
        </header>

        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="error-msg" style="color:#155724;background:rgba(40,167,69,0.12);" role="status">
                <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-msg" role="alert">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <?= Csrf::field() ?>
            
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