<?php
require_once('./Datenbank/dbconectio.php');

try {
    // Überprüfe ob die Tabelle existiert
    $tables_result = $conn->query("SHOW TABLES");
    $tables = $tables_result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Vorhandene Tabellen:</h2>";
    echo "<pre>";
    print_r($tables);
    echo "</pre>";
    
    // Überprüfe die Struktur der fenster_konfigurationen Tabelle
    if (in_array('fenster_konfigurationen', $tables)) {
        echo "<h2>Struktur der fenster_konfigurationen Tabelle:</h2>";
        $columns = $conn->query("DESCRIBE fenster_konfigurationen")->fetchAll();
        echo "<table border='1' style='padding: 10px; margin: 10px;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            foreach ($col as $val) {
                echo "<td>" . $val . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h2 style='color: red;'>Tabelle 'fenster_konfigurationen' existiert NICHT!</h2>";
        echo "<p>Erstelle die Tabelle...</p>";
        
        $sql = "CREATE TABLE IF NOT EXISTS fenster_konfigurationen (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_uid INT DEFAULT NULL,
            type ENUM('rechteck','rund','sonder') NOT NULL,
            staerke VARCHAR(50) NOT NULL,
            lichtstufe VARCHAR(50) NOT NULL,
            verglasung VARCHAR(20) NOT NULL,
            bemessung VARCHAR(255) NOT NULL,
            anzahl INT NOT NULL DEFAULT 1,
            sonderwuensche TEXT,
            material VARCHAR(50) DEFAULT NULL,
            skizze VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) DEFAULT 'neu',
            notes TEXT,
            erstelldatum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_uid (user_uid),
            CONSTRAINT fk_fk_user_uid FOREIGN KEY (user_uid) REFERENCES `user`(uid) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        $conn->exec($sql);
        echo "<div style='color: green; margin: 10px;'>✓ Tabelle erstellt!</div>";
    }
    
} catch (PDOException $e) {
    echo "Datenbankfehler: " . $e->getMessage();
}
?>
