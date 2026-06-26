<?php
declare(strict_types=1);

function ctl_dealer_index(): void
{
    $user = require_login();
    $rows = db()->query('SELECT * FROM dealer_accounts ORDER BY id DESC')->fetchAll();
    layout_top('Dealer accounts', $user);
    ?>
    <div class="page-head">
      <div><h1>Dealer accounts</h1><div class="muted"><?= count($rows) ?> partners</div></div>
      <a class="btn" href="/dealer-account/create">+ New dealer account</a>
    </div>
    <div class="card">
      <table>
        <thead><tr><th>Code</th><th>Dealer</th><th>Region</th><th>Tier</th><th>Rep</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="/dealer-account/<?= (int) $r['id'] ?>"><strong><?= e((string) ($r['dealer_code'] ?? '—')) ?></strong></a></td>
            <td><?= e($r['name']) ?><div class="muted" style="font-size:12px"><?= e($r['email']) ?></div></td>
            <td><?= e((string) $r['region']) ?></td>
            <td><?= pill((string) $r['tier']) ?></td>
            <td><?= ((int) $r['is_representative'] === 1) ? 'Yes' : '—' ?></td>
            <td><?= ((int) $r['is_active'] === 1) ? pill('active') : pill('inactive') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="muted">No dealer accounts yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
    layout_bottom();
}

function ctl_dealer_show(int $id): void
{
    $user = require_login();
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM dealer_accounts WHERE id = ?');
    $st->execute([$id]);
    $d = $st->fetch();
    if (!$d) { http_response_code(404); layout_top('Not found', $user); echo '<div class="card">Dealer not found.</div>'; layout_bottom(); return; }

    $cs = $pdo->prepare('SELECT id, case_number, claim_amount, status FROM subrogation_cases WHERE dealer_id = ? ORDER BY id DESC');
    $cs->execute([$id]);
    $cases = $cs->fetchAll();

    layout_top($d['name'], $user);
    ?>
    <div class="page-head">
      <div><h1><?= e($d['name']) ?> <?= ((int) $d['is_active'] === 1) ? pill('active') : pill('inactive') ?></h1>
        <div class="muted"><?= e((string) $d['dealer_code']) ?> · <?= e((string) $d['region']) ?></div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:18px;align-items:start">
      <div class="card">
        <h2>Partner details</h2>
        <table>
          <tr><th>Tier</th><td><?= pill((string) $d['tier']) ?></td></tr>
          <tr><th>Email</th><td><?= e($d['email']) ?></td></tr>
          <tr><th>Phone</th><td><?= e((string) $d['phone']) ?></td></tr>
          <tr><th>Representative</th><td><?= ((int) $d['is_representative'] === 1) ? 'Yes' : 'No' ?></td></tr>
          <tr><th>Account type</th><td><?= (int) $d['dealer_account_type'] ?></td></tr>
          <tr><th>Linked user id</th><td><?= (int) $d['user_id'] ?></td></tr>
        </table>
      </div>
      <div class="card">
        <h2>Associated claims</h2>
        <table>
          <thead><tr><th>Case #</th><th>Claim</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($cases as $r): ?>
            <tr><td><a href="/subrogation/<?= (int) $r['id'] ?>"><?= e($r['case_number']) ?></a></td>
              <td><?= money($r['claim_amount']) ?></td><td><?= pill($r['status']) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$cases): ?><tr><td colspan="3" class="muted">No claims linked to this dealer.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <p style="margin-top:8px"><a href="/dealer-account">← Back to dealers</a></p>
    <?php
    layout_bottom();
}

function ctl_dealer_create(?int $forUserId = null): void
{
    $user = require_login();
    $target = $forUserId ?? (int) $user['id'];
    layout_top('New dealer account', $user);
    ?>
    <h1>New dealer account</h1>
    <div class="card" style="max-width:640px">
      <form method="post" action="/user/<?= (int) $target ?>/dealer-account/store">
        <label>Dealer name</label><input name="name" required>
        <label>Dealer code</label><input name="dealer_code" placeholder="DLR-009">
        <label>Email</label><input name="email" type="email" required>
        <label>Email confirmation</label><input name="email_confirmation" type="email" required>
        <label>Phone</label><input name="phone">
        <label>Region</label><input name="region" placeholder="Kanto">
        <label>Tier</label>
        <select name="tier"><?php foreach (['bronze','silver','gold','platinum'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select>
        <label>Account type</label><input name="dealer_account_type" type="number" value="1">
        <label><input type="checkbox" name="is_representative_dealer_account" value="1" style="width:auto"> Representative dealer account</label>
        <label><input type="checkbox" name="is_active" value="1" checked style="width:auto"> Active</label>
        <div style="margin-top:16px"><button type="submit">Create</button></div>
      </form>
    </div>
    <?php
    layout_bottom();
}

function ctl_dealer_store(int $userId): void
{
    /* ---- Finding #04: BROKEN ACCESS CONTROL -----------------------------
     * The {userId} path segment is taken at face value. There is NO check
     * that the caller owns or may act on that user, and userId=0 (a user
     * that does not exist) is accepted and stored as an orphan row.        */
    require_login();
    $p = params();

    $email = (string) ($p['email'] ?? '');
    $confirm = (string) ($p['email_confirmation'] ?? $email);
    if ($email === '' || $email !== $confirm) {
        flash('Email and confirmation must match.');
        redirect('/dealer-account/create');
    }

    $chk = db()->prepare('SELECT 1 FROM dealer_accounts WHERE email = ? LIMIT 1');
    $chk->execute([$email]);
    if ($chk->fetch()) {
        flash('Email already exists.');
        redirect('/dealer-account/create');
    }

    $tier = in_array($p['tier'] ?? '', ['bronze', 'silver', 'gold', 'platinum'], true) ? $p['tier'] : 'bronze';
    $st = db()->prepare('INSERT INTO dealer_accounts
        (user_id,dealer_code,name,email,phone,region,tier,is_representative,dealer_account_type,is_active)
        VALUES (?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        $userId,                                   // not validated — BAC
        (string) ($p['dealer_code'] ?? ''),
        (string) ($p['name'] ?? ''),
        $email,
        (string) ($p['phone'] ?? ''),
        (string) ($p['region'] ?? ''),
        $tier,
        !empty($p['is_representative_dealer_account']) ? 1 : 0,
        (int) ($p['dealer_account_type'] ?? 1),
        !empty($p['is_active']) ? 1 : 0,
    ]);

    flash('Dealer account created for user #' . $userId . '.');
    redirect('/dealer-account');
}
