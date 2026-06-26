<?php
declare(strict_types=1);

function ctl_announcement_index(): void
{
    $user = require_login();
    $rows = db()->query('SELECT a.*, c.name AS category FROM announcements a
                         LEFT JOIN categories c ON c.id = a.category_id
                         ORDER BY a.id DESC')->fetchAll();
    layout_top('Announcements', $user);
    ?>
    <h1>Announcements</h1>
    <p><a class="btn" href="/announcement/create">New announcement</a></p>
    <div class="card">
      <table>
        <tr><th>#</th><th>Title</th><th>Category</th><th>Published</th><th></th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['category']) ?></td>
          <td class="muted"><?= e($r['publish_start_date']) ?> → <?= e($r['publish_end_date']) ?></td>
          <td><a href="/announcement/<?= (int) $r['id'] ?>">View</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php
    layout_bottom();
}

function ctl_announcement_create(): void
{
    $user = require_login();
    $cats = db()->query('SELECT * FROM categories ORDER BY id')->fetchAll();
    layout_top('New announcement', $user);
    ?>
    <h1>New announcement</h1>
    <div class="card">
      <form method="post" action="/announcement/store">
        <label>Title</label>
        <input name="title" required>
        <label>Category</label>
        <select name="category_id">
          <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Body (HTML allowed)</label>
        <textarea name="body" placeholder="&lt;p&gt;...&lt;/p&gt;"></textarea>
        <label>Link URL</label>
        <input name="link_url" placeholder="https://...">
        <label>Publish start</label>
        <input name="publish_start_date" type="date" value="2026-01-01">
        <label>Publish end</label>
        <input name="publish_end_date" type="date" value="2026-12-31">
        <div style="margin-top:16px"><button type="submit">Publish</button></div>
      </form>
    </div>
    <?php
    layout_bottom();
}

function ctl_announcement_store(): void
{
    require_login();
    $p = params();

    $title = (string) ($p['title'] ?? '');
    $body  = (string) ($p['body'] ?? '');
    $link  = (string) ($p['link_url'] ?? '');
    $catId = (int) ($p['category_id'] ?? 1);
    $typeId = (int) ($p['type_id'] ?? 1);
    $start = (string) ($p['publish_start_date'] ?? '');
    $end   = (string) ($p['publish_end_date'] ?? '');
    $uid   = (int) ($_SESSION['user_id'] ?? 0);

    /* ---- Finding #07 REMOVED: link scheme whitelist ----------------------
     * Only http/https URLs are accepted; smb://, file://, javascript:, etc.
     * are dropped so they can never be rendered as a clickable link.       */
    $scheme = strtolower((string) parse_url($link, PHP_URL_SCHEME));
    if ($link !== '' && !in_array($scheme, ['http', 'https'], true)) {
        $link = '';
        flash('Link URL rejected: only http/https schemes are allowed. ');
    }

    // Dates restricted to a safe character set (not part of the practice target).
    $start = preg_replace('/[^0-9-]/', '', $start) ?: '2026-01-01';
    $end   = preg_replace('/[^0-9-]/', '', $end) ?: '2026-12-31';

    /* ---- SQL INJECTION (added requirement) -------------------------------
     * title and body are concatenated straight into the INSERT statement.
     * With emulated prepares + multi-statement support, this sink is open to
     * in-band and stacked-query injection. Login stays parameterized.
     * Example payload (title):
     *   x',1,'b','',1,'2026-01-01','2026-12-31',1);
     *   UPDATE users SET role='admin' WHERE email='iiiishan@cloudinsure.local';-- -
     */
    $sql = "INSERT INTO announcements
              (title, category_id, body, link_url, type_id, publish_start_date, publish_end_date, created_by)
            VALUES
              ('{$title}', {$catId}, '{$body}', '{$link}', {$typeId}, '{$start}', '{$end}', {$uid})";

    try {
        db()->exec($sql);
    } catch (Throwable $ex) {
        // Verbose error helps demonstrate the injection during practice.
        flash('DB error: ' . $ex->getMessage());
        redirect('/announcement/create');
    }

    flash('Announcement published.');
    redirect('/announcement');
}

function ctl_announcement_show(int $id): void
{
    $user = require_login();
    $stmt = db()->prepare('SELECT a.*, c.name AS category FROM announcements a
                           LEFT JOIN categories c ON c.id = a.category_id
                           WHERE a.id = ?');
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if (!$a) {
        http_response_code(404);
        layout_top('Not found', $user);
        echo '<div class="card">Announcement not found.</div>';
        layout_bottom();
        return;
    }

    layout_top($a['title'], $user);
    ?>
    <h1><?= e($a['title']) ?></h1>
    <p class="muted">Category: <?= e($a['category']) ?> ·
       <?= e($a['publish_start_date']) ?> → <?= e($a['publish_end_date']) ?></p>
    <div class="card">
      <!-- Finding #01: STORED XSS — body is rendered without escaping. -->
      <?= $a['body'] ?>
    </div>
    <?php if (!empty($a['link_url'])): ?>
      <p>Reference: <a href="<?= e($a['link_url']) ?>"><?= e($a['link_url']) ?></a></p>
    <?php endif; ?>
    <p><a href="/announcement">← Back</a></p>
    <?php
    layout_bottom();
}
