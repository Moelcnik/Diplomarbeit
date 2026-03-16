<?php
// Sitzung starten, um auf sie zugreifen zu können
session_start();

// Alle Sitzungsvariablen löschen
$_SESSION = array();

// Falls ein Session-Cookie existiert, dieses im Browser löschen
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Die Sitzung am Server komplett zerstören
session_destroy();

// Den Benutzer sicher zurück zur Startseite leiten
header("Location: ../Website/startseite.php");
exit();
?>