<?php
session_start();
// Zabezpieczenie: wyrzuca do logowania, jeśli ktoś nie wpisał hasła
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    header("Location: login.php");
    exit;
}

// Wylogowywanie
if (isset($_GET['wyloguj'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Panel Dodawania Nekrologu</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f4f4f9; 
            margin: 0;
            padding: 50px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            box-sizing: border-box;
        }
        .main-wrapper {
            display: flex;
            gap: 20px;
            width: 100%;
            max-width: 1050px;
            align-items: flex-start;
        }
        .admin-container { 
            background: white; 
            padding: 40px; 
            flex: 3;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            border-radius: 8px;
        }
        .sidebar {
            background: white;
            padding: 20px;
            flex: 1;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-width: 200px;
        }
        .sidebar-link {
            display: block;
            border: 2px solid #6e3b4f;
            color: #222;
            text-decoration: none;
            padding: 14px 10px;
            text-align: center;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: transparent;
        }
        .sidebar-link:hover {
            background: #6e3b4f;
            color: white;
            box-shadow: 0 4px 12px rgba(110, 59, 79, 0.3);
            transform: translateY(-2px);
        }
        .logout-link {
            border-color: #dc3545;
        }
        .logout-link:hover {
            background: #dc3545;
            color: white;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        .top-bar { 
            margin-bottom: 25px; 
            border-bottom: 2px solid #eee; 
            padding-bottom: 15px;
            text-align: center;
        }
        h2 { margin: 0; color: #333; font-size: 22px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 14px; }
        input { 
            width: 100%; 
            padding: 10px; 
            box-sizing: border-box; 
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: inherit;
        }
        input:focus { outline: none; border-color: #6e3b4f; }
        .btn { 
            background: #6e3b4f; 
            color: white; 
            padding: 12px 15px; 
            border: none; 
            cursor: pointer; 
            width: 100%; 
            margin-top: 15px;
            border-radius: 4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: background 0.3s;
        }
        .btn:hover { background: #552d3d; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; text-align: center; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <!-- Karta główna (Lewa strona) -->
        <div class="admin-container">
            <div class="top-bar">
                <h2>Panel Systemowy (Witaj, <?php echo htmlspecialchars($_SESSION['login'] ?? 'Użytkowniku'); ?>!)</h2>
            </div>

            <?php if(isset($_GET['status']) && $_GET['status'] == 'ok') echo "<div class='success'>Nekrolog został pomyślnie dodany!</div>"; ?>

            <form action="zapisz.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Imię i nazwisko (np. ŚP. JAN KOWALSKI)</label>
                    <input type="text" name="imie_nazwisko" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="form-group" style="flex: 1;"><label>Data ur.</label><input type="text" name="data_ur" placeholder="DD.MM.RRRR" required></div>
                    <div class="form-group" style="flex: 1;"><label>Data śm.</label><input type="text" name="data_sm" placeholder="DD.MM.RRRR" required></div>
                    <div class="form-group" style="flex: 1;"><label>Wiek</label><input type="text" name="wiek" required></div>
                </div>
                <div class="form-group">
                    <label>Data pogrzebu</label>
                    <input type="text" name="data_pogrzebu" placeholder="DD.MM.RRRR" required>
                </div>
                <div class="form-group">
                    <label>Msza Święta (data i godzina)</label>
                    <input type="text" name="msza_swieta" placeholder="np. 14.03.2026 o godz. 09:00" required>
                </div>
                <div class="form-group">
                    <label>Miejsce Mszy Świętej (do mapy Google)</label>
                    <input type="text" name="msza_miejsce" placeholder="np. Kaplica na Starym Cmentarzu, Jelenia Góra, ul. Sudecka 44" required>
                </div>
                <div class="form-group">
                    <label>Wyprowadzenie do grobu o godz.</label>
                    <input type="text" name="wyprowadzenie" placeholder="np. 09:00" required>
                </div>
                <div class="form-group">
                    <label>Cmentarz</label>
                    <input type="text" name="cmentarz" placeholder="np. Cmentarz Komunalny w Jeleniej Górze (STARY), Jelenia Góra, ul. Sudecka 44" required>
                </div>
                <div class="form-group">
                    <label>Zdjęcie zmarłego (opcjonalnie)</label>
                    <input type="file" name="zdjecie" accept="image/*">
                </div>
                <button type="submit" class="btn">Opublikuj na stronie</button>
            </form>
        </div>

        <!-- Osobny biały kwadrat z prawej strony (Menu) -->
        <div class="sidebar">
            <?php if(isset($_SESSION['rola']) && ($_SESSION['rola'] === 'superadmin' || (isset($_SESSION['impersonator']) && $_SESSION['impersonator'] === 'superadmin'))): ?>
                <a href="superadmin.php<?php echo isset($_SESSION['impersonator']) ? '?wroc_do_szefa=1' : ''; ?>" class="sidebar-link">Główne centrum SaaS</a>
            <?php endif; ?>
            
            <a href="lista.php" class="sidebar-link">Lista Nekrologów</a>
            <a href="?wyloguj=1" class="sidebar-link logout-link">Wyloguj się</a>
        </div>
    </div>

</body>
</html>