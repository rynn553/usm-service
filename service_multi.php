<?php

declare(strict_types=1);
const HARDWARE_SERVICE_NAME = 'Perkhidmatan Penukaran/ Pemasangan Perkakasan Komputer';
function multi_ids(mixed $raw, array $types): array
{
    if (!is_array($raw) || !$raw || count($raw) > 30) throw new InvalidArgumentException('Pilih sekurang-kurangnya satu jenis perkhidmatan.');
    $allowed = array_map(static fn($t) => (int)$t['id'], $types);
    $ids = [];
    foreach ($raw as $value) {
        if (!is_string($value) || !ctype_digit($value) || !in_array((int)$value, $allowed, true)) throw new InvalidArgumentException('Jenis perkhidmatan tidak sah.');
        $ids[] = (int)$value;
    }
    return array_values(array_unique($ids));
}
function hardware_service_id(array $types): ?int
{
    foreach ($types as $type) if (preg_match('/Penukaran\s*\/\s*Pemasangan Perkakasan/i', $type['name'])) return (int)$type['id'];
    return null;
}
function multi_hardware(mixed $raw, bool $required): array
{
    if (!$required) return [];
    if (!is_array($raw) || !$raw || count($raw) > 20) throw new InvalidArgumentException('Tambah sekurang-kurangnya satu rekod perkakasan (maksimum 20).');
    $result = [];
    foreach ($raw as $item) {
        if (!is_array($item)) throw new InvalidArgumentException('Maklumat perkakasan tidak sah.');
        $fields = [];
        foreach (['work_type', 'hardware_type', 'old_model', 'new_model', 'old_serial', 'new_serial', 'notes'] as $key) {
            if (!is_string($item[$key] ?? null)) throw new InvalidArgumentException('Maklumat perkakasan tidak lengkap.');
            $fields[$key] = trim($item[$key]);
        }
        if (!in_array($fields['work_type'], ['replacement', 'installation'], true) || $fields['hardware_type'] === '' || $fields['new_model'] === '') throw new InvalidArgumentException('Jenis kerja, jenis perkakasan dan model baharu wajib diisi.');
        foreach (['hardware_type' => 120, 'old_model' => 255, 'new_model' => 255, 'old_serial' => 150, 'new_serial' => 150, 'notes' => 1000] as $key => $limit) if (mb_strlen($fields[$key]) > $limit) throw new InvalidArgumentException('Maklumat perkakasan terlalu panjang.');
        if ($fields['work_type'] === 'installation') {
            $fields['old_model'] = '';
            $fields['old_serial'] = '';
        }
        $result[] = $fields;
    }
    return $result;
}
function save_multi(PDO $pdo, int $id, array $ids, array $hardware): void
{
    $pdo->prepare('DELETE FROM service_request_types WHERE request_id=?')->execute([$id]);
    $insert = $pdo->prepare('INSERT INTO service_request_types(request_id,service_type_id) VALUES (?,?)');
    foreach ($ids as $type) $insert->execute([$id, $type]);
    $pdo->prepare('DELETE FROM service_request_hardware WHERE request_id=?')->execute([$id]);
    $insert = $pdo->prepare('INSERT INTO service_request_hardware(request_id,work_type,hardware_type,old_model,new_model,old_serial,new_serial,notes) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($hardware as $h) $insert->execute([$id, $h['work_type'], $h['hardware_type'], $h['old_model'] ?: null, $h['new_model'], $h['old_serial'] ?: null, $h['new_serial'] ?: null, $h['notes'] ?: null]);
}
function fetch_multi(PDO $pdo, array $rows): array
{
    if (!$rows) return $rows;
    $ids = array_map(static fn($r) => (int)$r['id'], $rows);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT request_id,service_type_id FROM service_request_types WHERE request_id IN ($placeholders) ORDER BY service_type_id");
    $stmt->execute($ids);
    $types = [];
    foreach ($stmt as $r) $types[(int)$r['request_id']][] = (int)$r['service_type_id'];
    $stmt = $pdo->prepare("SELECT * FROM service_request_hardware WHERE request_id IN ($placeholders) ORDER BY id");
    $stmt->execute($ids);
    $hardware = [];
    foreach ($stmt as $r) $hardware[(int)$r['request_id']][] = $r;
    foreach ($rows as &$r) {
        $id = (int)$r['id'];
        $r['service_type_ids'] = $types[$id] ?? [(int)$r['service_type_id']];
        $r['hardware'] = $hardware[$id] ?? [];
    }
    unset($r);
    return $rows;
}


function device_input(array $post): array
{
    $keys = ['requester_phone', 'requester_email', 'device_ownership', 'device_type', 'device_type_other', 'device_brand', 'device_model', 'device_serial', 'asset_number', 'accessory_other', 'device_condition', 'condition_notes'];
    $d = [];
    foreach ($keys as $key) $d[$key] = is_string($post[$key] ?? null) ? trim($post[$key]) : '';
    $raw = $post['accessories'] ?? [];
    if (!is_array($raw) || count($raw) > 4) throw new InvalidArgumentException('Aksesori tidak sah.');
    $allowed = ['Charger', 'Mouse', 'Beg Laptop', 'Lain-lain'];
    foreach ($raw as $v) if (!is_string($v) || !in_array($v, $allowed, true)) throw new InvalidArgumentException('Aksesori tidak sah.');
    $d['accessories'] = array_values(array_unique($raw));
    if (!in_array('Lain-lain', $d['accessories'], true)) $d['accessory_other'] = '';
    if (!in_array($d['device_ownership'], ['usm', 'personal'], true)) throw new InvalidArgumentException('Pilih pemilikan peranti.');
    if (!in_array($d['device_type'], ['Laptop', 'Desktop', 'Printer', 'Other'], true)) throw new InvalidArgumentException('Pilih jenis peranti.');
    if ($d['device_type'] !== 'Other') $d['device_type_other'] = '';
    if ($d['device_type'] === 'Other' && $d['device_type_other'] === '') throw new InvalidArgumentException('Nyatakan jenis peranti.');
    if ($d['device_ownership'] === 'personal') $d['asset_number'] = '';
    if ($d['device_brand'] === '' || $d['device_model'] === '') throw new InvalidArgumentException('Jenama dan model peranti wajib diisi.');
    foreach (['requester_phone' => 30, 'requester_email' => 190, 'device_type_other' => 100, 'device_brand' => 120, 'device_model' => 150, 'device_serial' => 150, 'asset_number' => 150, 'accessory_other' => 255, 'condition_notes' => 1000] as $k => $max) if (mb_strlen($d[$k]) > $max) throw new InvalidArgumentException('Maklumat peranti atau pemohon terlalu panjang.');
    if ($d['requester_email'] !== '' && !filter_var($d['requester_email'], FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Format emel tidak sah.');
    if ($d['requester_phone'] !== '' && !preg_match('/^[0-9+() .-]{7,30}$/D', $d['requester_phone'])) throw new InvalidArgumentException('Format nombor telefon tidak sah.');
    if (!in_array($d['device_condition'], ['unchecked', 'good', 'damaged', 'off'], true)) throw new InvalidArgumentException('Keadaan peranti tidak sah.');
    return $d;
}
function save_device(PDO $pdo, int $id, array $d): void
{
    $cols = ['requester_phone', 'requester_email', 'device_ownership', 'device_type', 'device_type_other', 'device_brand', 'device_model', 'device_serial', 'asset_number', 'device_condition', 'condition_notes'];
    $sets = implode(',', array_map(static fn($c) => "$c=?", $cols));
    $pdo->prepare("UPDATE service_requests SET $sets WHERE id=?")->execute([...array_map(static fn($c) => $d[$c] ?: null, $cols), $id]);
    $pdo->prepare('DELETE FROM service_request_accessories WHERE request_id=?')->execute([$id]);
    $stmt = $pdo->prepare('INSERT INTO service_request_accessories(request_id,accessory_name) VALUES (?,?)');
    foreach ($d['accessories'] as $v) $stmt->execute([$id, $v === 'Lain-lain' ? $d['accessory_other'] ?: 'Lain-lain' : $v]);
}
function fetch_device(PDO $pdo, array $rows): array
{
    if (!$rows) return $rows;
    $ids = array_column($rows, 'id');
    $qs = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT request_id,accessory_name FROM service_request_accessories WHERE request_id IN ($qs)");
    $stmt->execute($ids);
    $access = [];
    foreach ($stmt as $a) $access[(int)$a['request_id']][] = $a['accessory_name'];
    foreach ($rows as &$r) $r['accessories'] = $access[(int)$r['id']] ?? [];
    unset($r);
    return $rows;
}
