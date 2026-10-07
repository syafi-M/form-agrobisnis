<?php

declare(strict_types=1);
require __DIR__ . '/lib.php';

$allowedOrigin = getenv('FRONTEND_ORIGIN') ?: '';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') {
    if ($allowedOrigin !== '' && hash_equals($allowedOrigin, $origin)) header('Access-Control-Allow-Origin: ' . $origin);
    else if ($allowedOrigin !== '') json_response(403, ['ok' => false, 'error' => 'Origin tidak diizinkan.']);
    header('Vary: Origin');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(405, ['ok' => false, 'error' => 'Method tidak diizinkan.']);

$input = json_decode(file_get_contents('php://input'), true);
$errors = [];
if (!is_array($input)) $errors['form'] = 'JSON tidak valid.';
$nama = is_array($input) ? trim((string)($input['nama'] ?? '')) : '';
$whatsapp = is_array($input) ? (string)($input['whatsapp'] ?? '') : '';
$kehadiran = is_array($input) ? (string)($input['kehadiran'] ?? '') : '';
$tanggal = is_array($input) ? (string)($input['tanggal'] ?? '') : '';
$kilo = is_array($input) ? (string)($input['kilo'] ?? '') : ''; 
if ($nama === '' || mb_strlen($nama) > 120) $errors['nama'] = 'Nama wajib diisi (maksimal 120 karakter).';
if (!validate_phone($whatsapp)) $errors['whatsapp'] = 'Nomor WhatsApp tidak valid.';
if (!validate_attendance($kehadiran)) $errors['kehadiran'] = 'Pilihan kehadiran tidak valid.';
if (!preg_match('/^2026-10-(2[2-5])$/D', $tanggal)) $errors['tanggal'] = 'Tanggal hadir harus 22 sampai 25 Oktober 2026.';
if (!is_numeric($kilo) || (float)$kilo <= 0 || (float)$kilo > 100) $errors['kilo'] = 'Jumlah kg harus antara 0,1 sampai 100 kg.';
if ($errors) json_response(422, ['ok' => false, 'error' => 'Periksa data Anda.', 'fields' => $errors]);

$id = new_id();
$created = gmdate('c');
$dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) json_response(500, ['ok' => false, 'error' => 'Data gagal disimpan.']);
try {
    append_xlsx($dir . DIRECTORY_SEPARATOR . 'registrations.xlsx', [$id, $nama, $whatsapp, $kehadiran, $tanggal, $kilo . ' kg', $created, '', '']);
} catch (Throwable) {
    json_response(500, ['ok' => false, 'error' => 'Data gagal disimpan.']);
}
json_response(201, ['ok' => true, 'data' => compact('id', 'nama', 'whatsapp', 'kehadiran', 'tanggal', 'kilo')]);
