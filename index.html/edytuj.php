<?php
session_start();
if (!isset($_SESSION['zalogowany']) || $_SESSION['zalogowany'] !== true) {
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) die("Brak ID");

$dane = json_decode(file_get_contents('dane.json'), true) ?? [];
$nekrolog = null;
foreach($dane as $n) {
    if ((string)$n['id'] === (string)$id) {
        if ($_SESSION['rola'] !== 'superadmin' && (!isset($n['autor']) || $n['autor'] !== $_SESSION['login'])) {
            die("Brak dostępu: ten nekrolog należy do innego zakładu.");
        }
        $nekrolog = $n; break;
    }
}
if (!$nekrolog) die("Nie znaleziono nekrologu");
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Nekrolog</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f9; padding: 40px; }
        .admin-container { background: white; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; box-sizing: border-box; }
        .btn { background: #007bff; color: white; padding: 10px 15px; border: none; cursor: pointer; width: 100%; margin-top: 10px; font-weight:bold;}
        .btn:hover { background: #0056b3; }
        .top-bar { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;}
    </style>
</head>
<body>

    <div class="admin-container">
        <div class="top-bar">
            <h2>Edytuj Nekrolog</h2>
            <div style="margin-top: 10px;">
                <?php if($_SESSION['rola'] === 'superadmin' || (isset($_SESSION['impersonator']) && $_SESSION['impersonator'] === 'superadmin')): ?>
                    <a href="superadmin.php<?php echo isset($_SESSION['impersonator']) ? '?wroc_do_szefa=1' : ''; ?>" style="color: #17a2b8; text-decoration: none; margin-right: 15px; font-weight:bold;">Główne centrum SaaS</a>
                <?php endif; ?>
                <a href="otworz.php?id=<?php echo htmlspecialchars($id); ?>" target="_blank" style="color: #28a745; text-decoration: none; margin-right: 15px; font-weight:bold;">Zobacz podstronę nekrologu</a>
                <a href="lista.php" style="color: #6e3b4f; text-decoration: none; font-weight:bold;">⬅ Powrót do Listy</a>
            </div>
        </div>

        <form action="edytuj_zapisz.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
            <div class="form-group">
                <label>Imię i nazwisko</label>
                <input type="text" name="imie_nazwisko" value="<?php echo htmlspecialchars($nekrolog['imie_nazwisko']); ?>" required>
            </div>
            <div style="display: flex; gap: 10px;">
                <div class="form-group" style="flex: 1;"><label>Data ur.</label><input type="text" name="data_ur" value="<?php echo htmlspecialchars($nekrolog['data_ur']); ?>" required></div>
                <div class="form-group" style="flex: 1;"><label>Data śm.</label><input type="text" name="data_sm" value="<?php echo htmlspecialchars($nekrolog['data_sm']); ?>" required></div>
                <div class="form-group" style="flex: 1;"><label>Wiek</label><input type="text" name="wiek" value="<?php echo htmlspecialchars($nekrolog['wiek']); ?>" required></div>
            </div>
            <div class="form-group">
                <label>Data pogrzebu</label>
                <input type="text" name="data_pogrzebu" value="<?php echo htmlspecialchars($nekrolog['data_pogrzebu']); ?>" required>
            </div>
            <div class="form-group">
                <label>Msza Święta (data i godzina)</label>
                <input type="text" name="msza_swieta" value="<?php echo htmlspecialchars($nekrolog['msza_swieta'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Miejsce Mszy Świętej (do mapy Google)</label>
                <input type="text" name="msza_miejsce" value="<?php echo htmlspecialchars($nekrolog['msza_miejsce'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Wyprowadzenie do grobu o godz.</label>
                <input type="text" name="wyprowadzenie" value="<?php echo htmlspecialchars($nekrolog['wyprowadzenie'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Cmentarz</label>
                <input type="text" name="cmentarz" value="<?php echo htmlspecialchars($nekrolog['cmentarz'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Zdjęcie zmarłego (opcjonalnie)</label>
                <?php if(!empty($nekrolog['zdjecie'])): ?>
                    <div style="margin-bottom: 5px; font-size:12px; color:#555;">Obecne: <?php echo htmlspecialchars(basename($nekrolog['zdjecie'])); ?> . Wybierz nowe, by podmienić.</div>
                <?php endif; ?>
                <input type="file" name="zdjecie" accept="image/*">
            </div>
            <button type="submit" class="btn">Zapisz Zmiany i Opublikuj</button>
        </form>
    </div>

</body>
</html>
