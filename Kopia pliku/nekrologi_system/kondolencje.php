<?php
session_start();
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) { header("Location: login.php"); exit; }

$id = $_GET['id'] ?? null;
if (!$id) die("Brak ID");

$plik_komentarzy = 'komentarze.json';
$wszystkie_komentarze = file_exists($plik_komentarzy) ? json_decode(file_get_contents($plik_komentarzy), true) : [];
if (!is_array($wszystkie_komentarze)) $wszystkie_komentarze = [];

$imie_nazwisko = "Nekrolog #" . $id;
$plik_bazy = 'dane.json';
if (file_exists($plik_bazy)) {
    $dane = json_decode(file_get_contents($plik_bazy), true);
    foreach ($dane as $n) {
        if ((string)$n['id'] === (string)$id) {
            if ($_SESSION['rola'] !== 'superadmin' && (!isset($n['autor']) || $n['autor'] !== $_SESSION['login'])) {
                die("Brak uprawnień do zarządzania kondolencjami w tym nekrologu.");
            }
            $imie_nazwisko = $n['imie_nazwisko'];
            break;
        }
    }
}

if (isset($_GET['usun'])) {
    $id_kom = $_GET['usun'];
    if (isset($wszystkie_komentarze[$id])) {
        foreach ($wszystkie_komentarze[$id] as $key => $kom) {
            if ((string)$kom['id'] === (string)$id_kom) {
                unset($wszystkie_komentarze[$id][$key]);
                $wszystkie_komentarze[$id] = array_values($wszystkie_komentarze[$id]);
                file_put_contents($plik_komentarzy, json_encode($wszystkie_komentarze, JSON_PRETTY_PRINT));
                break;
            }
        }
    }
    header("Location: kondolencje.php?id=" . $id . "&status=deleted");
    exit;
}

$komentarze = $wszystkie_komentarze[$id] ?? [];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zarządzaj Kondolencjami</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f9; padding: 40px; }
        .admin-container { background: white; padding: 40px; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .top-bar { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;}
        .btn-danger { background: #dc3545; color:white; padding:5px 10px; text-decoration:none; display:inline-block; border-radius:3px; font-size: 14px; }
        .btn-danger:hover { background: #c82333; }
        .komentarz { border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; border-left: 4px solid #6e3b4f; }
        .komentarz-header { font-size: 0.9em; color:#666; display:flex; justify-content:space-between; margin-bottom:10px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="top-bar">
            <h2>Kondolencje (<?php echo htmlspecialchars($imie_nazwisko); ?>)</h2>
            <div style="margin-top: 10px;">
                <?php if($_SESSION['rola'] === 'superadmin' || (isset($_SESSION['impersonator']) && $_SESSION['impersonator'] === 'superadmin')): ?>
                    <a href="superadmin.php<?php echo isset($_SESSION['impersonator']) ? '?wroc_do_szefa=1' : ''; ?>" style="color: #17a2b8; text-decoration: none; margin-right: 15px; font-weight:bold;">Główne centrum SaaS</a>
                <?php endif; ?>
                <a href="otworz.php?id=<?php echo htmlspecialchars($id); ?>" target="_blank" style="color: #28a745; text-decoration: none; margin-right: 15px; font-weight:bold; display:inline-block; margin-top:10px;">Zobacz podstronę nekrologu</a>
                <a href="lista.php" style="color: #6e3b4f; text-decoration: none; font-weight:bold; display:inline-block; margin-top:10px;">⬅ Powrót do Listy</a>
        </div>
        
        <?php if(empty($komentarze)) echo "<p>Brak kondolencji dla tego nekrologu. Świeci pustkami...</p>"; ?>
        
        <?php foreach($komentarze as $kom): ?>
            <div class="komentarz">
                <div class="komentarz-header">
                    <span><strong><?php echo htmlspecialchars($kom['author']); ?></strong> (<?php echo htmlspecialchars($kom['date']); ?>)</span>
                    <a href="?id=<?php echo htmlspecialchars($id); ?>&usun=<?php echo htmlspecialchars($kom['id']); ?>" class="btn-danger" onclick="return confirm('Usunąć ten komentarz? Zniknie on ze strony klienta bezpowrotnie.');">Usuń</a>
                </div>
                <div><?php echo htmlspecialchars(nl2br($kom['text'])); ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
