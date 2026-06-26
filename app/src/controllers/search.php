<?php
declare(strict_types=1);

function ctl_search(): void
{
    $user = require_login();
    $q = trim((string) input('q', ''));
    $pdo = db();
    $like = '%' . $q . '%';

    $customers = $cases = $dealers = $users = $anns = [];
    $suggest = false;
    if ($q !== '') {
        /* ---- Finding #12: BLIND SQL INJECTION --------------------------------
         * This sink concatenates `q` straight into a query whose result is used
         * ONLY as a boolean flag ($suggest) — the rows are never displayed and
         * errors are swallowed. Exploitation is therefore blind:
         *   boolean : /search?q=zzz%' OR '1'='1'-- -   -> "related records" badge shows
         *             /search?q=zzz%' OR '1'='2'-- -   -> badge hidden
         *   time    : /search?q=zzz%' OR SLEEP(5)-- -  -> response delayed 5s
         * (The displayed result sets below stay parameterized, so the injection
         * leaks nothing directly — you infer data one bit at a time.)         */
        try {
            $blindSql = "SELECT 1 FROM customers WHERE name LIKE '%{$q}%' LIMIT 1";
            $suggest = (bool) $pdo->query($blindSql)->fetchColumn();
        } catch (Throwable $e) {
            $suggest = false;            // suppressed → no error oracle, fully blind
        }

        // The visible result sets are parameterized — intentionally safe.
        $st = $pdo->prepare('SELECT id, policy_number, name, vehicle FROM customers
                             WHERE name LIKE ? OR policy_number LIKE ? OR email LIKE ? LIMIT 8');
        $st->execute([$like, $like, $like]);
        $customers = $st->fetchAll();

        $st = $pdo->prepare('SELECT id, case_number, status FROM subrogation_cases
                             WHERE case_number LIKE ? OR description LIKE ? LIMIT 8');
        $st->execute([$like, $like]);
        $cases = $st->fetchAll();

        $st = $pdo->prepare('SELECT id, dealer_code, name, region FROM dealer_accounts
                             WHERE name LIKE ? OR dealer_code LIKE ? OR region LIKE ? LIMIT 8');
        $st->execute([$like, $like, $like]);
        $dealers = $st->fetchAll();

        $st = $pdo->prepare('SELECT id, name, email FROM users WHERE name LIKE ? OR email LIKE ? LIMIT 8');
        $st->execute([$like, $like]);
        $users = $st->fetchAll();

        $st = $pdo->prepare('SELECT id, title FROM announcements WHERE title LIKE ? OR body LIKE ? LIMIT 8');
        $st->execute([$like, $like]);
        $anns = $st->fetchAll();
    }
    $total = count($customers) + count($cases) + count($dealers) + count($users) + count($anns);

    layout_top('Search', $user);
    ?>
    <div class="page-head">
      <div><h1>Search results</h1>
        <?php /* Finding #11: REFLECTED XSS — the raw query is echoed back without
           encoding. e.g. /search?q=<img src=x onerror=alert(document.domain)> */ ?>
        <div class="muted"><?= $q === '' ? 'Type a query in the search bar above.' : $total . ' result' . ($total === 1 ? '' : 's') . ' for “' . $q . '”' ?></div>
        <?php if ($q !== '' && $suggest): // boolean oracle for the blind SQLi sink ?>
          <div style="margin-top:6px"><span class="badge ok">Related policyholder records found</span></div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($q !== '' && $total === 0): ?>
      <div class="card"><p class="muted">No matches found. Try a policy number, case number, name, or region.</p></div>
    <?php endif; ?>

    <?php if ($cases): ?>
    <div class="card"><h2><?= icon('briefcase') ?> Claims</h2><table><tbody>
      <?php foreach ($cases as $r): ?><tr>
        <td><a href="/subrogation/<?= (int) $r['id'] ?>"><strong><?= e($r['case_number']) ?></strong></a></td>
        <td><?= pill($r['status']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>

    <?php if ($customers): ?>
    <div class="card"><h2><?= icon('user') ?> Customers</h2><table><tbody>
      <?php foreach ($customers as $r): ?><tr>
        <td><a href="/customer/<?= (int) $r['id'] ?>"><strong><?= e($r['policy_number']) ?></strong></a></td>
        <td><?= e($r['name']) ?></td><td class="muted"><?= e((string) $r['vehicle']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>

    <?php if ($dealers): ?>
    <div class="card"><h2><?= icon('users') ?> Dealers</h2><table><tbody>
      <?php foreach ($dealers as $r): ?><tr>
        <td><a href="/dealer-account/<?= (int) $r['id'] ?>"><strong><?= e((string) $r['dealer_code']) ?></strong></a></td>
        <td><?= e($r['name']) ?></td><td class="muted"><?= e((string) $r['region']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>

    <?php if ($users): ?>
    <div class="card"><h2><?= icon('shield') ?> Staff</h2><table><tbody>
      <?php foreach ($users as $r): ?><tr>
        <td><a href="/user/<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a></td>
        <td class="muted"><?= e($r['email']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>

    <?php if ($anns): ?>
    <div class="card"><h2><?= icon('mega') ?> Announcements</h2><table><tbody>
      <?php foreach ($anns as $r): ?><tr>
        <td><a href="/announcement/<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?php endif; ?>
    <?php
    layout_bottom();
}
