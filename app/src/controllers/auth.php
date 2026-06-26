<?php
declare(strict_types=1);

function ctl_login(): void
{
    if (current_user()) {
        redirect('/dashboard');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = (string) input('email', '');
        $password = (string) input('password', '');

        /* ---- Login uses a PARAMETERIZED query on purpose --------------------
         * Per spec, the SQL-injection practice target lives in the INSERT path
         * (announcement store), NOT here. Authentication is safe.            */
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        // NOTE: no rate limiting / lockout (finding #02) — brute force is possible.
        if ($u && (int) $u['is_active'] === 1 && password_verify($password, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $u['id'];

            // Finding #05 REMOVED: bind this session as the only valid one.
            $up = db()->prepare('UPDATE users SET current_session_id = ? WHERE id = ?');
            $up->execute([session_id(), $u['id']]);

            redirect('/dashboard');
        }

        // Generic, constant message — login itself does not enumerate users.
        flash('Invalid credentials.');
        redirect('/login');
    }

    $err = flash();
    auth_top('Sign in');
    ?>
      <h1>Welcome back</h1>
      <p class="sub">Sign in to your CloudInsure workspace.</p>
      <?php if ($err): ?><div class="err"><?= e($err) ?></div><?php endif; ?>
      <form method="post" action="/login">
        <label>Work email</label>
        <input name="email" type="email" placeholder="you@company.com" autofocus required>
        <label>Password</label>
        <input name="password" type="password" placeholder="••••••••" required>
        <div class="row">
          <label style="display:flex;align-items:center;gap:7px;margin:0;font-weight:400;color:#64748b">
            <input type="checkbox" style="width:auto"> Remember me
          </label>
          <a href="/forgot-password">Forgot password?</a>
        </div>
        <button type="submit">Sign in</button>
      </form>
      <p class="muted" style="margin-top:18px;font-size:12.5px;text-align:center">
        Protected workspace · Unauthorized access is prohibited.
      </p>
    <?php
    auth_bottom();
}

function ctl_logout(): void
{
    $u = current_user();
    if ($u) {
        $up = db()->prepare('UPDATE users SET current_session_id = NULL WHERE id = ?');
        $up->execute([$u['id']]);
    }
    session_destroy();
    redirect('/login');
}

function ctl_forgot_password(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = (string) input('email', '');

        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $exists = (bool) $stmt->fetch();

        /* ---- Finding #03: USER ENUMERATION ----------------------------------
         * The response differs depending on whether the email is registered. */
        if ($exists) {
            $token = bin2hex(random_bytes(16));
            $ins = db()->prepare('INSERT INTO password_resets (email, token) VALUES (?, ?)');
            $ins->execute([$email, $token]);
            flash('A password reset link has been sent to ' . $email . '.');
        } else {
            flash('No account is registered with that email address.');
        }
        redirect('/forgot-password');
    }

    $msg = flash();
    auth_top('Reset password');
    ?>
      <h1>Reset your password</h1>
      <p class="sub">Enter your account email and we'll send you a reset link.</p>
      <?php if ($msg): ?><div class="err" style="background:#eff6ff;border-color:#bfdbfe;color:#1e40af"><?= e($msg) ?></div><?php endif; ?>
      <form method="post" action="/forgot-password">
        <label>Work email</label>
        <input name="email" type="email" placeholder="you@company.com" required autofocus>
        <button type="submit">Send reset link</button>
      </form>
      <p class="muted" style="margin-top:18px;text-align:center"><a href="/login">← Back to sign in</a></p>
    <?php
    auth_bottom();
}
