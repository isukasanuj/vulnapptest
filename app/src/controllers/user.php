<?php
declare(strict_types=1);

function ctl_user_index(): void
{
    /* ---- Finding #03: VERTICAL PRIVILEGE ESCALATION / BROKEN ACCESS CONTROL
     * This is an administrative view, but it only calls require_login() —
     * NOT require_admin(). Any authenticated low-privilege user can list
     * every staff account, including roles and contact details.           */
    $user = require_login();
    $rows = db()->query('SELECT id, name, email, role, department, job_title, phone, is_active
                         FROM users ORDER BY id')->fetchAll();
    layout_top('Users', $user);
    ?>
    <div class="page-head">
      <div><h1>Staff directory</h1><div class="muted"><?= count($rows) ?> internal accounts</div></div>
    </div>
    <div class="card">
      <table>
        <thead><tr><th>Name</th><th>Department</th><th>Title</th><th>Phone</th><th>Status</th><th>Role</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="/user/<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a>
              <div class="muted" style="font-size:12px"><?= e($r['email']) ?></div></td>
            <td><?= e($r['department']) ?></td>
            <td><?= e($r['job_title']) ?></td>
            <td class="muted"><?= e((string) $r['phone']) ?></td>
            <td><?= ((int) $r['is_active'] === 1) ? pill('active') : pill('inactive') ?></td>
            <td><?php if ($r['role'] === 'admin'): ?><span class="badge admin">Admin</span><?php else: ?><span class="badge">Staff</span><?php endif; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    layout_bottom();
}

function ctl_user_show(int $id): void
{
    // Same missing authorization: any logged-in user can read any profile.
    $user = require_login();
    $stmt = db()->prepare('SELECT id, name, email, role, department, job_title, phone, is_active, dealer_account_type FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    layout_top('User #' . $id, $user);
    if (!$u) {
        echo '<div class="card">User not found.</div>';
    } else {
        ?>
        <div class="page-head">
          <div><h1><?= e($u['name']) ?> <?php if ($u['role'] === 'admin'): ?><span class="badge admin">Admin</span><?php endif; ?></h1>
            <div class="muted"><?= e($u['job_title']) ?> · <?= e($u['department']) ?></div></div>
        </div>
        <div class="card" style="max-width:560px">
          <table>
            <tr><th>Email</th><td><?= e($u['email']) ?></td></tr>
            <tr><th>Phone</th><td><?= e((string) $u['phone']) ?></td></tr>
            <tr><th>Department</th><td><?= e($u['department']) ?></td></tr>
            <tr><th>Title</th><td><?= e($u['job_title']) ?></td></tr>
            <tr><th>Status</th><td><?= ((int) $u['is_active'] === 1) ? 'Active' : 'Inactive' ?></td></tr>
            <tr><th>Role</th><td><?= e($u['role']) ?></td></tr>
          </table>
          <p style="margin-top:14px"><a class="btn gray" href="/user/<?= (int) $id ?>/dealer-account/create">+ Dealer account for this user</a></p>
        </div>
        <?php
    }
    layout_bottom();
}
