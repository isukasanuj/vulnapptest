<?php
declare(strict_types=1);

const CASE_STATUSES = ['open', 'investigating', 'recovering', 'recovered', 'closed', 'denied'];

function ctl_subrogation_index(): void
{
    $user = require_login();
    $pdo = db();

    $q      = trim((string) input('q', ''));
    $status = (string) input('status', '');
    $page   = max(1, (int) input('page', 1));
    $per    = 10;

    $where = [];
    $args  = [];
    if ($q !== '') {
        $where[] = '(s.case_number LIKE ? OR s.description LIKE ?)';
        $args[] = "%{$q}%";
        $args[] = "%{$q}%";
    }
    if (in_array($status, CASE_STATUSES, true)) {
        $where[] = 's.status = ?';
        $args[] = $status;
    }
    $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $total = (int) (function () use ($pdo, $w, $args) {
        $st = $pdo->prepare("SELECT COUNT(*) c FROM subrogation_cases s $w");
        $st->execute($args);
        return $st->fetch()['c'];
    })();
    $pages = max(1, (int) ceil($total / $per));
    $page = min($page, $pages);
    $offset = ($page - 1) * $per;

    $sql = "SELECT s.*, c.name AS customer, u.name AS adjuster
            FROM subrogation_cases s
            LEFT JOIN customers c ON c.id = s.customer_id
            LEFT JOIN users u ON u.id = s.adjuster_id
            $w ORDER BY s.id DESC LIMIT $per OFFSET $offset";
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $rows = $st->fetchAll();

    layout_top('Claims', $user);
    ?>
    <div class="page-head">
      <div><h1>Subrogation claims</h1><div class="muted"><?= $total ?> cases · recovery workflow</div></div>
      <a class="btn" href="/subrogation/create">+ New case</a>
    </div>

    <div class="card" style="padding:14px 16px">
      <form method="get" action="/subrogation" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <div class="search" style="max-width:300px;margin:0"><?= icon('search') ?>
          <input name="q" value="<?= e($q) ?>" placeholder="Search case # or description…"></div>
        <select name="status" style="width:auto">
          <option value="">All statuses</option>
          <?php foreach (CASE_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <?php if ($q !== '' || $status !== ''): ?><a class="btn gray" href="/subrogation">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="card">
      <table>
        <thead><tr><th>Case #</th><th>Customer</th><th>Adjuster</th><th>Claim</th><th>Recovered</th><th>Priority</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="/subrogation/<?= (int) $r['id'] ?>"><strong><?= e($r['case_number']) ?></strong></a></td>
            <td><?= e($r['customer'] ?? '—') ?></td>
            <td><?= e($r['adjuster'] ?? '—') ?></td>
            <td><?= money($r['claim_amount']) ?></td>
            <td><?= money($r['recovered_amount']) ?></td>
            <td><?= pill($r['priority']) ?></td>
            <td><?= pill($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="muted">No cases match your filters.</td></tr><?php endif; ?>
        </tbody>
      </table>
      <?= pager('/subrogation', $page, $pages, array_filter(['q' => $q, 'status' => $status])) ?>
    </div>
    <?php
    layout_bottom();
}

function ctl_subrogation_show(int $id): void
{
    $user = require_login();
    $pdo = db();
    $st = $pdo->prepare('SELECT s.*, c.name AS customer, c.policy_number, c.email AS customer_email,
                                d.name AS dealer, u.name AS adjuster
                         FROM subrogation_cases s
                         LEFT JOIN customers c ON c.id = s.customer_id
                         LEFT JOIN dealer_accounts d ON d.id = s.dealer_id
                         LEFT JOIN users u ON u.id = s.adjuster_id
                         WHERE s.id = ?');
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) {
        http_response_code(404);
        layout_top('Not found', $user);
        echo '<div class="card">Case not found.</div>';
        layout_bottom();
        return;
    }
    $ns = $pdo->prepare('SELECT n.*, u.name AS author FROM case_notes n
                         LEFT JOIN users u ON u.id = n.author_id WHERE n.case_id = ? ORDER BY n.id DESC');
    $ns->execute([$id]);
    $notes = $ns->fetchAll();

    $rate = $c['claim_amount'] > 0 ? round(($c['recovered_amount'] / $c['claim_amount']) * 100) : 0;

    layout_top($c['case_number'], $user);
    ?>
    <div class="page-head">
      <div><h1><?= e($c['case_number']) ?> <?= pill($c['status']) ?></h1>
        <div class="muted">Incident <?= e((string) $c['incident_date']) ?> · <?= pill($c['priority']) ?> priority</div></div>
      <a class="btn gray" href="/subrogation/<?= $id ?>/edit">Edit case</a>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:18px;align-items:start">
      <div>
        <div class="card">
          <h2>Case details</h2>
          <p style="margin:0 0 14px"><?= e((string) $c['description']) ?></p>
          <table>
            <tr><th>Customer</th><td><?= $c['customer_id'] ? '<a href="/customer/' . (int) $c['customer_id'] . '">' . e($c['customer']) . '</a>' : '—' ?> <span class="muted"><?= e((string) $c['policy_number']) ?></span></td></tr>
            <tr><th>Dealer</th><td><?= $c['dealer_id'] ? '<a href="/dealer-account/' . (int) $c['dealer_id'] . '">' . e($c['dealer']) . '</a>' : '—' ?></td></tr>
            <tr><th>Adjuster</th><td><?= e($c['adjuster'] ?? '—') ?></td></tr>
            <tr><th>Claim amount</th><td><?= money($c['claim_amount']) ?></td></tr>
            <tr><th>Recovered</th><td><?= money($c['recovered_amount']) ?> · <?= $rate ?>%</td></tr>
          </table>
        </div>

        <div class="card">
          <h2>Activity</h2>
          <form method="post" action="/subrogation/<?= $id ?>/note" style="margin-bottom:16px;display:flex;gap:10px">
            <input name="body" placeholder="Add a note to this case…" required style="flex:1">
            <button type="submit">Post</button>
          </form>
          <?php foreach ($notes as $n): ?>
            <div style="border-left:3px solid #e2e8f0;padding:2px 0 12px 14px;margin-left:4px">
              <div style="font-size:13px"><?= e((string) $n['body']) ?></div>
              <div class="muted" style="font-size:12px"><?= e($n['author'] ?? 'System') ?> · <?= e((string) $n['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!$notes): ?><p class="muted">No activity yet.</p><?php endif; ?>
        </div>
      </div>

      <div class="card">
        <h2>Recovery</h2>
        <div class="stat" style="border:0;box-shadow:none;padding:0">
          <div class="n"><?= $rate ?>%</div><div class="l">of claim recovered</div>
        </div>
        <div style="height:8px;background:#eef2f7;border-radius:99px;margin:12px 0;overflow:hidden">
          <div style="height:100%;width:<?= min(100, $rate) ?>%;background:#2563eb"></div>
        </div>
        <p class="muted" style="font-size:12.5px"><?= money($c['recovered_amount']) ?> recovered of <?= money($c['claim_amount']) ?> claimed.</p>
      </div>
    </div>
    <p style="margin-top:8px"><a href="/subrogation">← Back to claims</a></p>
    <?php
    layout_bottom();
}

function subrogation_form_data(): array
{
    $pdo = db();
    return [
        'customers' => $pdo->query('SELECT id, name, policy_number FROM customers ORDER BY name')->fetchAll(),
        'dealers'   => $pdo->query('SELECT id, name FROM dealer_accounts ORDER BY name')->fetchAll(),
        'adjusters' => $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll(),
    ];
}

function ctl_subrogation_create(): void
{
    $user = require_login();
    $d = subrogation_form_data();
    layout_top('New case', $user);
    ?>
    <h1>New subrogation case</h1>
    <div class="card" style="max-width:640px">
      <form method="post" action="/subrogation/store">
        <label>Customer</label>
        <select name="customer_id"><?php foreach ($d['customers'] as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['policy_number']) ?>)</option><?php endforeach; ?></select>
        <label>Dealer</label>
        <select name="dealer_id"><option value="">— none —</option><?php foreach ($d['dealers'] as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <label>Adjuster</label>
        <select name="adjuster_id"><?php foreach ($d['adjusters'] as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <label>Incident date</label>
        <input name="incident_date" type="date" value="2026-01-15">
        <label>Claim amount (¥)</label>
        <input name="claim_amount" type="number" step="0.01" value="0">
        <label>Priority</label>
        <select name="priority"><option>medium</option><option>high</option><option>low</option></select>
        <label>Description</label>
        <textarea name="description" placeholder="Incident summary…"></textarea>
        <div style="margin-top:16px"><button type="submit">Create case</button></div>
      </form>
    </div>
    <?php
    layout_bottom();
}

function ctl_subrogation_store(): void
{
    require_login();
    $p = params();
    $pdo = db();
    $next = (int) $pdo->query('SELECT IFNULL(MAX(id),0)+1 n FROM subrogation_cases')->fetch()['n'];
    $st = $pdo->prepare('INSERT INTO subrogation_cases
        (case_number,customer_id,dealer_id,adjuster_id,incident_date,claim_amount,status,priority,description)
        VALUES (?,?,?,?,?,?,?,?,?)');
    $st->execute([
        sprintf('SUB-2026-%04d', $next),
        (int) ($p['customer_id'] ?? 0) ?: null,
        (int) ($p['dealer_id'] ?? 0) ?: null,
        (int) ($p['adjuster_id'] ?? 0) ?: null,
        preg_replace('/[^0-9-]/', '', (string) ($p['incident_date'] ?? '')) ?: null,
        (float) ($p['claim_amount'] ?? 0),
        'open',
        in_array($p['priority'] ?? '', ['low', 'medium', 'high'], true) ? $p['priority'] : 'medium',
        (string) ($p['description'] ?? ''),
    ]);
    flash('Case created.');
    redirect('/subrogation/' . (int) $pdo->lastInsertId());
}

function ctl_subrogation_edit(int $id): void
{
    $user = require_login();
    $st = db()->prepare('SELECT * FROM subrogation_cases WHERE id = ?');
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) { http_response_code(404); echo 'Not found'; return; }
    layout_top('Edit ' . $c['case_number'], $user);
    ?>
    <h1>Edit <?= e($c['case_number']) ?></h1>
    <div class="card" style="max-width:640px">
      <form method="post" action="/subrogation/<?= $id ?>/update">
        <label>Status</label>
        <select name="status"><?php foreach (CASE_STATUSES as $s): ?>
          <option <?= $c['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
        <label>Priority</label>
        <select name="priority"><?php foreach (['low','medium','high'] as $s): ?>
          <option <?= $c['priority'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
        <label>Claim amount (¥)</label>
        <input name="claim_amount" type="number" step="0.01" value="<?= e((string) $c['claim_amount']) ?>">
        <label>Recovered amount (¥)</label>
        <input name="recovered_amount" type="number" step="0.01" value="<?= e((string) $c['recovered_amount']) ?>">
        <label>Description</label>
        <textarea name="description"><?= e((string) $c['description']) ?></textarea>
        <div style="margin-top:16px"><button type="submit">Save changes</button>
          <a class="btn gray" href="/subrogation/<?= $id ?>">Cancel</a></div>
      </form>
    </div>
    <?php
    layout_bottom();
}

function ctl_subrogation_update(int $id): void
{
    require_login();
    $p = params();
    $st = db()->prepare('UPDATE subrogation_cases
        SET status=?, priority=?, claim_amount=?, recovered_amount=?, description=? WHERE id=?');
    $st->execute([
        in_array($p['status'] ?? '', CASE_STATUSES, true) ? $p['status'] : 'open',
        in_array($p['priority'] ?? '', ['low', 'medium', 'high'], true) ? $p['priority'] : 'medium',
        (float) ($p['claim_amount'] ?? 0),
        (float) ($p['recovered_amount'] ?? 0),
        (string) ($p['description'] ?? ''),
        $id,
    ]);
    flash('Case updated.');
    redirect('/subrogation/' . $id);
}

function ctl_subrogation_note(int $id): void
{
    $user = require_login();
    $body = trim((string) input('body', ''));
    if ($body !== '') {
        $st = db()->prepare('INSERT INTO case_notes (case_id, author_id, body) VALUES (?,?,?)');
        $st->execute([$id, (int) $user['id'], $body]);
    }
    redirect('/subrogation/' . $id);
}
