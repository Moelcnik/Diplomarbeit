<?php
// ...existing code...
require_once __DIR__ . '/dbconectio.php';

try {
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
        skizze VARCHAR(255) DEFAULT NULL,
        erstelldatum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_uid (user_uid),
        CONSTRAINT fk_fk_user_uid FOREIGN KEY (user_uid) REFERENCES `user`(uid) ON DELETE SET NULL ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->exec($sql);
    echo "Tabelle 'fenster_konfigurationen' wurde erstellt (oder existiert bereits).";
} catch (PDOException $e) {
    echo "Fehler beim Erstellen der Tabelle: " . $e->getMessage();
}

?>