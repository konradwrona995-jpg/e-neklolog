<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$id_nekrologu = $_GET['id'] ?? '';
$plik_komentarzy = 'komentarze.json';

if (!$id_nekrologu) {
    echo json_encode(['error' => 'Brak ID']);
    exit;
}

$wszystkie_komentarze = file_exists($plik_komentarzy) ? json_decode(file_get_contents($plik_komentarzy), true) : [];
if (!is_array($wszystkie_komentarze)) $wszystkie_komentarze = [];

if ($action === 'load') {
    $komentarze_nekrologu = $wszystkie_komentarze[$id_nekrologu] ?? [];
    echo json_encode($komentarze_nekrologu);
    exit;
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && !empty($input['text'])) {
        $nowy_komentarz = [
            'id' => time() . rand(100, 999),
            'author' => htmlspecialchars(substr($input['author'] ?? 'Anonim', 0, 100)),
            'text' => htmlspecialchars(substr($input['text'], 0, 1000)),
            'date' => date('d.m.Y H:i')
        ];
        
        if (!isset($wszystkie_komentarze[$id_nekrologu])) {
            $wszystkie_komentarze[$id_nekrologu] = [];
        }
        
        array_unshift($wszystkie_komentarze[$id_nekrologu], $nowy_komentarz);
        file_put_contents($plik_komentarzy, json_encode($wszystkie_komentarze, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['error' => 'Brak tresci']);
    }
    exit;
}
echo json_encode(['error' => 'Nieznana akcja']);
