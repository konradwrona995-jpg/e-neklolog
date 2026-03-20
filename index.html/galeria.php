<?php
$plik_dane = 'dane.json';
$dane = [];
if (file_exists($plik_dane)) {
    $dane = json_decode(file_get_contents($plik_dane), true);
    if (!is_array($dane)) $dane = [];
}

if (isset($_GET['klient']) && $_GET['klient'] !== '') {
    $klient = $_GET['klient'];
    $dane = array_filter($dane, function($n) use ($klient) {
        return isset($n['autor']) && $n['autor'] === $klient;
    });
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Widget Galerii Nekrologów</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        body { 
            margin: 0; padding: 20px; font-family: 'Lato', sans-serif; background: transparent; color: #222;
        }
        .empty-state {
            text-align: center; font-style: italic; color: #666; font-size: 1.1rem; padding: 40px; border: 1px dashed #ccc;
        }
        .obituary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }
        .obituary-card {
            background: white; border: 1px solid #e0e0e0;
            padding: 30px 20px; text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative;
        }
        .ribbon {
            position: absolute; top: 0; left: 0; width: 0; height: 0;
            border-style: solid; border-width: 40px 40px 0 0;
            border-color: #000 transparent transparent transparent;
        }
        .memorial-graphics {
            display: flex; justify-content: center; align-items: center; gap: 15px;
            margin-bottom: 25px; flex-direction: column;
        }
        .css-cross { position: relative; width: 10px; height: 60px; background-color: #222; margin-bottom: 10px; }
        .css-cross::before {
            content: ''; position: absolute; top: 15px; left: -14px;
            width: 38px; height: 10px; background-color: #222;
        }
        .photo-frame {
            width: 80px; height: 110px; border: 4px solid #222; padding: 2px;
            background-color: #f0f0f0; outline: 1px solid #ccc; outline-offset: -2px;
        }
        h3 { font-family: 'Cinzel', serif; font-size: 1.4rem; margin-bottom: 5px; margin-top:0; text-transform: uppercase; }
        p { margin: 5px 0; color: #666; font-size: 0.95rem; }
    </style>
</head>
<body>

    <?php if (empty($dane)): ?>
        <div class="empty-state">
            Brak dodanych nekrologów.
        </div>
    <?php else: ?>
        <div class="obituary-grid">
            <?php foreach ($dane as $n): ?>
                <?php 
                    $cross_style = '';
                    $photo_html = '';
                    if (!empty($n['zdjecie_plik'])) {
                        $photo_html = '<img class="photo-frame" src="' . htmlspecialchars($n['zdjecie_plik']) . '" style="object-fit:cover;">';
                    } else {
                        $cross_style = 'transform: scale(1.5); margin: 30px auto;';
                    }
                ?>
                <div class="obituary-card">
                    <div class="ribbon"></div>
                    <div class="memorial-graphics">
                        <div class="css-cross" style="<?php echo $cross_style; ?>"></div>
                        <?php echo $photo_html; ?>
                    </div>
                    <h3><?php echo htmlspecialchars($n['imie_nazwisko']); ?></h3>
                    <p><?php echo htmlspecialchars($n['data_ur']); ?> - <?php echo htmlspecialchars($n['data_sm']); ?></p>
                    <p>Wiek: <?php echo htmlspecialchars($n['wiek']); ?> lat</p>
                    <p style="margin-top: 15px; font-size: 0.85rem; font-weight: bold;">Pogrzeb: <?php echo htmlspecialchars($n['data_pogrzebu']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</body>
</html>