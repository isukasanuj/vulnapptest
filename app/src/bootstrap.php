<?php
/**
 * CloudInsure — intentionally vulnerable practice app (VAPT lab).
 * Bootstrap: DB connection, helpers, seeding, session handling.
 *
 * DO NOT deploy this anywhere reachable from the internet.
 */

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

/* ---------------------------------------------------------------------------
 * Response headers
 *  - We intentionally DO NOT send X-Content-Type-Options (finding #10).
 *  - X-Powered-By is suppressed via php.ini expose_php=Off (finding #06 removed).
 *  - No HSTS is configured because the lab is plain HTTP on localhost
 *    (finding #04 is out of scope for this build).
 * ------------------------------------------------------------------------- */

/* --------------------------- Database -------------------------------------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'cloudinsure';
    $user = getenv('DB_USER') ?: 'cloudinsure';
    $pass = getenv('DB_PASS') ?: 'cloudinsure_pw';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    // Retry a few times: the web container may boot just before MySQL is ready.
    $lastErr = null;
    for ($i = 0; $i < 30; $i++) {
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Emulation ON so the deliberate SQL-injection sink in
                // announcement INSERT supports stacked queries.
                PDO::ATTR_EMULATE_PREPARES   => true,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            $lastErr = $e;
            sleep(1);
        }
    }
    throw new RuntimeException('DB unavailable: ' . ($lastErr ? $lastErr->getMessage() : ''));
}

/* --------------------------- Seeding --------------------------------------- */
function seed_if_empty(): void
{
    $pdo = db();
    $count = (int) $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    if ($count > 0) {
        return;
    }

    $pick = static fn(array $a, int $i) => $a[$i % count($a)];

    /* ---- Staff ---- */
    $staff = [
        ['Admin User',   'admin@cloudinsure.local',      'Admin@123!', 'admin', 'Operations',  'Operations Manager',        '+81-3-5500-0100'],
        ['Ishan Analyst','iiiishan@cloudinsure.local',   'Password@1', 'user',  'Claims',       'Claims Adjuster',           '+81-3-5500-0111'],
        ['Mei Tanaka',   'analyst@cloudinsure.local',    'Analyst@1',  'user',  'Subrogation',  'Senior Recovery Specialist','+81-3-5500-0122'],
        ['Raj Patel',    'raj.patel@cloudinsure.local',  'Welcome@1',  'user',  'Claims',       'Claims Adjuster',           '+81-3-5500-0133'],
        ['Sara Lopez',   'sara.lopez@cloudinsure.local', 'Welcome@1',  'user',  'Subrogation',  'Recovery Specialist',       '+81-3-5500-0144'],
    ];
    $su = $pdo->prepare('INSERT INTO users (name,email,password,role,department,job_title,phone,dealer_account_type)
                         VALUES (?,?,?,?,?,?,?,?)');
    foreach ($staff as $i => [$n,$e,$p,$r,$dept,$jt,$ph]) {
        $su->execute([$n,$e,password_hash($p, PASSWORD_BCRYPT),$r,$dept,$jt,$ph,($i % 3) + 1]);
    }
    $adjusterIds = [2, 3, 4, 5];

    /* ---- Customers ---- */
    $firsts = ['Kenji','Yuki','Haruto','Aoi','Sota','Riku','Hina','Mio','Ren','Sara','Daichi','Nao','Emi','Taro','Kana'];
    $lasts  = ['Sato','Suzuki','Takahashi','Tanaka','Watanabe','Ito','Yamamoto','Nakamura','Kobayashi','Kato','Yoshida','Yamada','Sasaki','Mori','Abe'];
    $cars   = ['Toyota Prius 2022','Honda Civic 2021','Nissan Leaf 2023','Mazda CX-5 2020','Subaru Impreza 2022','Toyota Aqua 2019','Honda Fit 2023','Lexus IS 2021','Suzuki Swift 2022','Nissan Note 2020'];
    $cstat  = ['active','active','active','pending','active','lapsed','active','active','cancelled','active'];
    $sc = $pdo->prepare('INSERT INTO customers (policy_number,name,email,phone,vehicle,premium,policy_status,agent_id,start_date,end_date)
                         VALUES (?,?,?,?,?,?,?,?,?,?)');
    for ($i = 0; $i < 15; $i++) {
        $name = $pick($firsts, $i) . ' ' . $pick($lasts, $i * 3 + 1);
        $start = date('Y-m-d', strtotime('2025-01-01 +' . ($i * 17) . ' days'));
        $sc->execute([
            sprintf('POL-2026-%05d', 1001 + $i),
            $name,
            strtolower(str_replace(' ', '.', $name)) . '@example.jp',
            sprintf('+81-90-%04d-%04d', 1000 + $i * 7, 2000 + $i * 3),
            $pick($cars, $i),
            number_format(48000 + ($i * 1373) % 92000, 2, '.', ''),
            $pick($cstat, $i),
            $pick($adjusterIds, $i),
            $start,
            date('Y-m-d', strtotime($start . ' +1 year')),
        ]);
    }

    /* ---- Dealers ---- */
    $dnames = ['Tokyo Auto Group','Osaka Motors','Nagoya Drive Co.','Yokohama Wheels','Sapporo Cars','Fukuoka AutoHub','Kobe Vehicles','Kyoto Mobility'];
    $regions = ['Kanto','Kansai','Chubu','Kanto','Hokkaido','Kyushu','Kansai','Kansai'];
    $tiers   = ['gold','silver','platinum','bronze','silver','gold','bronze','platinum'];
    $sd = $pdo->prepare('INSERT INTO dealer_accounts (user_id,dealer_code,name,email,phone,region,tier,is_representative,dealer_account_type,is_active)
                         VALUES (?,?,?,?,?,?,?,?,?,?)');
    foreach ($dnames as $i => $dn) {
        $sd->execute([
            ($i % 5) + 1,
            sprintf('DLR-%03d', $i + 1),
            $dn,
            'partners@' . strtolower(str_replace([' ', '.'], '', $dn)) . '.co.jp',
            sprintf('+81-6-%04d-%04d', 4000 + $i * 11, 7000 + $i * 5),
            $regions[$i],
            $tiers[$i],
            $i % 4 === 0 ? 1 : 0,
            ($i % 3) + 1,
            $i === 4 ? 0 : 1,
        ]);
    }

    /* ---- Subrogation cases ---- */
    $statuses = ['open','investigating','recovering','recovered','closed','denied','open','investigating','recovering','recovered'];
    $prio     = ['high','medium','low','medium','high','low'];
    $descs = [
        'Rear-end collision at intersection; third-party insurer liability under review.',
        'Hail damage claim; pursuing recovery from manufacturer warranty.',
        'Multi-vehicle incident on expressway; awaiting police report.',
        'Parking lot side-swipe; dealer dashcam footage requested.',
        'Water damage from flooding; subrogation against municipal authority.',
        'Windshield damage; at-fault driver identified, demand letter sent.',
    ];
    $scase = $pdo->prepare('INSERT INTO subrogation_cases
        (case_number,customer_id,dealer_id,adjuster_id,incident_date,claim_amount,recovered_amount,status,priority,description)
        VALUES (?,?,?,?,?,?,?,?,?,?)');
    $noteStmt = $pdo->prepare('INSERT INTO case_notes (case_id,author_id,body) VALUES (?,?,?)');
    for ($i = 0; $i < 22; $i++) {
        $status = $pick($statuses, $i);
        $claim  = 1500 + ($i * 2731) % 46000;
        $rec = 0.0;
        if (in_array($status, ['recovered','closed'], true)) {
            $rec = $claim * (0.6 + (($i % 4) * 0.1));
        } elseif ($status === 'recovering') {
            $rec = $claim * 0.35;
        }
        $incident = date('Y-m-d', strtotime('2025-09-01 +' . ($i * 9) . ' days'));
        $scase->execute([
            sprintf('SUB-2026-%04d', 1 + $i),
            ($i % 15) + 1,
            ($i % 8) + 1,
            $pick($adjusterIds, $i + 1),
            $incident,
            number_format($claim, 2, '.', ''),
            number_format($rec, 2, '.', ''),
            $status,
            $pick($prio, $i),
            $pick($descs, $i),
        ]);
        $caseId = (int) $pdo->lastInsertId();
        if ($i < 12) {
            $noteStmt->execute([$caseId, $pick($adjusterIds, $i), 'Case opened and assigned. Initial liability assessment scheduled.']);
            if ($i % 2 === 0) {
                $noteStmt->execute([$caseId, $pick($adjusterIds, $i + 2), 'Contacted third-party insurer; awaiting response within 10 business days.']);
            }
        }
    }

    /* ---- Announcements ---- */
    $a = $pdo->prepare('INSERT INTO announcements (title,category_id,body,link_url,type_id,publish_start_date,publish_end_date,created_by)
                        VALUES (?,?,?,?,1,?,?,1)');
    $a->execute(['Q3 Subrogation Recovery Targets', 2,
        '<p>Recovery targets for Q3 have been raised to 68%. Adjusters should prioritise cases over ¥20,000 in claim value.</p>',
        'https://intranet.cloudinsure.local/targets', '2026-01-01', '2026-12-31']);
    $a->execute(['New Dealer Onboarding Process', 1,
        '<p>The dealer onboarding workflow has moved to the Partners portal. See the updated SOP for KYC requirements.</p>',
        'https://intranet.cloudinsure.local/dealers', '2026-02-01', '2026-12-31']);
    $a->execute(['Scheduled Maintenance', 4,
        '<p>The payment-file service will be offline Saturday 02:00–04:00 JST for upgrades.</p>',
        'https://status.cloudinsure.local', '2026-06-01', '2026-12-31']);
}

/* --------------------------- Helpers --------------------------------------- */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

/** Read JSON body (the original app is an API-style backend). */
function json_body(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    $cache = is_array($data) ? $data : [];
    return $cache;
}

/** Merge form + JSON + query into one param bag. */
function params(): array
{
    return array_merge($_GET, $_POST, json_body());
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/* --------------------------- Auth / sessions ------------------------------- */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        return null;
    }

    /* ---- Finding #05 REMOVED: single active session enforcement -----------
     * On each login we store the new PHP session id in users.current_session_id.
     * If a newer login happened elsewhere, the older session no longer matches
     * and is rejected here — so concurrent sessions are not allowed.        */
    if (!empty($u['current_session_id']) && $u['current_session_id'] !== session_id()) {
        session_destroy();
        return null;
    }
    return $u;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('/login');
    }
    return $u;
}

/**
 * NOTE: this helper exists but is deliberately NOT called by the
 * user-management / export controllers — that omission is the vertical
 * privilege-escalation + broken-access-control practice target.
 */
function require_admin(): array
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    return $u;
}

seed_if_empty();
