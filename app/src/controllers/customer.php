<?php
declare(strict_types=1);

const POLICY_STATUSES = ['active', 'pending', 'lapsed', 'cancelled'];

function ctl_customer_index(): void
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
        $where[] = '(cu.name LIKE ? OR cu.policy_number LIKE ? OR cu.email LIKE ?)';
        array_push($args, "%{$q}%", "%{$q}%", "%{$q}%");
    }
    if (in_array($status, POLICY_STATUSES, true)) {
        $where[] = 'cu.policy_status = ?';
        $args[] = $status;
    }
    $w = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $cst = $pdo->prepare("SELECT COUNT(*) c FROM customers cu $w");
    $cst->execute($args);
    $total = (int) $cst->fetch()['c'];
    $pages = max(1, (int) ceil($total / $per));
    $page = min($page, $pages);
    $offset = ($page - 1) * $per;

    $st = $pdo->prepare("SELECT cu.*, u.name AS agent FROM customers cu
                         LEFT JOIN users u ON u.id = cu.agent_id
                         $w ORDER BY cu.id DESC LIMIT $per OFFSET $offset");
    $st->execute($args);
    $rows = $st->fetchAll();

    layout_top('Customers', $user);
    ?>
    <div class="page-head">
      <div><h1>Policyholders</h1><div class="muted"><?= $total ?> customers</div></div>
      <a class="btn" href="/customer/create">+ New customer</a>
    </div>

    <div class="card" style="padding:14px 16px">
      <form method="get" action="/customer" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <div class="search" style="max-width:300px;margin:0"><?= icon('search') ?>
          <input name="q" value="<?= e($q) ?>" placeholder="Search name, policy #, email…"></div>
        <select name="status" style="width:auto">
          <option value="">All policies</option>
          <?php foreach (POLICY_STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <?php if ($q !== '' || $status !== ''): ?><a class="btn gray" href="/customer">Clear</a><?php endif; ?>
      </form>
    </div>

    <div class="card">
      <table>
        <thead><tr><th>Policy #</th><th>Name</th><th>Vehicle</th><th>Premium</th><th>Agent</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><a href="/customer/<?= (int) $r['id'] ?>"><strong><?= e($r['policy_number']) ?></strong></a></td>
            <td><?= e($r['name']) ?><div class="muted" style="font-size:12px"><?= e($r['email']) ?></div></td>
            <td><?= e($r['vehicle']) ?></td>
            <td><?= money($r['premium']) ?></td>
            <td><?= e($r['agent'] ?? '—') ?></td>
            <td><?= pill($r['policy_status']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="muted">No customers match your filters.</td></tr><?php endif; ?>
        </tbody>
      </table>
      <?= pager('/customer', $page, $pages, array_filter(['q' => $q, 'status' => $status])) ?>
    </div>
    <?php
    layout_bottom();
}

function ctl_customer_show(int $id): void
{
    $user = require_login();
    $pdo = db();
    $st = $pdo->prepare('SELECT cu.*, u.name AS agent FROM customers cu
                         LEFT JOIN users u ON u.id = cu.agent_id WHERE cu.id = ?');
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) { http_response_code(404); layout_top('Not found', $user); echo '<div class="card">Customer not found.</div>'; layout_bottom(); return; }

    $cs = $pdo->prepare('SELECT id, case_number, claim_amount, recovered_amount, status FROM subrogation_cases WHERE customer_id = ? ORDER BY id DESC');
    $cs->execute([$id]);
    $cases = $cs->fetchAll();

    layout_top($c['name'], $user);
    ?>
    <div class="page-head">
      <div><h1><?= e($c['name']) ?> <?= pill($c['policy_status']) ?></h1>
        <div class="muted"><?= e($c['policy_number']) ?> · <?= e($c['vehicle']) ?></div></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:18px;align-items:start">
      <div class="card">
        <h2>Policy</h2>
        <table>
          <tr><th>Email</th><td><?= e($c['email']) ?></td></tr>
          <tr><th>Phone</th><td><?= e((string) $c['phone']) ?></td></tr>
          <tr><th>Premium</th><td><?= money($c['premium']) ?>/yr</td></tr>
          <tr><th>Agent</th><td><?= e($c['agent'] ?? '—') ?></td></tr>
          <tr><th>Term</th><td><?= e((string) $c['start_date']) ?> → <?= e((string) $c['end_date']) ?></td></tr>
        </table>
      </div>
      <div class="card">
        <h2>Claims history</h2>
        <table>
          <thead><tr><th>Case #</th><th>Claim</th><th>Recovered</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach ($cases as $r): ?>
            <tr><td><a href="/subrogation/<?= (int) $r['id'] ?>"><?= e($r['case_number']) ?></a></td>
              <td><?= money($r['claim_amount']) ?></td><td><?= money($r['recovered_amount']) ?></td>
              <td><?= pill($r['status']) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$cases): ?><tr><td colspan="4" class="muted">No claims on record.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <p style="margin-top:8px"><a href="/customer">← Back to customers</a></p>
    <?php
    layout_bottom();
}

function ctl_customer_create(): void
{
    $user = require_login();
    $agents = db()->query('SELECT id, name FROM users ORDER BY name')->fetchAll();
    layout_top('New customer', $user);
    ?>
    <h1>New policyholder</h1>
    <div class="card" style="max-width:640px">
      <form method="post" action="/customer/store">
        <label>Full name</label><input name="name" required>
        <label>Email</label><input name="email" type="email" required>
        <label>Phone</label><input name="phone">
        <label>Vehicle</label><input name="vehicle" placeholder="Make Model Year">
        <label>Annual premium (¥)</label><input name="premium" type="number" step="0.01" value="0">
        <label>Policy status</label>
        <select name="policy_status"><?php foreach (POLICY_STATUSES as $s): ?><option><?= $s ?></option><?php endforeach; ?></select>
        <label>Assigned agent</label>
        <select name="agent_id"><?php foreach ($agents as $a): ?><option value="<?= (int) $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?></select>
        <div style="margin-top:16px"><button type="submit">Create customer</button></div>
      </form>
    </div>
    <?php
    layout_bottom();
}

function ctl_customer_store(): void
{
    require_login();
    $p = params();
    $pdo = db();
    $next = (int) $pdo->query('SELECT IFNULL(MAX(id),0)+1 n FROM customers')->fetch()['n'];
    $st = $pdo->prepare('INSERT INTO customers (policy_number,name,email,phone,vehicle,premium,policy_status,agent_id,start_date,end_date)
                         VALUES (?,?,?,?,?,?,?,?,?,?)');
    $st->execute([
        sprintf('POL-2026-%05d', 2000 + $next),
        (string) ($p['name'] ?? ''),
        (string) ($p['email'] ?? ''),
        (string) ($p['phone'] ?? ''),
        (string) ($p['vehicle'] ?? ''),
        (float) ($p['premium'] ?? 0),
        in_array($p['policy_status'] ?? '', POLICY_STATUSES, true) ? $p['policy_status'] : 'active',
        (int) ($p['agent_id'] ?? 0) ?: null,
        date('Y-m-d'),
        date('Y-m-d', strtotime('+1 year')),
    ]);
    flash('Customer created.');
    redirect('/customer/' . (int) $pdo->lastInsertId());
}
