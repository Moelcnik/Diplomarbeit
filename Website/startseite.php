<?php
// FTS Podesser Website – Responsive Version
session_start(); // Session starten, um den Login-Status prüfen zu können

// Wir prüfen, ob der Benutzer angemeldet ist
$is_logged_in = isset($_SESSION['user_id']);
$user_email = $is_logged_in ? $_SESSION['email'] : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FTS Podesser - Fenster • Türen • Sonnenschutz</title>
  
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="../Style/chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
  
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
  
  <style>
    /* Kleiner Fix, damit Karten wie Buttons wirken */
    .clickable-card {
        cursor: pointer;
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">FTS Podesser</div>
    
    <nav>
      <a href="../Konfigurator/fensterauswahl.php">Leistungen</a>
      <a href="#ueber-uns">Über uns</a>
      <a href="../Website/impressum.php">Impressum</a>
      <a href="#karte">Standort</a>
    </nav>
    
    <div class="header-actions">
      <?php if ($is_logged_in): ?>
        <span class="user-greeting">Hallo, <?php echo htmlspecialchars($user_email); ?></span>
        <a href="../User_Info/logout.php" class="action-btn action-btn-register">Logout</a>
      <?php else: ?>
        <a href="../User_Info/login.php" class="action-btn action-btn-login">Login</a>
        <a href="../User_Info/regestrierung.php" class="action-btn action-btn-register">Registrieren</a>
      <?php endif; ?>
    </div>
  </header>

  <section class="hero">
    <h1>Fenster • Türen • Sonnenschutz</h1>
    <p>Qualität, Präzision und Design – direkt von FTS Podesser.</p>
    <button onclick="location.href='#kontakt'">Jetzt beraten lassen</button>
  </section>

  <section id="leistungen">
    <h2>Unsere Leistungen</h2>
    <div class="leistungen">
      <div class='card clickable-card' onclick="location.href='../Konfigurator/fensterauswahl.php'">
        <h3>Fenster</h3>
        <p>Moderne, energieeffiziente Fensterlösungen – individuell geplant und professionell montiert. <br><strong>Hier zum Konfigurator</strong></p>
      </div>
      <div class='card'>
        <h3>Türen</h3>
        <p>Sichere und stilvolle Türen, die zu Ihrem Zuhause und Ihrem Anspruch passen.</p>
      </div>
      <div class='card'>
        <h3>Sonnenschutz</h3>
        <p>Innovative Systeme für mehr Komfort, Schutz und Energieeffizienz – von Rollläden bis Raffstores.</p>
      </div>
    </div>
  </section>

  <section id="ueber-uns">
    <h2>Über uns</h2>
    <p>
      Willkommen bei <strong>FTS Podesser</strong>, Ihrem verlässlichen Fachbetrieb für hochwertige
      <strong>Fenster, Türen und Sonnenschutzsysteme</strong>.  
      Wir stehen für Qualität, Präzision und persönliche Beratung – vom ersten Gespräch bis zur fachgerechten Montage.
      <br><br>
      Unser Ziel ist es, Wohn- und Arbeitsräume nicht nur funktional, sondern auch ästhetisch und energieeffizient zu gestalten.
      Dabei setzen wir auf moderne Materialien, innovative Technik und echtes Handwerk.
      <br><br>
      Mit jahrelanger Erfahrung und einem engagierten Team bietet <strong>FTS Podesser</strong> maßgeschneiderte Lösungen,
      die höchsten Ansprüchen gerecht werden.  
      Neben Fenstern und Türen umfasst unser Sortiment ein breites Angebot an <strong>Sonnenschutzsystemen</strong> –
      für mehr Komfort, Sicherheit und Nachhaltigkeit in Ihrem Zuhause.
      <br><br>
      Vertrauen Sie auf <strong>FTS Podesser</strong> – Qualität aus einer Hand, zuverlässig und termingerecht umgesetzt.
    </p>
  </section>

  <section id="kontakt" class="kontaktbereich">
    <h2>Kontakt</h2>
    <div class="kontakt">
      <div>
        <strong>Telefon:</strong>
        <p>+43 123 456 789</p>
      </div>
      <div>
        <strong>Email:</strong>
        <p>office@podesser.at</p>
      </div>
      <div>
        <strong>Adresse:</strong>
        <p>Hauptstraße 45, 9813 Möllbrücke</p>
      </div>
    </div>
  </section>

  <section id="karte">
    <h2>Standort</h2>
    <div id="map" style="height: 400px; width: 100%; max-width: 1000px; margin: 0 auto; border-radius: 8px;"></div>
  </section>

  <footer>
    <p>© <?php echo date("Y"); ?> FTS Podesser – Fenster • Türen • Sonnenschutz</p>
  </footer>
  
  <!-- Live Chat Widget -->
  <div id="chat-widget">
      <div id="chat-button" onclick="toggleChat()">
          <i class="fas fa-comments fa-2x"></i>
          <div class="chat-notification"></div>
      </div>
      <div id="chat-window">
          <div id="chat-header">
              <span>FTS Podesser Support</span>
              <i class="fas fa-times" onclick="toggleChat()" style="cursor:pointer"></i>
          </div>
          <div id="chat-messages">
              <?php if ($is_logged_in): ?>
                  <div class="message message-admin">Hallo! Wie können wir Ihnen helfen?</div>
              <?php else: ?>
                  <div class="message message-admin">Bitte melden Sie sich an, um den Live-Chat zu nutzen.</div>
                  <a href="../User_Info/login.php" class="btn btn-primary btn-sm ml-3 mt-2" style="width: auto; padding: 5px 10px;">Zum Login</a>
              <?php endif; ?>
          </div>
          <?php if ($is_logged_in): ?>
          <div id="chat-input-area">
              <input type="text" id="chat-input" placeholder="Nachricht schreiben...">
              <button id="chat-send" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
          </div>
          <?php endif; ?>
      </div>
  </div>

  <script src="chat.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var mapElement = document.getElementById('map');
      if (!mapElement) return;

      var fallbackLatLng = [46.8385, 13.3898];
      var address = 'Hauptstraße 45, 9813 Möllbrücke, Österreich';

      var map = L.map('map', { scrollWheelZoom: false }).setView(fallbackLatLng, 15);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap-Mitwirkende'
      }).addTo(map);

      var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=' + encodeURIComponent(address);
      fetch(url, { headers: { 'Accept-Language': 'de' } })
        .then(function (res) { return res.json(); })
        .then(function (results) {
          if (results && results.length > 0) {
            var lat = parseFloat(results[0].lat);
            var lon = parseFloat(results[0].lon);
            var display = results[0].display_name || address;
            var latLng = [lat, lon];
            map.setView(latLng, 17);
            L.marker(latLng).addTo(map)
              .bindPopup('<strong>FTS Podesser</strong><br>' + display)
              .openPopup();
          } else {
            L.marker(fallbackLatLng).addTo(map)
              .bindPopup('<strong>FTS Podesser</strong><br>' + address)
              .openPopup();
          }
        })
        .catch(function () {
          L.marker(fallbackLatLng).addTo(map)
            .bindPopup('<strong>FTS Podesser</strong><br>' + address)
            .openPopup();
        });
    });
  </script>
</body>
</html>