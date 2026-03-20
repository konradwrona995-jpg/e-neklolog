<?php
session_start();

if (isset($_GET['wroc_do_szefa']) && isset($_SESSION['impersonator']) && $_SESSION['impersonator'] === 'superadmin') {
    $_SESSION['login'] = 'szef';
    $_SESSION['rola'] = 'superadmin';
    unset($_SESSION['impersonator']);
    header("Location: superadmin.php");
    exit;
}

if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true || $_SESSION['rola'] !== 'superadmin') {
    header("Location: login.php");
    exit;
}

$plik_kont = 'konta.json';
$konta = [];
if (file_exists($plik_kont)) {
    $konta = json_decode(file_get_contents($plik_kont), true);
    if (!is_array($konta)) $konta = [];
}

$komunikat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Dodawanie nowego klienta
    if (isset($_POST['nowy_login']) && isset($_POST['nowe_haslo'])) {
        $nowy_login = trim($_POST['nowy_login']);
        $nowe_haslo = trim($_POST['nowe_haslo']);
        
        $istnieje = false;
        foreach ($konta as $konto) {
            if ($konto['login'] === $nowy_login) { $istnieje = true; break; }
        }
        
            if ($istnieje) {
                $komunikat = "<div class='error'>Błąd: Konto o loginie '$nowy_login' już istnieje!</div>";
            } else if ($nowy_login !== '' && $nowe_haslo !== '') {
                $konta[] = ['login' => $nowy_login, 'haslo' => $nowe_haslo, 'rola' => 'zaklad'];
                file_put_contents($plik_kont, json_encode($konta, JSON_PRETTY_PRINT));
                $komunikat = "<div class='success'>Pomyślnie dodano nowe konto dla zakładu: <strong>$nowy_login</strong></div>";
            }
    }
    
    // 2. Usuwanie konta
    if (isset($_POST['usun_login'])) {
        $login_do_usuniecia = $_POST['usun_login'];
        $konta = array_filter($konta, function($k) use ($login_do_usuniecia) {
            return $k['login'] !== $login_do_usuniecia || $k['rola'] === 'superadmin'; 
        });
        $konta = array_values($konta);
        file_put_contents($plik_kont, json_encode($konta, JSON_PRETTY_PRINT));
        $komunikat = "<div class='success'>Konto klienta odłączone! Jego nekrologi wciąż istnieją w bazie, do usunięcia ręcznego z Głównej Listy Nekrologów.</div>";
    }

    // 3. Zmiana hasła
    if (isset($_POST['zmien_haslo_login']) && isset($_POST['nowe_haslo_edycja'])) {
        $login_zmiana = $_POST['zmien_haslo_login'];
        $nowe_h = trim($_POST['nowe_haslo_edycja']);
        foreach ($konta as &$k) {
            if ($k['login'] === $login_zmiana) {
                $k['haslo'] = $nowe_h;
                break;
            }
        }
        file_put_contents($plik_kont, json_encode($konta, JSON_PRETTY_PRINT));
        $komunikat = "<div class='success'>Hasło dla konta <strong>$login_zmiana</strong> zostało pomyślnie zmienione.</div>";
    }

    // 4. Zaloguj jako (Impersonacja)
    if (isset($_POST['zaloguj_jako'])) {
        $_SESSION['impersonator'] = 'superadmin';
        $_SESSION['login'] = $_POST['zaloguj_jako'];
        $_SESSION['rola'] = 'zaklad';
        header("Location: panel.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Głównego Szefa</title>
    <style>
        body { font-family: 'Lato', sans-serif; background: #f4f4f9; padding: 40px; margin: 0; color: #222; }
        .admin-container { background: white; padding: 40px; border-radius: 8px; max-width: 1000px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .top-bar { display: flex; justify-content: space-between; border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 30px; }
        .top-bar h2 { margin: 0; color: #6e3b4f; }
        .btn { background: #6e3b4f; color: white; padding: 10px 15px; border: none; cursor: pointer; text-decoration: none; font-size: 14px; display: inline-block; border-radius: 4px; }
        .btn-danger { background: #dc3545; }
        .btn-outline { background: transparent; color: #6e3b4f; border: 1px solid #6e3b4f; }
        .btn-outline:hover { background: #fafafa; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ccc; box-sizing: border-box; border-radius: 4px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 12px; border: 1px solid #e0e0e0; text-align: left; vertical-align: middle; }
        th { background: #fbfbfb; color: #555; }
        .role-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .role-superadmin { background: #ffeeba; color: #856404; }
        .role-zaklad { background: #d4edda; color: #155724; }
    </style>
</head>
<body>

    <div class="admin-container">
        <div class="top-bar">
            <h2>Witaj, Szefie! (Panel Główny SaaS)</h2>
            <div style="display:flex; gap:10px;">
                <a href="lista.php" class="btn btn-outline">Baza Wszystkich Nekrologów</a>
                <a href="lista.php?wyloguj=1" class="btn btn-outline" style="border-color: red; color: red;">Wyloguj się</a>
            </div>
        </div>

        <?php echo $komunikat; ?>

        <h3>Utwórz licencję dla układu klienckiego</h3>
        <form method="POST" style="background:#fcfcfc; padding:20px; border:1px solid #eee; border-radius:4px;">
            <div style="display:flex; gap:15px; align-items: flex-end;">
                <div class="form-group" style="flex:1; margin:0;">
                    <label>Login zakładu:</label>
                    <input type="text" name="nowy_login" placeholder="Np. ZakladWrzos" required>
                </div>
                <div class="form-group" style="flex:1; margin:0;">
                    <label>Hasło dostępowe:</label>
                    <input type="password" name="nowe_haslo" placeholder="Tymczasowe hasło" required>
                </div>
                <button type="submit" class="btn" style="height:39px;">Zarejestruj klienta</button>
            </div>
        </form>

        <h3 style="margin-top: 40px;">Panel Operacyjny Kont</h2>
        <table>
            <thead>
                <tr>
                    <th>Login</th>
                    <th>Zarządzanie Hasłem</th>
                    <th>Rola</th>
                    <th>Narzędzia Zdalne</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($konta as $konto): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($konto['login']); ?></strong></td>
                    <td>
                        <form method="POST" style="display:flex; gap:5px; margin:0;">
                            <input type="hidden" name="zmien_haslo_login" value="<?php echo htmlspecialchars($konto['login']); ?>">
                            <input type="text" name="nowe_haslo_edycja" value="<?php echo htmlspecialchars($konto['haslo']); ?>" style="width:130px; padding:6px; margin:0;">
                            <button type="submit" class="btn" style="padding:6px 12px; margin:0; font-size:12px;">Aktualizuj</button>
                        </form>
                    </td>
                    <td>
                        <span class="role-badge role-<?php echo $konto['rola']; ?>">
                            <?php echo strtoupper($konto['rola']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if($konto['rola'] !== 'superadmin'): ?>
                            <div style="display:flex; gap:5px; align-items:center;">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="zaloguj_jako" value="<?php echo htmlspecialchars($konto['login']); ?>">
                                    <button type="submit" class="btn" style="background:#17a2b8; padding:8px; display:flex; align-items:center;" title="Przejmij sesję użytkownika" onclick="return confirm('Przejmiesz tożsamość tegą konta i stracisz dostęp szefa do momentu ponownego wylogowania ze środowiska klienta. Kontynuować?');">Wciel się</button>
                                </form>
                                <a href="galeria.php?klient=<?php echo htmlspecialchars($konto['login']); ?>" target="_blank" class="btn btn-outline" style="padding:7px; text-decoration:none;" title="Zobacz widget klienta">Iframe</a>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="usun_login" value="<?php echo htmlspecialchars($konto['login']); ?>">
                                    <button type="submit" class="btn btn-danger" style="padding:8px;" onclick="return confirm('System bezpowrotnie odepnie tego użytkownika z bazy logowań. Jesteś pewny?');">Usuń</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span style="color:#999; font-size:12px; font-style:italic;">Zarządca Serwera (Ochrona Systemu)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
