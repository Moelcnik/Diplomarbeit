<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../User_Info/login.php');
    exit();
}

// Neuer POST-Handler: setzt das Material in die Session, legt optional einen DB-Eintrag an und leitet weiter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_material' && isset($_POST['material'])) {
    $material = ($_POST['material'] === 'holz') ? 'holz' : 'alu';
    $_SESSION['material'] = $material;

    // Das Material wird in der Session gespeichert und bei der nächsten 
    // Konfigurationsseite mit gespeichert (siehe konfigurator.php)

    // Sicherer Redirect: nur zu fensterauswahl.php erlauben (oder zurück zur Startseite)
    $redirect = $_POST['redirect'] ?? '';
    if ($redirect && strpos($redirect, 'fensterauswahl.php') !== false) {
        header('Location: ' . $redirect);
    } else {
        header('Location: ' . $_SERVER['PHP_SELF']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurator</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .category-card {
            text-decoration: none;
            color: inherit;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .category-card:hover {
            transform: scale(1.02);
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .category-image {
            height: 200px;
            width: 100%;
        }
        .category-title {
            padding: 10px;
            margin: 0;
            text-align: center;
        }
    </style>
</head>
<body>
<?php
    // Kategorien-Array mit Titeln und direkten Links, die das Material übergeben
    $categories = [
        [
            'id' => 'fenster',
            'title' => 'Alu-Fenster',
            'link' => 'fensterauswahl.php?material=alu',
            'material' => 'alu'
        ],
        [
            'id' => 'holzfenster',
            'title' => 'Holz-Fenster',
            'link' => 'fensterauswahl.php?material=holz',
            'material' => 'holz'
        ],
        [
            'id' => 'sonsAuftraege',
            'title' => 'Sonstige Aufträge',
            'link' => '#',
            'material' => null
        ]
    ];
?>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Produktkonfigurator</h1>

        <div class="category-grid">
            <?php foreach($categories as $category): 
                // data-material setzen (null bleibt leer) und href als Fallback
                $dataMat = $category['material'] ? htmlspecialchars($category['material']) : '';
            ?>
                <a href="<?php echo $category['link']; ?>" class="category-card js-material-link" data-material="<?php echo $dataMat; ?>">
                    <div class="category-image" style="display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                        <span class="h3"><?php echo $category['title']; ?></span>
                    </div>
                    <h2 class="category-title"><?php echo $category['title']; ?></h2>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    // Klicks auf Karten abfangen, Material per POST an diese Seite senden (verstecktes Formular),
    // der Server setzt die Session und leitet dann direkt zur Zielseite weiter.
    (function () {
        document.addEventListener('click', function (e) {
            var el = e.target;
            while (el && !el.classList.contains('js-material-link')) {
                el = el.parentElement;
            }
            if (!el) return;
            var material = el.getAttribute('data-material');
            var href = el.getAttribute('href') || '#';
            if (!material) return; // kein Material => normale Navigation
            e.preventDefault();

            // Erzeuge ein unsichtbares Formular und sende es per POST an diese Seite.
            var form = document.createElement('form');
            form.style.display = 'none';
            form.method = 'post';
            form.action = window.location.href;

            var inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'select_material';
            form.appendChild(inputAction);

            var inputMaterial = document.createElement('input');
            inputMaterial.type = 'hidden';
            inputMaterial.name = 'material';
            inputMaterial.value = material;
            form.appendChild(inputMaterial);

            var inputRedirect = document.createElement('input');
            inputRedirect.type = 'hidden';
            inputRedirect.name = 'redirect';
            inputRedirect.value = href;
            form.appendChild(inputRedirect);

            document.body.appendChild(form);
            form.submit();
        }, false);
    })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>