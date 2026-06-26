<?php
declare(strict_types=1);

function contract_storage_dir(): string
{
    $dir = '/var/www/html/storage/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return $dir;
}

function ctl_contract_index(): void
{
    $user = require_login();
    $rows = db()->query('SELECT * FROM contract_uploads ORDER BY id DESC')->fetchAll();
    layout_top('Contract templates', $user);
    ?>
    <h1>Contract front-page PDFs</h1>
    <div class="card">
      <h2>Upload (契約書オモテPDF)</h2>
      <p class="muted">Intended for PDF front pages only.</p>
      <form method="post" action="/contract_template_front_page_pdf/store" enctype="multipart/form-data">
        <label>File</label>
        <input type="file" name="file" required>
        <div style="margin-top:14px"><button type="submit">Upload</button></div>
      </form>
    </div>
    <div class="card">
      <h2>Uploaded files</h2>
      <table>
        <tr><th>#</th><th>Original name</th><th>Stored MIME</th><th>Size</th><th></th></tr>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= e($r['original_name']) ?></td>
          <td><?= e($r['mime']) ?></td>
          <td><?= (int) $r['size'] ?> B</td>
          <td><a href="/contract/download?id=<?= (int) $r['id'] ?>">Download</a></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <?php
    layout_bottom();
}

function ctl_contract_store(): void
{
    require_login();

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        flash('Upload failed.');
        redirect('/contract');
    }

    $f = $_FILES['file'];

    /* ---- Finding #09: IMPROPER FILE-TYPE / MIME VALIDATION ---------------
     * Validation only trusts the client-supplied filename + Content-Type and
     * never checks the real %PDF- magic bytes. An attacker can upload e.g.
     * fileuploadvalidationissue.html with Content-Type: text/html and it is
     * accepted as a "contract PDF".
     *
     * A correct check would be (commented out):
     *   $finfo = finfo_open(FILEINFO_MIME_TYPE);
     *   $real  = finfo_file($finfo, $f['tmp_name']);
     *   if ($real !== 'application/pdf') { reject(); }
     */
    $clientMime = (string) ($f['type'] ?? 'application/octet-stream');
    $original   = (string) ($f['name'] ?? 'upload');

    $stored = bin2hex(random_bytes(8)) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $original);
    $dest   = contract_storage_dir() . '/' . $stored;
    move_uploaded_file($f['tmp_name'], $dest);

    $stmt = db()->prepare(
        'INSERT INTO contract_uploads (original_name, stored_name, mime, size, uploaded_by)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$original, $stored, $clientMime, (int) ($f['size'] ?? 0), (int) ($_SESSION['user_id'] ?? 0)]);

    flash('File uploaded.');
    redirect('/contract');
}

function ctl_contract_download(): void
{
    require_login();
    $id = (int) input('id', 0);
    $stmt = db()->prepare('SELECT * FROM contract_uploads WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo 'Not found';
        return;
    }
    $path = contract_storage_dir() . '/' . $row['stored_name'];
    if (!is_file($path)) {
        http_response_code(404);
        echo 'Missing file';
        return;
    }
    // Forced download (does not neutralise the weak validation, but matches
    // the report's observed behaviour).
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($row['original_name']) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
}
