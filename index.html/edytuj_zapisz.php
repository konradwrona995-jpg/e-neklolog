<?php
session_start();
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) { die(); }
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $plik_bazy = 'dane.json';
    $dane = json_decode(file_get_contents($plik_bazy), true);
    
    $obecne_zdjecie = '';
    foreach ($dane as &$n) {
        if ((string)$n['id'] === (string)$id) {
            if ($_SESSION['rola'] !== 'superadmin' && (!isset($n['autor']) || $n['autor'] !== $_SESSION['login'])) {
                die("Brak uprawnień do edycji tego nekrologu.");
            }
            $obecne_zdjecie = $n['zdjecie'] ?? '';
            // Upload new image if present
            if (isset($_FILES['zdjecie']) && $_FILES['zdjecie']['error'] == UPLOAD_ERR_OK) {
                $uploads_dir = UPLOADS_DIR;
                if (!is_dir($uploads_dir)) @mkdir($uploads_dir, 0777, true);
                
                $ext = pathinfo($_FILES['zdjecie']['name'], PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nazwa_zdjecia = 'foto_' . time() . '_' . rand(1000,9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['zdjecie']['tmp_name'], $uploads_dir . $nazwa_zdjecia)) {
                        // Optionally delete old photo here, but keeping is safer
                        $obecne_zdjecie = 'uploads/' . $nazwa_zdjecia;
                    }
                }
            }

            $n['imie_nazwisko'] = $_POST['imie_nazwisko'];
            $n['data_ur'] = $_POST['data_ur'];
            $n['data_sm'] = $_POST['data_sm'];
            $n['wiek'] = $_POST['wiek'];
            $n['data_pogrzebu'] = $_POST['data_pogrzebu'];
            $n['msza_swieta'] = $_POST['msza_swieta'];
            $n['msza_miejsce'] = $_POST['msza_miejsce'];
            $n['wyprowadzenie'] = $_POST['wyprowadzenie'];
            $n['cmentarz'] = $_POST['cmentarz'];
            $n['zdjecie'] = $obecne_zdjecie;
            break;
        }
    }
    
    file_put_contents($plik_bazy, json_encode($dane, JSON_PRETTY_PRINT));
    
    // Generowanie HTML
    $docelowy_katalog = KATALOG_FRONTENDU;
    $szablon_zawartosc = @file_get_contents($docelowy_katalog . 'wzor-nekrolog.html');
    if (!$szablon_zawartosc) $szablon_zawartosc = @file_get_contents($docelowy_katalog . 'nekrolog.html');
    
    $msza_str = htmlspecialchars($_POST['msza_swieta']) . ', ' . htmlspecialchars($_POST['msza_miejsce']);
    $mapa_url = 'https://maps.google.com/maps?q=' . urlencode($_POST['msza_miejsce']) . '&t=&z=13&ie=UTF8&iwloc=&output=embed';

    $nowy_html = str_replace(
        ['{{IMIE_NAZWISKO}}', '{{DATA_UR}}', '{{DATA_SM}}', '{{WIEK}}', '{{DATA_POGRZEBU}}'],
        [htmlspecialchars($_POST['imie_nazwisko']), htmlspecialchars($_POST['data_ur']), htmlspecialchars($_POST['data_sm']), htmlspecialchars($_POST['wiek']), htmlspecialchars($_POST['data_pogrzebu'])],
        $szablon_zawartosc
    );
    $nowy_html = str_replace(
        ['ŚP. MICHAŁ WÓJCIK', '27.09.1947 - 10.03.2026', 'Wiek: 78 lat', 'Data pogrzebu: 14.03.2026'],
        [htmlspecialchars($_POST['imie_nazwisko']), htmlspecialchars($_POST['data_ur']) . ' - ' . htmlspecialchars($_POST['data_sm']), 'Wiek: ' . htmlspecialchars($_POST['wiek']) . ' lat', 'Data pogrzebu: ' . htmlspecialchars($_POST['data_pogrzebu'])],
        $nowy_html
    );

    $nowy_html = str_replace(
        '<li><strong>Msza Święta:</strong> 14.03.2026 o godz. 09:00, Kaplica na Starym Cmentarzu, Jelenia Góra, ul. Sudecka 44</li>',
        '<li><strong>Msza Święta:</strong> ' . $msza_str . '</li>',
        $nowy_html
    );
    $nowy_html = str_replace(
        '<li><strong>Wyprowadzenie do grobu o godz.</strong> 09:00</li>',
        '<li><strong>Wyprowadzenie do grobu o godz.</strong> ' . htmlspecialchars($_POST['wyprowadzenie']) . '</li>',
        $nowy_html
    );
    $nowy_html = str_replace(
        '<li><strong>Cmentarz:</strong> Cmentarz Komunalny w Jeleniej Górze (STARY), Jelenia Góra, ul. Sudecka 44</li>',
        '<li><strong>Cmentarz:</strong> ' . htmlspecialchars($_POST['cmentarz']) . '</li>',
        $nowy_html
    );

    $nowy_html = preg_replace(
        '/<iframe[^>]*google\.com\/maps[^>]*>.*?<\/iframe>/is',
        '<iframe src="' . $mapa_url . '" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
        $nowy_html
    );

    $foto_html_lista = '';
    $cross_style_lista = 'transform: scale(1.5); margin: 30px auto;';
    
    $foto_html_sub = '';
    $cross_style_sub = 'transform: scale(1.5); margin: 40px auto;';

    if (!empty($obecne_zdjecie)) {
        $foto_html_lista = '<img class="photo-frame" src="' . $obecne_zdjecie . '" alt="Zdjęcie zmarłego" style="object-fit:cover;">';
        $cross_style_lista = '';
        
        $foto_html_sub = '<img class="photo-frame lg" src="' . $obecne_zdjecie . '" alt="Zdjęcie zmarłego" style="object-fit:cover;">';
        $cross_style_sub = '';
    }
    
    // Replace in subpage template using robust regex
    $nowy_html = preg_replace('/<div class="photo-frame lg"[^>]*>.*?<\/div>/is', $foto_html_sub, $nowy_html);
    $nowy_html = preg_replace('/<div class="css-cross lg"[^>]*><\/div>/is', '<div class="css-cross lg" style="' . $cross_style_sub . '"></div>', $nowy_html);
    
    // Fallback if older template
    $nowy_html = preg_replace('/<div class="photo-frame"[^>]*>.*?<\/div>/is', $foto_html_lista, $nowy_html);
    $nowy_html = preg_replace('/<div class="css-cross"[^>]*><\/div>/is', '<div class="css-cross" style="' . $cross_style_lista . '"></div>', $nowy_html);

    // Wstrzyknięcie unikalnego ID dla podstrony by JS wiedział skąd ładować komentarze
    $nowy_html = str_replace('{{ID_NEKROLOGU}}', $id, $nowy_html);

    $nazwa_pliku = 'nekrolog-' . $id . '.html';
    file_put_contents($docelowy_katalog . $nazwa_pliku, $nowy_html);
    
    // AKTUALIZACJA LISTY W nekrologi.html
    $lista_plik = $docelowy_katalog . 'nekrologi.html';
    $lista_html = @file_get_contents($lista_plik);
    
    if ($lista_html) {
        $nowa_karta = '
            <div class="obituary-card">
                <div class="ribbon"></div>
                <div class="memorial-graphics">
                    <div class="css-cross" style="' . $cross_style_lista . '"></div>
                    ' . $foto_html_lista . '
                </div>
                <h3>' . htmlspecialchars($_POST['imie_nazwisko']) . '</h3>
                <p>' . htmlspecialchars($_POST['data_ur']) . ' - ' . htmlspecialchars($_POST['data_sm']) . '</p>
                <p>Wiek: ' . htmlspecialchars($_POST['wiek']) . ' lat</p>
                <p style="margin-top: 15px; font-size: 0.85rem;">Data pogrzebu: ' . htmlspecialchars($_POST['data_pogrzebu']) . '</p>
                <div class="card-buttons">
                    <a href="' . $nazwa_pliku . '" class="btn-outline">Więcej informacji</a>
                    <a href="' . $nazwa_pliku . '#kondolencje" class="btn-outline">Złóż kondolencje</a>
                </div>
            </div>
';
        $wskaźnik_szukamy = 'nekrolog-' . $id . '.html"';
        $poz_linku = strpos($lista_html, $wskaźnik_szukamy);
        if ($poz_linku !== false) {
            $start = strrpos(substr($lista_html, 0, $poz_linku), '<div class="obituary-card">');
            if ($start !== false) {
                // szukając konca karty, musimy przejsc obok linków
                $koniec_karty = strpos($lista_html, '</div>', $poz_linku);
                $koniec_karty = strpos($lista_html, '</div>', $koniec_karty + 6); // end obituary-card
                if ($koniec_karty !== false) {
                    $lista_html = substr($lista_html, 0, $start) . "\n" . trim($nowa_karta) . "\n" . substr($lista_html, $koniec_karty + 6);
                    file_put_contents($lista_plik, $lista_html);
                }
            }
        }
    }
}
header("Location: lista.php?status=ok");
exit;
