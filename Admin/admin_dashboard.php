<?php
// Sitzung starten und Admin-Authentifizierung
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../User_Info/login.php');
    exit();
}

require_once __DIR__ . '/../Datenbank/dbconectio.php';

$user_uid = $_SESSION['user_id'];

// Überprüfe ob Benutzer Admin ist
try {
    $stmt = $conn->prepare("SELECT uid, vorname, nachname, admin FROM user WHERE uid = :uid LIMIT 1");
    $stmt->bindParam(':uid', $user_uid, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch();
    
    // Admin-Check: Benutzer muss Admin sein (admin = 'ja' oder uid = 3)
    if (!$user || ($user['admin'] !== 'ja' && $user['uid'] != 3)) {
        header('Location: ../Website/startseite.php');
        exit();
    }
} catch (PDOException $e) {
    die('Fehler: ' . $e->getMessage());
}

$success_message = '';
$error_message = '';
$konfigurationen = [];
$selected_config = null;

// Alle Konfigurationen abrufen
try {
    $sql = "SELECT fc.fid, fc.uid, fc.type, fc.staerke, fc.lichtstufe, fc.verglasung, 
                   fc.bemessung, fc.anzahl, fc.sonderwuensche, fc.material, fc.skizze, 
                   fc.erstelldatum, u.email, u.vorname, u.nachname
            FROM fenster_konfigurationen fc
            LEFT JOIN user u ON fc.uid = u.uid
            ORDER BY fc.erstelldatum DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $konfigurationen = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Fehler beim Abrufen der Konfigurationen: ' . $e->getMessage();
}

// Status aktualisieren wenn gesendet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    try {
        $config_id = intval($_POST['config_id'] ?? 0);
        $new_status = $_POST['status'] ?? 'neu';
        $notes = $_POST['notes'] ?? '';
        
        // Überprüfe ob Spalten existieren, sonst erstelle sie
        try {
            $sql_check = "DESCRIBE fenster_konfigurationen";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute();
            $columns = $stmt_check->fetchAll();
            $column_names = array_column($columns, 'Field');
            
            // Status-Spalte hinzufügen wenn nicht vorhanden
            if (!in_array('status', $column_names)) {
                $conn->exec("ALTER TABLE fenster_konfigurationen ADD COLUMN status VARCHAR(50) DEFAULT 'neu'");
            }
            
            // Notes-Spalte hinzufügen wenn nicht vorhanden
            if (!in_array('notes', $column_names)) {
                $conn->exec("ALTER TABLE fenster_konfigurationen ADD COLUMN notes TEXT");
            }
        } catch (Exception $e) {
            // Spalten existieren bereits
        }
        
        $sql = "UPDATE fenster_konfigurationen SET status = :status, notes = :notes WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);
        $stmt->bindParam(':id', $config_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $success_message = 'Status erfolgreich aktualisiert!';
        
        // Seite neu laden um aktualisierte Daten zu zeigen
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
        
    } catch (Exception $e) {
        $error_message = 'Fehler beim Aktualisieren des Status: ' . $e->getMessage();
    }
}

// Konfiguration laden wenn einzeln angezeigt
if (isset($_GET['id'])) {
    $config_id = intval($_GET['id']);
    foreach ($konfigurationen as $config) {
        if ($config['fid'] == $config_id) {
            $selected_config = $config;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #ddd;
            min-height: 100vh;
        }
        .main-content {
            padding: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85em;
        }
        .status-neu {
            background-color: #ffc107;
            color: #000;
        }
        .status-bearbeitet {
            background-color: #17a2b8;
            color: white;
        }
        .status-abgeschlossen {
            background-color: #28a745;
            color: white;
        }
        .status-abgelehnt {
            background-color: #dc3545;
            color: white;
        }
        .config-list-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .config-list-item:hover {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .config-list-item.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .detail-view {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .detail-item {
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border-left: 4px solid #007bff;
        }
        .detail-item label {
            font-weight: bold;
            color: #666;
            display: block;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .detail-item span {
            color: #333;
        }
        .form-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 2px solid #007bff;
        }
        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 sidebar">
            <h4 class="mb-4">Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="admin_dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../Website/startseite.php">Startseite</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../User_Info/logout.php">Logout</a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 main-content">
            <h1 class="mb-4">Admin Dashboard - Fenster Konfigurationen</h1>

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

            <?php if (empty($konfigurationen)): ?>
                <div class="alert alert-info">
                    Keine Konfigurationen vorhanden.
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Liste der Konfigurationen -->
                    <div class="col-md-6">
                        <h5>Alle Aufträge (<?php echo count($konfigurationen); ?>)</h5>
                        <div style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($konfigurationen as $config): ?>
                                <a href="?id=<?php echo $config['id']; ?>" class="config-list-item <?php echo ($selected_config && $selected_config['id'] == $config['id']) ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div>
                                            <strong><?php echo htmlspecialchars($config['username'] ?? 'Unbekannt'); ?></strong><br>
                                            <small><?php 
                                                $type_labels = ['rechteck' => 'Rechteckig', 'rund' => 'Rund', 'sonder' => 'Sonderform'];
                                                echo $type_labels[$config['type']] ?? $config['type'];
                                            ?></small><br>
                                            <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($config['erstelldatum'])); ?></small>
                                        </div>
                                        <span class="status-badge status-<?php echo strtolower($config['status'] ?? 'neu'); ?>">
                                            <?php echo htmlspecialchars($config['status'] ?? 'neu'); ?>
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Detail-Ansicht -->
                    <div class="col-md-6">
                        <?php if ($selected_config): ?>
                            <h5>Auftrags-Details</h5>
                            <div class="detail-view">
                                <div class="detail-row">
                                    <div class="detail-item">
                                        <label>Kunde:</label>
                                        <span><?php echo htmlspecialchars($selected_config['username'] ?? 'Unbekannt'); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>E-Mail:</label>
                                        <span><?php echo htmlspecialchars($selected_config['email'] ?? 'Nicht vorhanden'); ?></span>
                                    </div>
                                </div>

                                <div class="detail-row">
                                    <div class="detail-item">
                                        <label>Fenstertyp:</label>
                                        <span><?php 
                                            $type_labels = ['rechteck' => 'Rechteckig', 'rund' => 'Rund', 'sonder' => 'Sonderform'];
                                            echo $type_labels[$selected_config['type']] ?? $selected_config['type'];
                                        ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Material:</label>
                                        <span><?php echo htmlspecialchars($selected_config['material'] ?? 'Nicht angegeben'); ?></span>
                                    </div>
                                </div>

                                <div class="detail-row">
                                    <div class="detail-item">
                                        <label>Fensterstärke:</label>
                                        <span><?php echo htmlspecialchars($selected_config['staerke']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Lichtstufe:</label>
                                        <span><?php echo htmlspecialchars($selected_config['lichtstufe']); ?></span>
                                    </div>
                                </div>

                                <div class="detail-row">
                                    <div class="detail-item">
                                        <label>Verglasung:</label>
                                        <span><?php echo htmlspecialchars($selected_config['verglasung']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Maße (BxH):</label>
                                        <span><?php echo htmlspecialchars($selected_config['bemessung']); ?></span>
                                    </div>
                                </div>

                                <div class="detail-row">
                                    <div class="detail-item">
                                        <label>Anzahl:</label>
                                        <span><?php echo intval($selected_config['anzahl']); ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <label>Erstellt am:</label>
                                        <span><?php echo date('d.m.Y H:i', strtotime($selected_config['erstelldatum'])); ?></span>
                                    </div>
                                </div>

                                <?php if (!empty($selected_config['sonderwuensche'])): ?>
                                    <div class="detail-item">
                                        <label>Sonderwünsche:</label>
                                        <span><?php echo nl2br(htmlspecialchars($selected_config['sonderwuensche'])); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($selected_config['skizze'])): ?>
                                    <div class="detail-item">
                                        <label>Skizze:</label>
                                        <span><a href="<?php echo htmlspecialchars($selected_config['skizze']); ?>" target="_blank" class="btn btn-sm btn-info">Anzeigen</a></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Status Bearbeitung -->
                                <div class="form-section">
                                    <h6>Status bearbeiten</h6>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="config_id" value="<?php echo $selected_config['fid']; ?>">

                                        <div class="form-group">
                                            <label for="status">Status:</label>
                                            <select id="status" name="status" class="form-control" required>
                                                <option value="neu" <?php echo ($selected_config['status'] ?? 'neu') === 'neu' ? 'selected' : ''; ?>>Neu</option>
                                                <option value="bearbeitet" <?php echo ($selected_config['status'] ?? 'neu') === 'bearbeitet' ? 'selected' : ''; ?>>In Bearbeitung</option>
                                                <option value="abgeschlossen" <?php echo ($selected_config['status'] ?? 'neu') === 'abgeschlossen' ? 'selected' : ''; ?>>Abgeschlossen</option>
                                                <option value="abgelehnt" <?php echo ($selected_config['status'] ?? 'neu') === 'abgelehnt' ? 'selected' : ''; ?>>Abgelehnt</option>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="notes">Notizen:</label>
                                            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Interne Notizen..."><?php echo htmlspecialchars($selected_config['notes'] ?? ''); ?></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary">Status speichern</button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Wählen Sie einen Auftrag aus der Liste, um die Details anzuzeigen.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
