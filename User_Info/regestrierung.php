<?php
session_start();
require_once('../Datenbank/dbconectio.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $anrede = $_POST['anrede'];
    $vorname = $_POST['vorname'];
    $nachname = $_POST['nachname'];
    $email = $_POST['email'];
    $adresse = $_POST['adresse'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $admin = 'nein'; // Standard-Wert für neue Benutzer

    try {
        // Überprüfe, ob E-Mail bereits existiert
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Diese E-Mail-Adresse ist bereits registriert.";
        } else {
            // Füge neuen Benutzer hinzu
            $sql = "INSERT INTO user (anrede, vorname, nachname, email, adresse, password, admin) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$anrede, $vorname, $nachname, $email, $adresse, $password, $admin]);
            
            header("Location: login.php");
            exit();
        }
    } catch(PDOException $e) {
        $error = "Registrierungsfehler: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Registrierung</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="anrede">Anrede</label>
                                <select class="form-control" id="anrede" name="anrede" required>
                                    <option value="Herr">Herr</option>
                                    <option value="Frau">Frau</option>
                                    <option value="Divers">Divers</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="vorname">Vorname</label>
                                <input type="text" class="form-control" id="vorname" name="vorname" required>
                            </div>

                            <div class="form-group">
                                <label for="nachname">Nachname</label>
                                <input type="text" class="form-control" id="nachname" name="nachname" required>
                            </div>

                            <div class="form-group">
                                <label for="email">E-Mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label for="adresse">Adresse</label>
                                <input type="text" class="form-control" id="adresse" name="adresse" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Passwort</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block">Registrieren</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="login.php">Bereits registriert? Hier anmelden</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>