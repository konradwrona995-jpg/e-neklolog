<?php
session_start();
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    die("Brak dostępu.");
}
require_once 'config.php';

$id = $_GET['id'] ?? null;
if ($id) {
    $plik = str_replace('/', '\\', KATALOG_FRONTENDU . 'nekrolog-' . $id . '.html');
    if (file_exists($plik)) {
        // Wymuś lokalne otwarcie pliku w przeglądarce pod Windows (omija blokady security Chrome na linki file://)
        pclose(popen('start "" "' . $plik . '"', 'r'));
        
        // Zamyka automatycznie tę niewidzialną zakładkę
        echo "<script>window.close();</script>";
        echo "Otwieranie...";
        exit;
    } else {
        echo "Podstrona nekrologu na ten moment jeszcze nie istnieje fizycznie na dysku docelowym.";
    }
} else {
    echo "Brak ID.";
}
?>
