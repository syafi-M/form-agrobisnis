<?php

declare(strict_types=1);

function validate_phone(mixed $value): bool
{
    return is_string($value) && preg_match('/^(08\d{8,13}|62\d{9,14})$/D', $value) === 1;
}

function validate_attendance(mixed $value): bool
{
    return is_string($value) && in_array($value, ['hadir', 'tidak hadir'], true);
}

function new_id(): string
{
    return bin2hex(random_bytes(16));
}

function json_response(int $status, array $body): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function column_name(int $number): string
{
    $name = '';
    while ($number > 0) {
        $number--;
        $name = chr(65 + $number % 26) . $name;
        $number = intdiv($number, 26);
    }
    return $name;
}

function sheet_row(array $values, int $row): string
{
    $cells = '';
    foreach (array_values($values) as $index => $value) {
        $ref = column_name($index + 1) . $row;
        $cells .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . xml((string)$value) . '</t></is></c>';
    }
    return '<row r="' . $row . '">' . $cells . '</row>';
}

function append_xlsx(string $path, array $values): void
{
    $lock = fopen($path . '.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Storage unavailable');
    }
    try {
        $zip = new ZipArchive();
        $exists = is_file($path);
        $mode = $exists ? ZipArchive::RDONLY : ZipArchive::CREATE;
        if ($zip->open($path, $mode) !== true) {
            throw new RuntimeException('Workbook unavailable');
        }
        if ($exists) {
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();
            if ($sheet === false || !preg_match('/<sheetData>(.*?)<\/sheetData>/s', $sheet, $match)) {
                throw new RuntimeException('Workbook invalid');
            }
            preg_match_all('/<row r="(\d+)"/', $match[1], $rows);
            $next = $rows[1] ? max(array_map('intval', $rows[1])) + 1 : 1;
            $sheet = preg_replace('/<\/sheetData>/', sheet_row($values, $next) . '</sheetData>', $sheet, 1);
            if ($zip->open($path) !== true || !$zip->deleteName('xl/worksheets/sheet1.xml') || !$zip->addFromString('xl/worksheets/sheet1.xml', $sheet)) {
                throw new RuntimeException('Workbook update failed');
            }
        } else {
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Registrasi" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . sheet_row(['ID', 'Nama', 'No Whatsapp', 'Kehadiran', 'Tanggal Hadir', 'Jumlah Buah', 'Dibuat Pada'], 1) . sheet_row($values, 2) . '</sheetData></worksheet>');
        }
        if ($zip->close() !== true) {
            throw new RuntimeException('Workbook save failed');
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
