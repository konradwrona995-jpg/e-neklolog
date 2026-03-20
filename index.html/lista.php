<?php
session_start();

if (isset($_GET['wyloguj'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    header("Location: login.php");
    exit;
}

$dane = [];
if (file_exists('dane.json')) {
    $dane = json_decode(file_get_contents('dane.json'), true);
    if (!is_array($dane)) $dane = [];
}

if ($_SESSION['rola'] !== 'superadmin') {
    $dane = array_filter($dane, function($n) {
        return isset($n['autor']) && $n['autor'] === $_SESSION['login'];
    });
}

$search = $_GET['q'] ?? '';
if ($search !== '') {
    $search_lower = strtolower($search);
    $dane = array_filter($dane, function($n) use ($search_lower) {
        return strpos(strtolower($n['imie_nazwisko']), $search_lower) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Lista Nekrologów</title>
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
            text-align: left;
        }
        h2 { margin: 0; color: #333; font-size: 22px; }
        .btn { background: #6e3b4f; color: white; padding: 10px 15px; border: none; cursor: pointer; text-decoration: none; font-size: 14px; border-radius: 4px; font-weight: bold; transition: background 0.3s;}
        .btn-sm { padding: 6px 10px; font-size: 13px; }
        .btn:hover { background: #552d3d; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; text-align: center; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background: #fdfdfd; font-weight: 600; color: #555; }
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input { flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-family: inherit; }
        .search-box input:focus { outline: none; border-color: #6e3b4f; }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <div class="admin-container">
            <div class="top-bar">
                <h2>Zarządzaj Nekrologami</h2>
            </div>

            <?php if(isset($_GET['status']) && $_GET['status'] == 'deleted') echo "<div class='success'>Nekrolog usunięty!</div>"; ?>

            <form method="GET" class="search-box">
                <input type="text" name="q" placeholder="Szukaj po nazwisku..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">Szukaj</button>
                <?php if($search): ?><a href="lista.php" class="btn" style="background:#888;">Wyczyść</a><?php endif; ?>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Imię i Nazwisko</th>
                        <th>Data Śmierci</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($dane as $n): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($n['imie_nazwisko']); ?></strong></td>
                        <td><?php echo htmlspecialchars($n['data_sm']); ?></td>
                        <td style="white-space: nowrap;">
                            <div style="display:flex; gap:6px; align-items:center;">
                                <a href="kondolencje.php?id=<?php echo htmlspecialchars($n['id']); ?>" class="btn btn-sm" style="background: #28a745;">Kondolencje</a>
                                <a href="edytuj.php?id=<?php echo htmlspecialchars($n['id']); ?>" class="btn btn-sm" style="background:#007bff;">Edytuj</a>
                                <a href="otworz.php?id=<?php echo htmlspecialchars($n['id']); ?>" target="_blank" class="btn btn-sm" style="background:#ffc107; color:#222; text-decoration:none;" title="Otwórz wygenerowaną podstronę w przeglądarce">Otwórz</a>
                                <a href="usun.php?id=<?php echo htmlspecialchars($n['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Na pewno usunąć?');">Usuń</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($dane)) echo "<tr><td colspan='3' style='text-align:center;'>Brak wpisów</td></tr>"; ?>
                </tbody>
            </table>
        </div>

        <div class="sidebar">
            <?php if(isset($_SESSION['rola']) && ($_SESSION['rola'] === 'superadmin' || (isset($_SESSION['impersonator']) && $_SESSION['impersonator'] === 'superadmin'))): ?>
                <a href="superadmin.php<?php echo isset($_SESSION['impersonator']) ? '?wroc_do_szefa=1' : ''; ?>" class="sidebar-link">Główne centrum SaaS</a>
            <?php endif; ?>
            <a href="panel.php" class="sidebar-link">Dodaj nowy</a>
            <a href="?wyloguj=1" class="sidebar-link logout-link">Wyloguj się</a>
        </div>
    </div>

</body>
</html>
