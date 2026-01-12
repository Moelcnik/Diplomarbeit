<?php
// Sitzung starten und Datenbankverbindung
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../User_Info/login.php');
    exit();
}

require_once __DIR__ . '/../Datenbank/dbconectio.php';

$user_uid = $_SESSION['user_id'];
$user_email = '';
$success_message = '';
$error_message = '';
$konfigurationen = [];

// Benutzer-E-Mail abrufen
try {
    $sql_user = "SELECT email FROM user WHERE uid = :uid LIMIT 1";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bindParam(':uid', $user_uid, PDO::PARAM_INT);
    $stmt_user->execute();
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        $user_email = $user_data['email'];
    }
} catch (PDOException $e) {
    $error_message = 'Fehler beim Abrufen der Benutzerdaten: ' . $e->getMessage();
}

// Alle Konfigurationen des Benutzers abrufen
try {
    $sql = "SELECT type, staerke, lichtstufe, verglasung, bemessung, anzahl, sonderwuensche, material, skizze, erstelldatum 
            FROM fenster_konfigurationen 
            WHERE uid = :uid 
            ORDER BY erstelldatum DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':uid', $user_uid, PDO::PARAM_INT);
    $stmt->execute();
    $konfigurationen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = 'Fehler beim Abrufen der Konfigurationen: ' . $e->getMessage();
}

// E-Mail versenden
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    try {
        // E-Mail-Inhalt generieren
        $email_subject = 'Neue Fenster-Bestellung';
        $email_body = "Neue Bestellung vom Benutzer (UID: " . $user_uid . ")\n";
        $email_body .= "E-Mail des Kunden: " . $user_email . "\n\n";
        $email_body .= "========================================\n\n";
        $email_body .= "Übersicht aller Fenster-Konfigurationen:\n\n";
        $email_body .= "========================================\n\n";
        
        if (count($konfigurationen) === 0) {
            $email_body .= "Der Benutzer hat noch keine Konfigurationen erstellt.\n\n";
        } else {
            foreach ($konfigurationen as $index => $config) {
                $email_body .= "Konfiguration #" . ($index + 1) . "\n";
                $email_body .= "-----------------------------------------\n";
                $email_body .= "Fenstertyp: " . htmlspecialchars($config['type']) . "\n";
                $email_body .= "Material: " . (empty($config['material']) ? 'Nicht angegeben' : htmlspecialchars($config['material'])) . "\n";
                $email_body .= "Fensterstärke: " . htmlspecialchars($config['staerke']) . "\n";
                $email_body .= "Lichtstufe: " . htmlspecialchars($config['lichtstufe']) . "\n";
                $email_body .= "Verglasung: " . htmlspecialchars($config['verglasung']) . "\n";
                $email_body .= "Maße (BxH): " . htmlspecialchars($config['bemessung']) . "\n";
                $email_body .= "Anzahl: " . intval($config['anzahl']) . "\n";
                
                if (!empty($config['sonderwuensche'])) {
                    $email_body .= "Sonderwünsche: " . htmlspecialchars($config['sonderwuensche']) . "\n";
                }
                
                if (!empty($config['skizze'])) {
                    $email_body .= "Skizze: " . htmlspecialchars($config['skizze']) . "\n";
                }
                
                $email_body .= "Erstellt am: " . $config['erstelldatum'] . "\n";
                $email_body .= "-----------------------------------------\n\n";
            }
        }
        
        $email_body .= "========================================\n\n";
        
        // E-Mail-Header
        $headers = "From: " . $user_email . "\r\n";
        $headers .= "Reply-To: " . $user_email . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // E-Mail an Firmenmail versenden
        $shop_email = 'moelcnikdomi@gmail.com';
        
        if (mail($shop_email, $email_subject, $email_body, $headers)) {
            $success_message = 'Ihre Konfigurationen wurden erfolgreich versendet!';
        } else {
            throw new Exception('Fehler beim Versenden der E-Mail. Bitte versuchen Sie es später erneut.');
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurationen Übersicht</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container {
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .config-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
        .config-header {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .config-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 10px;
        }
        .config-item {
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border-left: 4px solid #007bff;
            padding-left: 15px;
        }
        .config-item label {
            font-weight: bold;
            color: #666;
            display: block;
            font-size: 0.9em;
            margin-bottom: 3px;
        }
        .config-item span {
            color: #333;
            font-size: 1.1em;
        }
        .no-configs {
            text-align: center;
            padding: 40px 20px;
            background-color: #e9ecef;
            border-radius: 8px;
            color: #666;
        }
        .email-form {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            border: 2px solid #007bff;
        }
        .btn-group-custom {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .btn-group-custom .btn {
            flex: 1;
            min-width: 150px;
        }
        @media (max-width: 768px) {
            .config-row {
                grid-template-columns: 1fr;
            }
            .btn-group-custom {
                flex-direction: column;
            }
            .btn-group-custom .btn {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="mb-4">Meine Fenster-Konfigurationen</h1>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Konfigurationen anzeigen -->
    <?php if (count($konfigurationen) === 0): ?>
        <div class="no-configs">
            <h3>Keine Konfigurationen vorhanden</h3>
            <p>Sie haben noch keine Fenster-Konfigurationen erstellt.</p>
            <a href="kgstart.php" class="btn btn-primary mt-3">Zur Konfiguration</a>
        </div>
    <?php else: ?>
        <div class="mb-4">
            <p class="text-muted">Sie haben <strong><?php echo count($konfigurationen); ?></strong> Konfiguration<?php echo count($konfigurationen) !== 1 ? 'en' : ''; ?>.</p>
        </div>

        <?php foreach ($konfigurationen as $index => $config): ?>
            <div class="config-card">
                <div class="config-header">
                    Konfiguration #<?php echo $index + 1; ?> - 
                    <?php 
                        $type_labels = [
                            'rechteck' => 'Rechteckig',
                            'rund' => 'Rund',
                            'sonder' => 'Sonderform'
                        ];
                        echo isset($type_labels[$config['type']]) ? $type_labels[$config['type']] : htmlspecialchars($config['type']);
                    ?>
                </div>

                <div class="config-row">
                    <div class="config-item">
                        <label>Material:</label>
                        <span><?php echo empty($config['material']) ? '<em>Nicht angegeben</em>' : htmlspecialchars(ucfirst($config['material'])); ?></span>
                    </div>
                    <div class="config-item">
                        <label>Fensterstärke:</label>
                        <span><?php echo htmlspecialchars($config['staerke']); ?></span>
                    </div>
                </div>

                <div class="config-row">
                    <div class="config-item">
                        <label>Lichtstufe:</label>
                        <span><?php echo htmlspecialchars($config['lichtstufe']); ?></span>
                    </div>
                    <div class="config-item">
                        <label>Verglasung:</label>
                        <span><?php echo htmlspecialchars($config['verglasung']); ?></span>
                    </div>
                </div>

                <div class="config-row">
                    <div class="config-item">
                        <label>Maße (BxH):</label>
                        <span><?php echo htmlspecialchars($config['bemessung']); ?></span>
                    </div>
                    <div class="config-item">
                        <label>Anzahl:</label>
                        <span><?php echo intval($config['anzahl']); ?></span>
                    </div>
                </div>

                <?php if (!empty($config['sonderwuensche'])): ?>
                    <div class="config-item">
                        <label>Sonderwünsche:</label>
                        <span><?php echo nl2br(htmlspecialchars($config['sonderwuensche'])); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($config['skizze'])): ?>
                    <div class="config-item">
                        <label>Skizze:</label>
                        <span><a href="<?php echo htmlspecialchars($config['skizze']); ?>" target="_blank" class="btn btn-sm btn-info">Skizze anzeigen</a></span>
                    </div>
                <?php endif; ?>

                <div class="config-item">
                    <label>Erstellt am:</label>
                    <span><?php echo date('d.m.Y H:i', strtotime($config['erstelldatum'])); ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- E-Mail Formular -->
        <div class="email-form">
            <h3 class="mb-3">Konfigurationen per E-Mail versenden</h3>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                <div class="form-group">
             p>Die Konfigurationen werden an Ihre registrierte E-Mail-Adresse (<strong><?php echo htmlspecialchars($user_email); ?></strong>) versendet.</p>
            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                <input type="hidden" name="action" value="send_email">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-envelope"></i> Jetzt
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <div class="btn-group-custom mt-4">
        <a href="kgstart.php" class="btn btn-primary">Neue Konfiguration</a>
        <a href="../Website/startseite.php" class="btn btn-secondary">Zurück zur Startseite</a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
