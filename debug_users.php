<?php
require_once('./Datenbank/dbconectio.php');

try {
    $stmt = $conn->prepare("SELECT uid, email, admin, password FROM user");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "<h2>Alle Benutzer in der Datenbank:</h2>";
    echo "<table border='1' style='padding: 10px; margin: 10px;'>";
    echo "<tr><th>UID</th><th>Email</th><th>Admin</th><th>Passwort Hash</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['uid'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['admin'] . "</td>";
        echo "<td>" . substr($user['password'], 0, 30) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Admin-Benutzer erstellen/aktualisieren:</h2>";
    echo "<form method='POST'>";
    echo "<label>Email: <input type='email' name='email' value='admin@gmail.com'></label><br>";
    echo "<label>Passwort: <input type='password' name='password' value='admin123'></label><br>";
    echo "<button type='submit' name='create_admin'>Admin erstellen</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_admin'])) {
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // Überprüfen ob Benutzer existiert
        $check = $conn->prepare("SELECT uid FROM user WHERE email = ?");
        $check->execute([$email]);
        
        if ($check->rowCount() > 0) {
            // Update existing
            $update = $conn->prepare("UPDATE user SET password = ?, admin = 'ja' WHERE email = ?");
            $update->execute([$password, $email]);
            echo "<div style='color: green; margin: 10px;'>✓ Admin-Benutzer aktualisiert!</div>";
        } else {
            // Create new
            $insert = $conn->prepare("INSERT INTO user (anrede, vorname, nachname, email, adresse, password, admin) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert->execute(['Herr', 'Admin', 'Benutzer', $email, 'Admin-Adresse', $password, 'ja']);
            echo "<div style='color: green; margin: 10px;'>✓ Neuer Admin-Benutzer erstellt!</div>";
        }
        
        // Passwort-Test
        echo "<h3>Passwort-Verifikation:</h3>";
        if (password_verify($_POST['password'], $password)) {
            echo "<div style='color: green;'>✓ Passwort ist korrekt!</div>";
        } else {
            echo "<div style='color: red;'>✗ Passwort-Verifikation fehlgeschlagen!</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "Datenbankfehler: " . $e->getMessage();
}
?>
