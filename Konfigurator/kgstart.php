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

    // --- DB: Bestellung/Eintrag mit Material speichern (fehlertolerant) ---
    // Passen Sie diese Werte an Ihre Umgebung an:
    $dbHost = 'localhost';
    $dbUser = 'db_user';
    $dbPass = 'db_password';
    $dbName = 'db_name';
    $tableName = 'bestellungen'; // <-- ersetze durch Ihren Tabellennamen

    // Optional: user_id aus Session (falls vorhanden) speichern
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    // Flag ob Insert erfolgreich war (nur für Logging, nicht zwingend erforderlich)
    $dbSaved = false;

    // 1) Versuche mysqli (wenn installiert)
    if (extension_loaded('mysqli')) {
        $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
        if ($mysqli && $mysqli->connect_errno === 0) {
            $sql = "INSERT INTO `$tableName` (material, user_id, created_at) VALUES (?, ?, NOW())";
            if ($stmt = $mysqli->prepare($sql)) {
                // user_id kann NULL sein; binde als integer (wenn NULL, binde 0 oder handle in DB)
                $stmt->bind_param('si', $material, $userId);
                $stmt->execute();
                $stmt->close();
                $dbSaved = true;
            } else {
                error_log("kgstart.php: mysqli prepare failed: " . $mysqli->error);
            }
            $mysqli->close();
        } else {
            error_log("kgstart.php: mysqli connect failed: " . ($mysqli->connect_error ?? 'unknown'));
        }

    // 2) Falls mysqli nicht vorhanden, versuche PDO (pdo_mysql)
    } elseif (extension_loaded('pdo_mysql')) {
        try {
            $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $stmt = $pdo->prepare("INSERT INTO `$tableName` (material, user_id, created_at) VALUES (:material, :user_id, NOW())");
            $stmt->execute([':material' => $material, ':user_id' => $userId]);
            $dbSaved = true;
        } catch (Exception $e) {
            error_log("kgstart.php: PDO error: " . $e->getMessage());
        }

    // 3) Keine MySQL-Erweiterung verfügbar — protokollieren und weitermachen
    } else {
        error_log("kgstart.php: Keine MySQL-Erweiterung installiert (mysqli oder pdo_mysql). DB-Insert übersprungen.");
    }
    // --- Ende DB ---

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