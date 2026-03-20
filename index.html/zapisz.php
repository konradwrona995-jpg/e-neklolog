<?php
session_start();
if (!isset($_SESSION['zalogowany'])) { die("Brak dostępu."); }
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plik_bazy = 'dane.json';
    
    // Pobierz obecne dane z pliku (jeśli plik nie istnieje, stwórz pustą tablicę)
    $obecne_dane = [];
    if (file_exists($plik_bazy)) {
        $zawartosc = file_get_contents($plik_bazy);
        $obecne_dane = json_decode($zawartosc, true);
        if (!is_array($obecne_dane)) { $obecne_dane = []; }
    }

    // Upload image
    $zdjecie_plik = '';
    if (isset($_FILES['zdjecie']) && $_FILES['zdjecie']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = UPLOADS_DIR;
        if (!is_dir($uploads_dir)) {
            @mkdir($uploads_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['zdjecie']['name'], PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $nazwa_zdjecia = 'foto_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['zdjecie']['tmp_name'], $uploads_dir . $nazwa_zdjecia)) {
                $zdjecie_plik = 'uploads/' . $nazwa_zdjecia;
            }
        }
    }

    // Zbuduj paczkę z nowym nekrologiem
    $nowy_nekrolog = [
        "id" => time(), // Unikalne ID oparte na czasie
        "autor" => $_SESSION['login'],
        "imie_nazwisko" => $_POST['imie_nazwisko'],
        "data_ur" => $_POST['data_ur'],
        "data_sm" => $_POST['data_sm'],
        "wiek" => $_POST['wiek'],
        "data_pogrzebu" => $_POST['data_pogrzebu'],
        "msza_swieta" => $_POST['msza_swieta'],
        "msza_miejsce" => $_POST['msza_miejsce'],
        "wyprowadzenie" => $_POST['wyprowadzenie'],
        "cmentarz" => $_POST['cmentarz'],
        "zdjecie" => $zdjecie_plik
    ];

    // Dodaj nowy nekrolog na SAM POCZĄTEK listy (żeby najnowsze były u góry)
    array_unshift($obecne_dane, $nowy_nekrolog);

    // Zapisz wszystko z powrotem do pliku JSON
    file_put_contents($plik_bazy, json_encode($obecne_dane, JSON_PRETTY_PRINT));

    // GENEROWANIE PLIKU HTML NA DYSKU Z:
    $docelowy_katalog = KATALOG_FRONTENDU;
    
    // Szablon
    $szablon_zawartosc = @file_get_contents($docelowy_katalog . 'wzor-nekrolog.html');
    if (!$szablon_zawartosc) {
        $szablon_zawartosc = @file_get_contents($docelowy_katalog . 'nekrolog.html');
    }
    
    $msza_str = htmlspecialchars($_POST['msza_swieta']) . ', ' . htmlspecialchars($_POST['msza_miejsce']);
    $mapa_url = 'https://maps.google.com/maps?q=' . urlencode($_POST['msza_miejsce']) . '&t=&z=13&ie=UTF8&iwloc=&output=embed';

    // Podmieniamy dane (obsluguje zarowno nowy wzor z markerami, jak i stary nekrolog.html)
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

    // Nowe pola - podmieniamy cale linie z oryginalnego nekrolog.html zeby zachowac formatowanie
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

    // Podmiana mapy (wyszukanie tagu iframe i podmiana jego SRC)
    $nowy_html = preg_replace(
        '/<iframe[^>]*google\.com\/maps[^>]*>.*?<\/iframe>/is',
        '<iframe src="' . $mapa_url . '" width="100%" height="100%" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
        $nowy_html
    );

    $foto_html_lista = '';
    $cross_style_lista = 'transform: scale(1.5); margin: 30px auto;';
    
    $foto_html_sub = '';
    $cross_style_sub = 'transform: scale(1.5); margin: 40px auto;';

    if (!empty($zdjecie_plik)) {
        $foto_html_lista = '<img class="photo-frame" src="' . $zdjecie_plik . '" alt="Zdjęcie zmarłego" style="object-fit:cover;">';
        $cross_style_lista = '';
        
        $foto_html_sub = '<img class="photo-frame lg" src="' . $zdjecie_plik . '" alt="Zdjęcie zmarłego" style="object-fit:cover;">';
        $cross_style_sub = '';
    }
    
    $nowy_html = preg_replace('/<div class="photo-frame lg"[^>]*>.*?<\/div>/is', $foto_html_sub, $nowy_html);
    $nowy_html = preg_replace('/<div class="css-cross lg"[^>]*><\/div>/is', '<div class="css-cross lg" style="' . $cross_style_sub . '"></div>', $nowy_html);
    
    $nowy_html = preg_replace('/<div class="photo-frame"[^>]*>.*?<\/div>/is', $foto_html_lista, $nowy_html);
    $nowy_html = preg_replace('/<div class="css-cross"[^>]*><\/div>/is', '<div class="css-cross" style="' . $cross_style_lista . '"></div>', $nowy_html);

    // Wstrzyknięcie unikalnego ID dla podstrony by JS wiedział skąd ładować komentarze
    $nowy_html = str_replace('{{ID_NEKROLOGU}}', $nowy_nekrolog['id'], $nowy_html);

    $nazwa_pliku = 'nekrolog-' . $nowy_nekrolog['id'] . '.html';
    file_put_contents($docelowy_katalog . $nazwa_pliku, $nowy_html);

    // AKTUALIZACJA PLIKU NEKROLOGI.HTML (LISTY)
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
        $wskaźnik = '<section class="obituary-grid" id="obituary-grid">';
        $pozycja = strpos($lista_html, $wskaźnik);
        if ($pozycja !== false) {
            $wstaw_po = $pozycja + strlen($wskaźnik);
            $lista_html = substr_replace($lista_html, "\n" . $nowa_karta, $wstaw_po, 0);
            file_put_contents($lista_plik, $lista_html);
        }
    }

    // Wróć do panelu z sukcesem
    header("Location: panel.php?status=ok");
    exit;
}
?>