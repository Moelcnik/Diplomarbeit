<?php
// Sitzung starten und Datenbankverbindung
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../User_Info/login.php');
    exit();
}

require_once __DIR__ . '/../Datenbank/dbconectio.php';

$success_message = '';
$error_message = '';

// Verarbeitung des Formulars
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Daten aus dem Formular sammeln
        $user_uid = $_SESSION['user_id'];
        $type = 'sonder'; // Fenstertyp für diese Seite
        $staerke = $_POST['staerke'] ?? 'keine Angabe'; // Oft nicht relevant für Sonderformen
        $lichtstufe = $_POST['lichtstufe'] ?? 'keine Angabe';
        $verglasung = $_POST['verglasung'] ?? 'keine Angabe';
        $bemessung = $_POST['bemessung'] ?? '';
        $anzahl = $_POST['anzahl'] ?? 1;
        $sonderwuensche = $_POST['sonderwuensche'] ?? '';
        $skizze_datei = null;
        
        // Skizze hochladen wenn vorhanden
        if (isset($_FILES['skizze']) && $_FILES['skizze']['error'] === UPLOAD_ERR_OK) {
            $uploads_dir = __DIR__ . '/uploads';
            
            // Uploads-Verzeichnis erstellen, falls nicht vorhanden
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }
            
            $file_tmp = $_FILES['skizze']['tmp_name'];
            $file_name = $_FILES['skizze']['name'];
            $file_size = $_FILES['skizze']['size'];
            
            // Datei validieren
            $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
            $file_type = mime_content_type($file_tmp);
            
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception('Nur PNG, JPG und PDF Dateien sind erlaubt.');
            }
            
            if ($file_size > 5000000) { // 5MB max
                throw new Exception('Datei ist zu groß. Maximum 5MB.');
            }
            
            // Datei speichern mit eindeutigem Namen
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = 'skizze_' . $user_uid . '_' . time() . '.' . $file_extension;
            $file_path = $uploads_dir . '/' . $unique_filename;
            
            if (!move_uploaded_file($file_tmp, $file_path)) {
                throw new Exception('Fehler beim Upload der Datei.');
            }
            
            $skizze_datei = 'uploads/' . $unique_filename;
        }
        
        // Daten validieren
        if (empty($bemessung) && empty($sonderwuensche)) {
            throw new Exception('Bitte geben Sie mindestens Maße oder eine Beschreibung ein.');
        }
        
        // SQL-Statement vorbereiten (Spalte `uid` verwenden, falls `user_uid` in DB nicht existiert)
        $sql = "INSERT INTO fenster_konfigurationen 
            (uid, type, staerke, lichtstufe, verglasung, bemessung, anzahl, sonderwuensche, skizze) 
            VALUES (:uid, :type, :staerke, :lichtstufe, :verglasung, :bemessung, :anzahl, :sonderwuensche, :skizze)";
        
        $stmt = $conn->prepare($sql);
        
        // Parameter binden und Statement ausführen
        $stmt->bindParam(':uid', $user_uid, PDO::PARAM_INT);
        $stmt->bindParam(':type', $type, PDO::PARAM_STR);
        $stmt->bindParam(':staerke', $staerke, PDO::PARAM_STR);
        $stmt->bindParam(':lichtstufe', $lichtstufe, PDO::PARAM_STR);
        $stmt->bindParam(':verglasung', $verglasung, PDO::PARAM_STR);
        $stmt->bindParam(':bemessung', $bemessung, PDO::PARAM_STR);
        $stmt->bindParam(':anzahl', $anzahl, PDO::PARAM_INT);
        $stmt->bindParam(':sonderwuensche', $sonderwuensche, PDO::PARAM_STR);
        $stmt->bindParam(':skizze', $skizze_datei, PDO::PARAM_STR);
        
        $stmt->execute();
        
        $success_message = 'Konfiguration erfolgreich gespeichert!';
        
    } catch (PDOException $e) {
        $error_message = 'Fehler beim Speichern in der Datenbank: ' . $e->getMessage();
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fenster Konfigurator — Sonderform</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>.container{margin-top:50px}.form-group{margin-bottom:20px}</style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Fenster Konfigurator — Sonderform</h2>
    
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
    
    <form action="konfigurator_sonderform.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="staerke">Fensterstärke (optional)</label>
            <select id="staerke" name="staerke" class="form-control">
                <option value="">Keine Angabe</option>
                <option value="68mm">68mm Standard</option>
                <option value="78mm">78mm Premium</option>
            </select>
        </div>

        <div class="form-group">
            <label for="lichtstufe">Lichtstufe (optional)</label>
            <select id="lichtstufe" name="lichtstufe" class="form-control">
                <option value="">Keine Angabe</option>
                <option value="klar">Klar</option>
                <option value="milchglas">Milchglas</option>
            </select>
        </div>

        <div class="form-group">
            <label for="verglasung">Anzahl der Verglasungen (optional)</label>
            <select id="verglasung" name="verglasung" class="form-control">
                <option value="">Keine Angabe</option>
                <option value="2">2-fach</option>
                <option value="3">3-fach</option>
            </select>
        </div>

        <div class="form-group">
            <label for="bemessung">Maße (wenn möglich)</label>
            <input type="text" id="bemessung" name="bemessung" class="form-control" placeholder="z.B. 1200x800">
        </div>

        <div class="form-group">
            <label for="anzahl">Anzahl</label>
            <input type="number" id="anzahl" name="anzahl" class="form-control" value="1" min="1" required>
        </div>

        <div class="form-group">
            <label for="skizze">Skizze hochladen (jpg/png/pdf)</label>
            <input type="file" id="skizze" name="skizze" class="form-control-file" accept=".png,.jpg,.jpeg,.pdf">
        </div>

        <div class="form-group">
            <label for="sonderwuensche">Weitere Sonderwünsche / Beschreibung</label>
            <textarea id="sonderwuensche" name="sonderwuensche" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Konfiguration speichern</button>
        <a href="fensterauswahl.php" class="btn btn-secondary">Zurück</a>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>