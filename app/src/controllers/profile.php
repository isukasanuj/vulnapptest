<?php
declare(strict_types=1);

function ctl_profile(): void
{
    $user = require_login();
    layout_top('Profile', $user);
    ?>
    <h1>My profile</h1>
    <div class="card">
      <form method="post" action="/profile/update">
        <label>Name</label>
        <input name="name" value="<?= e($user['name']) ?>">
        <label>Email</label>
        <input name="email" value="<?= e($user['email']) ?>">
        <div style="margin-top:16px"><button type="submit">Save</button></div>
      </form>
    </div>
    <div class="card">
      <h2>Current account</h2>
      <table>
        <tr><th>ID</th><td><?= (int) $user['id'] ?></td></tr>
        <tr><th>Role</th><td><?= e($user['role']) ?></td></tr>
        <tr><th>Active</th><td><?= ((int) $user['is_active'] === 1) ? 'yes' : 'no' ?></td></tr>
        <tr><th>Dealer type</th><td><?= (int) $user['dealer_account_type'] ?></td></tr>
      </table>
    </div>
    <?php
    layout_bottom();
}

function ctl_profile_update(): void
{
    $user = require_login();
    $p = params();

    /* ---- MASS ASSIGNMENT (added requirement) ----------------------------
     * Every submitted key that matches a real column is written back to the
     * user's row. The form only exposes name/email, but the handler will
     * happily accept role, is_active, dealer_account_type, etc. A normal
     * user can escalate to admin with:
     *   POST /profile/update  {"name":"x","role":"admin"}
     * Columns are bound as parameters, so this is mass assignment, not SQLi. */
    $columns = ['name', 'email', 'role', 'is_active', 'dealer_account_type', 'password'];

    $sets = [];
    $vals = [];
    foreach ($p as $key => $value) {
        if (in_array($key, $columns, true)) {
            if ($key === 'password') {
                $value = password_hash((string) $value, PASSWORD_BCRYPT);
            }
            $sets[] = "{$key} = ?";
            $vals[] = $value;
        }
    }

    if ($sets) {
        $vals[] = (int) $user['id'];
        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?';
        db()->prepare($sql)->execute($vals);
    }

    flash('Profile updated.');
    redirect('/profile');
}
