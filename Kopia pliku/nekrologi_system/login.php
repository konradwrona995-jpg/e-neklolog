<?php
session_start();

$plik_kont = 'konta.json';
$konta = file_exists($plik_kont) ? json_decode(file_get_contents($plik_kont), true) : [];
if (!is_array($konta)) $konta = [];

if (isset($_POST['login']) && isset($_POST['haslo'])) {
    $podany_login = trim($_POST['login']);
    $podane_haslo = trim($_POST['haslo']);
    
    $zalogowano = false;
    foreach ($konta as $konto) {
        if ($konto['login'] === $podany_login && $konto['haslo'] === $podane_haslo) {
            $_SESSION['zalogowany'] = true;
            $_SESSION['login'] = $konto['login'];
            $_SESSION['rola'] = $konto['rola'];
            $zalogowano = true;
            
            if ($konto['rola'] === 'superadmin') {
                header("Location: superadmin.php");
            } else {
                header("Location: panel.php");
            }
            exit;
        }
    }
    
    if (!$zalogowano) {
        $blad = "Nieprawidłowy login lub hasło!";
    }
}

if (isset($_SESSION['zalogowany']) && $_SESSION['zalogowany'] === true) {
    if ($_SESSION['rola'] === 'superadmin') header("Location: superadmin.php");
    else header("Location: panel.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie SaaS</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Lato', sans-serif; background: #fbfbfb; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-box { background: white; padding: 50px 40px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; width: 100%; max-width: 400px; border-top: 5px solid #6e3b4f; }
        h2 { color: #222; margin-top: 0; margin-bottom: 30px; }
        input[type="text"], input[type="password"] { padding: 12px; width: 100%; margin-bottom: 20px; border: 1px solid #e0e0e0; box-sizing: border-box; border-radius: 4px; font-family: inherit; }
        button { background: #6e3b4f; color: white; border: none; padding: 12px 20px; width: 100%; cursor: pointer; border-radius: 4px; font-family: inherit; text-transform: uppercase; font-weight: bold; transition: background 0.3s; }
        button:hover { background: #552d3d; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 0.9rem; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Panel Systemu SaaS</h2>
        <?php if(isset($blad)) echo "<div class='error'>$blad</div>"; ?>
        <form method="POST">
            <input type="text" name="login" placeholder="Twój login" required>
            <input type="password" name="haslo" placeholder="Twoje hasło" required>
            <button type="submit">Zaloguj się</button>
        </form>
    </div>
</body>
</html>