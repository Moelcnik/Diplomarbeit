<?php
require_once __DIR__ . '/dbconectio.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        sender_type ENUM('user', 'admin') NOT NULL,
        message TEXT NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_read TINYINT(1) DEFAULT 0,
        INDEX idx_user_id (user_id),
        CONSTRAINT fk_chat_user_id FOREIGN KEY (user_id) REFERENCES `user`(uid) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $conn->exec($sql);
    echo "Tabelle 'chat_messages' wurde erfolgreich erstellt.";
} catch (PDOException $e) {
    echo "Fehler beim Erstellen der Tabelle: " . $e->getMessage();
}
?>
