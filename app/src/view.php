<?php
declare(strict_types=1);

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $a = $parts[0][0] ?? 'U';
    $b = isset($parts[1][0]) ? $parts[1][0] : ($parts[0][1] ?? '');
    return strtoupper($a . $b);
}

function page_head(string $title): void
{
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · CloudInsure</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{
  --navy:#0b1b34;--navy-2:#11264a;--ink:#0f172a;--body:#475569;--muted:#7c899c;
  --line:#e7ebf2;--bg:#f4f6fb;--card:#ffffff;
  --brand:#2563eb;--brand-d:#1d4ed8;--accent:#0ea5e9;
  --ok:#059669;--ok-bg:#ecfdf5;--warn:#b45309;--warn-bg:#fffbeb;--bad:#dc2626;--bad-bg:#fef2f2;
  --shadow:0 1px 2px rgba(16,24,40,.04),0 4px 16px rgba(16,24,40,.06);
}
*{box-sizing:border-box}
html,body{margin:0;padding:0}
body{font-family:'Inter',system-ui,Segoe UI,Arial,sans-serif;background:var(--bg);color:var(--ink);font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased}
a{color:var(--brand);text-decoration:none}
a:hover{text-decoration:underline}
h1{font-size:22px;font-weight:700;margin:0 0 4px;letter-spacing:-.01em}
h2{font-size:16px;font-weight:600;margin:0 0 14px}
.muted{color:var(--muted)}
code{background:#0b1b34;color:#e2e8f0;padding:1px 6px;border-radius:5px;font-size:12.5px;font-family:ui-monospace,Menlo,Consolas,monospace}

/* ---------- App shell ---------- */
.shell{display:flex;min-height:100vh}
.sidebar{width:248px;background:linear-gradient(180deg,var(--navy),var(--navy-2));color:#c9d4e5;display:flex;flex-direction:column;position:fixed;inset:0 auto 0 0;z-index:20}
.sidebar .brand{display:flex;align-items:center;gap:10px;padding:20px 20px 14px;color:#fff;font-weight:700;font-size:16px}
.sidebar .brand svg{flex:0 0 auto}
.sidebar .brand small{display:block;font-weight:500;font-size:11px;color:#7f93b3;letter-spacing:.04em;text-transform:uppercase}
.nav{padding:8px 12px;overflow-y:auto;flex:1}
.nav .grp{font-size:10.5px;letter-spacing:.09em;text-transform:uppercase;color:#62769a;margin:16px 12px 6px;font-weight:600}
.nav a{display:flex;align-items:center;gap:11px;padding:9px 12px;border-radius:9px;color:#c2cee2;font-weight:500;margin-bottom:2px}
.nav a:hover{background:rgba(255,255,255,.06);color:#fff;text-decoration:none}
.nav a.active{background:var(--brand);color:#fff;box-shadow:0 6px 16px rgba(37,99,235,.35)}
.nav a svg{opacity:.9}
.sidebar .foot{border-top:1px solid rgba(255,255,255,.08);padding:12px 16px;display:flex;align-items:center;gap:10px}
.avatar{width:34px;height:34px;border-radius:50%;background:#1e3a8a;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:13px;flex:0 0 auto}
.sidebar .foot .nm{font-size:13px;color:#fff;font-weight:600;line-height:1.2}
.sidebar .foot .rl{font-size:11px;color:#7f93b3}
.sidebar .foot a{margin-left:auto;color:#9fb0cc}

.main{margin-left:248px;flex:1;display:flex;flex-direction:column;min-width:0}
.topbar{height:62px;background:rgba(255,255,255,.9);backdrop-filter:blur(6px);border-bottom:1px solid var(--line);display:flex;align-items:center;gap:16px;padding:0 28px;position:sticky;top:0;z-index:10}
.topbar .pt{font-weight:600;font-size:15px}
.search{margin-left:8px;flex:1;max-width:420px;position:relative}
.search input{width:100%;padding:8px 12px 8px 34px;border:1px solid var(--line);border-radius:9px;background:#f8fafc;font-size:13px}
.search svg{position:absolute;left:10px;top:9px;color:#94a3b8}
.topbar .spacer{flex:1}
.iconbtn{width:36px;height:36px;border-radius:9px;border:1px solid var(--line);background:#fff;display:flex;align-items:center;justify-content:center;color:#64748b;cursor:pointer;position:relative}
.iconbtn .dot{position:absolute;top:8px;right:9px;width:7px;height:7px;background:var(--bad);border-radius:50%;border:2px solid #fff}
.content{padding:28px;max-width:1180px;width:100%}
.page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap}

/* ---------- Cards / stats ---------- */
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:20px;margin-bottom:18px;box-shadow:var(--shadow)}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:6px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px;box-shadow:var(--shadow)}
.stat .top{display:flex;align-items:center;justify-content:space-between}
.stat .ic{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:#eff4ff;color:var(--brand)}
.stat .n{font-size:27px;font-weight:700;margin-top:12px;letter-spacing:-.02em}
.stat .l{font-size:13px;color:var(--muted);margin-top:2px}
.stat .trend{font-size:12px;font-weight:600;color:var(--ok);background:var(--ok-bg);padding:3px 8px;border-radius:999px}

/* ---------- Tables ---------- */
table{width:100%;border-collapse:collapse;font-size:13.5px}
th,td{text-align:left;padding:11px 12px;border-bottom:1px solid var(--line);vertical-align:middle}
th{color:var(--muted);font-weight:600;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;background:#fbfcfe}
tr:last-child td{border-bottom:0}
tbody tr:hover{background:#fafbff}

/* ---------- Badges / buttons ---------- */
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;font-size:12px;font-weight:600;background:#eef2f7;color:#475569}
.badge.admin{background:var(--bad-bg);color:var(--bad)}
.badge.ok{background:var(--ok-bg);color:var(--ok)}
.badge.off{background:#f1f5f9;color:#64748b}
.badge::before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor;opacity:.7}
button,.btn{display:inline-flex;align-items:center;gap:7px;background:var(--brand);color:#fff;border:0;padding:9px 15px;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;font-family:inherit;box-shadow:0 1px 2px rgba(37,99,235,.25)}
button:hover,.btn:hover{background:var(--brand-d);text-decoration:none}
.btn.gray{background:#fff;color:var(--ink);border:1px solid var(--line);box-shadow:none}
.btn.gray:hover{background:#f8fafc}

/* ---------- Forms ---------- */
label{display:block;font-size:13px;color:var(--ink);font-weight:500;margin:14px 0 6px}
input,textarea,select{width:100%;padding:10px 12px;border:1px solid #d5dce8;border-radius:9px;font-size:14px;font-family:inherit;background:#fff;color:var(--ink)}
input:focus,textarea:focus,select:focus{outline:0;border-color:var(--brand);box-shadow:0 0 0 3px rgba(37,99,235,.12)}
textarea{min-height:130px;resize:vertical}

/* ---------- Flash ---------- */
.flash{display:flex;align-items:center;gap:10px;background:var(--ok-bg);border:1px solid #a7f3d0;color:#065f46;padding:11px 15px;border-radius:11px;margin-bottom:18px;font-size:13.5px;font-weight:500}

/* ---------- Auth ---------- */
.auth{display:flex;min-height:100vh}
.auth .promo{flex:1.05;background:linear-gradient(150deg,#0b1b34,#15366b 70%,#1e40af);color:#fff;padding:56px 60px;display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden}
.auth .promo .blob{position:absolute;border-radius:50%;filter:blur(8px);opacity:.25}
.auth .promo h2{font-size:30px;line-height:1.25;font-weight:700;max-width:430px;margin:0 0 16px}
.auth .promo p{color:#aebfdc;max-width:430px;font-size:15px}
.auth .promo .brand{display:flex;align-items:center;gap:11px;font-weight:700;font-size:18px}
.auth .promo .stats{display:flex;gap:34px;margin-top:8px}
.auth .promo .stats .n{font-size:24px;font-weight:700}
.auth .promo .stats .l{font-size:12.5px;color:#9fb0cc}
.auth .formwrap{flex:1;display:flex;align-items:center;justify-content:center;padding:40px;background:#fff}
.auth .formcard{width:100%;max-width:380px}
.auth .formcard h1{font-size:24px;margin-bottom:6px}
.auth .formcard .sub{color:var(--muted);margin-bottom:22px}
.auth .formcard .row{display:flex;justify-content:space-between;align-items:center;margin-top:8px}
.auth .formcard button{width:100%;justify-content:center;padding:11px;margin-top:20px;font-size:14.5px}
.auth .err{background:var(--bad-bg);border:1px solid #fecaca;color:#991b1b;padding:10px 13px;border-radius:9px;font-size:13.5px;margin-bottom:16px}
@media(max-width:860px){.auth .promo{display:none}.sidebar{display:none}.main{margin-left:0}}
</style>
</head>
<body>
<?php
}

function icon(string $name): string
{
    $p = [
        'grid'   => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'mega'   => '<path d="M3 11v2a1 1 0 0 0 1 1h2l4 4V6L6 10H4a1 1 0 0 0-1 1Z"/><path d="M14 8a4 4 0 0 1 0 8"/>',
        'users'  => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0"/><path d="M16 6.2a3 3 0 0 1 0 5.6"/><path d="M17 19a5 5 0 0 0-2.5-4.3"/>',
        'user'   => '<circle cx="12" cy="8" r="3.6"/><path d="M5 20a7 7 0 0 1 14 0"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/>',
        'money'  => '<rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 9v6M18 9v6"/>',
        'trend'  => '<path d="M3 17l6-6 4 4 8-8"/><path d="M21 7v5h-5"/>',
        'file'   => '<path d="M6 2.5h7l5 5V21a.5.5 0 0 1-.5.5H6A.5.5 0 0 1 5.5 21V3A.5.5 0 0 1 6 2.5Z"/><path d="M13 2.5V8h5"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.4 3 8.3 7 9.5 4-1.2 7-5.1 7-9.5V6l-7-3Z"/>',
        'down'   => '<path d="M12 4v11"/><path d="m7 11 5 5 5-5"/><path d="M5 20h14"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.2-3.2"/>',
        'bell'   => '<path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6Z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
        'cloud'  => '<path d="M7 18a4 4 0 0 1-.5-7.97A5.5 5.5 0 0 1 17 10.5a3.5 3.5 0 0 1 .5 7Z"/>',
        'cog'    => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.3 1a7 7 0 0 0-1.7-1l-.4-2.6H9.5L9 5.6a7 7 0 0 0-1.7 1l-2.3-1-2 3.4L5 11a7 7 0 0 0 0 2l-2 1.5 2 3.4 2.3-1a7 7 0 0 0 1.7 1l.5 2.6h4l.4-2.6a7 7 0 0 0 1.7-1l2.3 1 2-3.4-2-1.5a7 7 0 0 0 .1-1Z"/>',
        'logout' => '<path d="M14 5V4a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1"/><path d="M10 12h10"/><path d="m17 8 4 4-4 4"/>',
    ];
    $body = $p[$name] ?? '';
    return '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

function brand_mark(int $size = 26): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none">'
        . '<rect width="24" height="24" rx="7" fill="#2563eb"/>'
        . '<path d="M8 14.5a3 3 0 0 1-.4-5.97A4 4 0 0 1 15.5 9a2.6 2.6 0 0 1 .4 5.5Z" fill="#fff"/>'
        . '<path d="M11 11.5 12 9l1 2.5" stroke="#2563eb" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

function money($n): string
{
    return '¥' . number_format((float) $n);
}

/** Coloured status / tier / priority pill. */
function pill(string $s): string
{
    $green = ['active', 'recovered', 'closed', 'gold', 'platinum'];
    $gray  = ['lapsed', 'cancelled', 'denied', 'bronze', 'silver', 'low', 'inactive'];
    $red   = ['high'];
    $cls = in_array($s, $green, true) ? 'ok' : (in_array($s, $gray, true) ? 'off' : (in_array($s, $red, true) ? 'admin' : ''));
    return '<span class="badge ' . $cls . '">' . e(ucfirst($s)) . '</span>';
}

/** Simple query-string pager. Returns rendered HTML. */
function pager(string $base, int $page, int $pages, array $extra = []): string
{
    if ($pages <= 1) {
        return '';
    }
    $q = static function (int $p) use ($base, $extra): string {
        $params = array_merge($extra, ['page' => $p]);
        return $base . '?' . http_build_query($params);
    };
    $h = '<div style="display:flex;gap:6px;justify-content:flex-end;margin-top:14px">';
    $prev = max(1, $page - 1);
    $next = min($pages, $page + 1);
    $h .= '<a class="btn gray" href="' . e($q($prev)) . '">‹ Prev</a>';
    $h .= '<span class="muted" style="align-self:center;padding:0 8px">Page ' . $page . ' of ' . $pages . '</span>';
    $h .= '<a class="btn gray" href="' . e($q($next)) . '">Next ›</a>';
    $h .= '</div>';
    return $h;
}

function nav_item(string $href, string $ic, string $label, string $cur): void
{
    $base = rtrim($href, '/');
    $isActive = ($href === '/dashboard')
        ? ($cur === '/dashboard')
        : (strpos($cur, $base) === 0);
    $cls = $isActive ? 'active' : '';
    echo '<a class="' . $cls . '" href="' . e($href) . '">' . icon($ic) . '<span>' . e($label) . '</span></a>';
}

function layout_top(string $title, ?array $user = null): void
{
    if (!$user) {                       // unauthenticated → minimal wrapper
        page_head($title);
        return;
    }
    $cur = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
    $f = flash();
    page_head($title);
    ?>
<div class="shell">
  <aside class="sidebar">
    <div class="brand"><?= brand_mark(28) ?><div>CloudInsure<small>Insurance Ops</small></div></div>
    <nav class="nav">
      <div class="grp">Workspace</div>
      <?php
        nav_item('/dashboard', 'grid', 'Dashboard', $cur);
        nav_item('/subrogation', 'briefcase', 'Claims', $cur);
        nav_item('/customer', 'user', 'Customers', $cur);
        nav_item('/dealer-account', 'users', 'Dealer Accounts', $cur);
        nav_item('/contract', 'file', 'Contracts', $cur);
        nav_item('/announcement', 'mega', 'Announcements', $cur);
      ?>
      <?php if (($user['role'] ?? '') === 'admin'): // admin-only menu (UI gating only — routes stay open) ?>
      <div class="grp">Administration</div>
      <?php
        nav_item('/user', 'shield', 'Users', $cur);
        nav_item('/export/announcements.csv', 'down', 'Reports', $cur);
      endif; ?>
      <div class="grp">Account</div>
      <?php nav_item('/profile', 'cog', 'Settings', $cur); ?>
    </nav>
    <div class="foot">
      <div class="avatar"><?= e(initials($user['name'])) ?></div>
      <div><div class="nm"><?= e($user['name']) ?></div><div class="rl"><?= e(ucfirst($user['role'])) ?></div></div>
      <a href="/logout" title="Sign out"><?= icon('logout') ?></a>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <div class="pt"><?= e($title) ?></div>
      <form class="search" method="get" action="/search"><?= icon('search') ?><input name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>" placeholder="Search policies, dealers, contracts…" autocomplete="off"></form>
      <div class="spacer"></div>
      <div class="iconbtn"><?= icon('bell') ?><span class="dot"></span></div>
      <div class="avatar"><?= e(initials($user['name'])) ?></div>
    </header>
    <div class="content">
    <?php if ($f): ?><div class="flash"><?= icon('shield') ?><?= e($f) ?></div><?php endif; ?>
<?php
}

function layout_bottom(): void
{
    if (empty($_SESSION['user_id'])) {     // unauthenticated wrapper
        echo "\n</body></html>";
        return;
    }
    ?>
    </div>
  </div>
</div>
</body>
</html><?php
}

/* ---------------- Auth (login / forgot) shell ---------------- */
function auth_top(string $title): void
{
    page_head($title);
    ?>
<div class="auth">
  <div class="promo">
    <div class="brand"><?= brand_mark(30) ?> CloudInsure</div>
    <div>
      <div class="blob" style="width:280px;height:280px;background:#2563eb;top:-60px;right:-40px"></div>
      <div class="blob" style="width:200px;height:200px;background:#0ea5e9;bottom:40px;left:-40px"></div>
      <h2>The operations platform for modern insurance teams.</h2>
      <p>Manage dealer accounts, claims subrogation, contracts and announcements — all in one secure workspace trusted by underwriting teams worldwide.</p>
    </div>
    <div class="stats">
      <div><div class="n">12,400+</div><div class="l">Policies managed</div></div>
      <div><div class="n">340</div><div class="l">Dealer partners</div></div>
      <div><div class="n">99.95%</div><div class="l">Uptime</div></div>
    </div>
  </div>
  <div class="formwrap">
    <div class="formcard">
<?php
}

function auth_bottom(): void
{
    ?>
    </div>
  </div>
</div>
</body>
</html><?php
}
