<?php
declare(strict_types=1);

function ctl_dashboard(): void
{
    $user = require_login();
    $pdo = db();

    $open = (int) $pdo->query("SELECT COUNT(*) c FROM subrogation_cases WHERE status IN ('open','investigating','recovering')")->fetch()['c'];
    $claimSum = (float) $pdo->query('SELECT IFNULL(SUM(claim_amount),0) s FROM subrogation_cases')->fetch()['s'];
    $recSum   = (float) $pdo->query('SELECT IFNULL(SUM(recovered_amount),0) s FROM subrogation_cases')->fetch()['s'];
    $rate = $claimSum > 0 ? round($recSum / $claimSum * 100) : 0;
    $activePolicies = (int) $pdo->query("SELECT COUNT(*) c FROM customers WHERE policy_status='active'")->fetch()['c'];

    $byStatus = [];
    foreach ($pdo->query("SELECT status, COUNT(*) c FROM subrogation_cases GROUP BY status") as $r) {
        $byStatus[$r['status']] = (int) $r['c'];
    }
    $order = ['open', 'investigating', 'recovering', 'recovered', 'closed', 'denied'];
    $series = [];
    foreach ($order as $s) {
        $series[$s] = $byStatus[$s] ?? 0;
    }
    $maxBar = max(1, max($series));

    $recent = $pdo->query("SELECT s.id, s.case_number, s.claim_amount, s.status, c.name AS customer
                           FROM subrogation_cases s LEFT JOIN customers c ON c.id=s.customer_id
                           ORDER BY s.id DESC LIMIT 6")->fetchAll();
    $anns = $pdo->query('SELECT id, title, created_at FROM announcements ORDER BY id DESC LIMIT 4')->fetchAll();

    $cards = [
        ['Open claims',     (string) $open,            'briefcase', '+3 this week'],
        ['Recovered YTD',   money($recSum),            'money',     '+8.4%'],
        ['Recovery rate',   $rate . '%',               'trend',     'target 68%'],
        ['Active policies', (string) $activePolicies,  'user',      'stable'],
    ];

    layout_top('Dashboard', $user);
    ?>
    <div class="page-head">
      <div>
        <h1>Good to see you, <?= e(explode(' ', $user['name'])[0]) ?> 👋</h1>
        <div class="muted"><?= e($user['department']) ?> · <?= e($user['job_title']) ?></div>
      </div>
      <div style="display:flex;gap:10px">
        <a class="btn gray" href="/customer/create">+ Customer</a>
        <a class="btn" href="/subrogation/create">+ New case</a>
      </div>
    </div>

    <div class="grid">
      <?php foreach ($cards as [$label, $value, $ic, $trend]): ?>
      <div class="stat">
        <div class="top"><div class="ic"><?= icon($ic) ?></div><span class="trend"><?= e($trend) ?></span></div>
        <div class="n"><?= e($value) ?></div><div class="l"><?= e($label) ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:18px;align-items:start">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
          <h2 style="margin:0">Claims by status</h2><span class="muted" style="font-size:12.5px">All open & closed cases</span>
        </div>
        <?php foreach ($series as $s => $n): ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:9px">
          <div style="width:104px;font-size:12.5px;color:#475569"><?= ucfirst($s) ?></div>
          <div style="flex:1;background:#eef2f7;border-radius:99px;height:12px;overflow:hidden">
            <div style="height:100%;width:<?= round($n / $maxBar * 100) ?>%;background:#2563eb"></div>
          </div>
          <div style="width:28px;text-align:right;font-size:12.5px;font-weight:600"><?= $n ?></div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <h2>Announcements</h2>
        <?php foreach ($anns as $a): ?>
          <div style="padding:9px 0;border-bottom:1px solid var(--line)">
            <a href="/announcement/<?= (int) $a['id'] ?>"><?= e($a['title']) ?></a>
            <div class="muted" style="font-size:12px"><?= e(substr((string) $a['created_at'], 0, 10)) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
        <h2 style="margin:0">Recent claims</h2><a href="/subrogation" style="font-size:13px">View all →</a>
      </div>
      <table>
        <thead><tr><th>Case #</th><th>Customer</th><th>Claim</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><a href="/subrogation/<?= (int) $r['id'] ?>"><strong><?= e($r['case_number']) ?></strong></a></td>
            <td><?= e($r['customer'] ?? '—') ?></td>
            <td><?= money($r['claim_amount']) ?></td>
            <td><?= pill($r['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
    layout_bottom();
}
