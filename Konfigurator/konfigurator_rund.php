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
        $type = 'rund'; // Fenstertyp für diese Seite
        $staerke = $_POST['staerke'] ?? '';
        $lichtstufe = $_POST['lichtstufe'] ?? '';
        $verglasung = $_POST['verglasung'] ?? '';
        $bemessung = $_POST['bemessung'] ?? '';
        $anzahl = $_POST['anzahl'] ?? 1;
        $sonderwuensche = $_POST['sonderwuensche'] ?? '';
        
        // Daten validieren
        if (empty($staerke) || empty($lichtstufe) || empty($verglasung) || empty($bemessung)) {
            throw new Exception('Bitte alle erforderlichen Felder ausfüllen.');
        }
        
        // SQL-Statement vorbereiten (Spalte `uid` verwenden, falls `user_uid` in DB nicht existiert)
        $sql = "INSERT INTO fenster_konfigurationen 
            (uid, type, staerke, lichtstufe, verglasung, bemessung, anzahl, sonderwuensche) 
            VALUES (:uid, :type, :staerke, :lichtstufe, :verglasung, :bemessung, :anzahl, :sonderwuensche)";
        
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
    <title>Fenster Konfigurator — Rund</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>.container{margin-top:50px}.form-group{margin-bottom:20px}</style>
</head>
<body>
<div class="container">
    <h2 class="mb-4">Fenster Konfigurator — Rund</h2>
    
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
    
    <form action="konfigurator_rund.php" method="POST">
        <div class="form-group">
            <label for="staerke">Fensterstärke</label>
            <select id="staerke" name="staerke" class="form-control" required>
                <option value="">Bitte wählen</option>
                <option value="68mm">68mm Standard</option>
                <option value="78mm">78mm Premium</option>
            </select>
        </div>

        <div class="form-group">
            <label for="lichtstufe">Lichtstufe</label>
            <select id="lichtstufe" name="lichtstufe" class="form-control" required>
                <option value="">Bitte wählen</option>
                <option value="klar">Klar</option>
                <option value="milchglas">Milchglas</option>
            </select>
        </div>

        <div class="form-group">
            <label for="verglasung">Anzahl der Verglasungen</label>
            <select id="verglasung" name="verglasung" class="form-control" required>
                <option value="">Bitte wählen</option>
                <option value="2">2-fach</option>
                <option value="3">3-fach</option>
            </select>
        </div>

        <div class="form-group">
            <label for="bemessung">Durchmesser (Ø in mm)</label>
            <input type="text" id="bemessung" name="bemessung" class="form-control" placeholder="z.B. Ø1000" required>
        </div>

        <div class="form-group">
            <label for="anzahl">Anzahl</label>
            <input type="number" id="anzahl" name="anzahl" class="form-control" value="1" min="1" required>
        </div>

        <div class="form-group">
            <label for="sonderwuensche">Sonderwünsche</label>
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
