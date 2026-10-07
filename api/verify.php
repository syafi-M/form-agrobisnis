<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

$id = trim((string)($_GET['id'] ?? ''));
if (!preg_match('/^[a-f0-9]{32}$/', $id)) {
    http_response_code(400);
    exit('QR tidak valid.');
}

try {
    $data = checkin_xlsx(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'registrations.xlsx', $id);
} catch (Throwable) {
    http_response_code(500);
    exit('Data tidak dapat diverifikasi.');
}
if ($data === null) {
    http_response_code(404);
    exit('Registrasi tidak ditemukan.');
}
if ($data['kehadiran'] !== 'hadir') exit('Registrasi valid, tetapi peserta memilih tidak hadir.');
?>
<!doctype html>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Check-in berhasil</title>
<style>
body{margin:0;padding:40px 20px;background:#f5f3ed;color:#28291f;font:16px Arial;text-align:center}
main{max-width:420px;margin:auto;padding:28px;background:#fffefa;border:1px solid #e7e5da;border-radius:12px}
h1{color:#404735;font-size:28px}p{margin:12px 0}strong{color:#404735}
</style>
<main>
  <h1>✓ Check-in berhasil</h1>
  <p><strong><?= htmlspecialchars($data['nama'], ENT_QUOTES, 'UTF-8') ?></strong></p>
  <p><?= htmlspecialchars($data['tanggal'], ENT_QUOTES, 'UTF-8') ?></p>
  <p>Status: <strong><?= htmlspecialchars($data['status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
</main>
