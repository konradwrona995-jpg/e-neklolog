<?php
session_start();
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    header("Location: login.php");
    exit;
}
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $plik_bazy = 'dane.json';
    if (file_exists($plik_bazy)) {
        $dane = json_decode(file_get_contents($plik_bazy), true);
        
        $nowe_dane = [];
        $plik_html_do_usuniecia = '';
        foreach ($dane as $n) {
            if ((string)$n['id'] === (string)$id) {
                if ($_SESSION['rola'] !== 'superadmin' && (!isset($n['autor']) || $n['autor'] !== $_SESSION['login'])) {
                    die("Brak uprawnień do usunięcia tego nekrologu.");
                }
                $plik_html_do_usuniecia = KATALOG_FRONTENDU . 'nekrolog-' . $id . '.html';
            } else {
                $nowe_dane[] = $n;
            }
        }
        file_put_contents($plik_bazy, json_encode($nowe_dane, JSON_PRETTY_PRINT));
        
        // Remove the static HTML file
        if ($plik_html_do_usuniecia && file_exists($plik_html_do_usuniecia)) {
            @unlink($plik_html_do_usuniecia);
        }
        
        // Remove from nekrologi.html statically
        $lista_plik = KATALOG_FRONTENDU . 'nekrologi.html';
        $lista_html = @file_get_contents($lista_plik);
        if ($lista_html) {
            $wskaźnik_szukamy = 'nekrolog-' . $id . '.html"';
            $poz_linku = strpos($lista_html, $wskaźnik_szukamy);
            if ($poz_linku !== false) {
                // szukamy najbliższego <div class="obituary-card"> przed linkiem
                $start = strrpos(substr($lista_html, 0, $poz_linku), '<div class="obituary-card">');
                if ($start !== false) {
                    $koniec_karty = strpos($lista_html, '</div>', $poz_linku); // ends card-buttons
                    $koniec_karty = strpos($lista_html, '</div>', $koniec_karty + 6); // ends obituary-card
                    if ($koniec_karty !== false) {
                        $lista_html = substr($lista_html, 0, $start) . substr($lista_html, $koniec_karty + 6);
                        file_put_contents($lista_plik, $lista_html);
                    }
                }
            }
        }
    }
}
header("Location: lista.php?status=deleted");
exit;
