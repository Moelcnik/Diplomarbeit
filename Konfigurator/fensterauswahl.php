
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fenster Auswahl</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .window-category {
            margin-bottom: 50px;
        }
        .window-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .window-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .window-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .window-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .country-section {
            padding: 20px;
            margin-bottom: 30px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Österreichische Fenster -->
        <div class="country-section">
            <h2 class="mb-4">Österreichische Fenster</h2>
            <div class="window-grid">
                <div class="window-card" onclick="location.href='konfigurator.php'">
                    <img src="images/placeholder_rechteck.jpg" alt="Rechteckiges Fenster" class="window-image">
                    <h3>Rechteckig</h3>
                </div>
                <div class="window-card" onclick="location.href='konfigurator_rund.php'">
                    <img src="images/placeholder_rund.jpg" alt="Rundes Fenster" class="window-image">
                    <h3>Rund</h3>
                </div>
                <div class="window-card" onclick="location.href='konfigurator_sonderform.php'">
                    <img src="images/placeholder_sonder.jpg" alt="Sonderform" class="window-image">
                    <h3>Sonderform</h3>
                </div>
            </div>
        </div>

        <!-- Tschechische Fenster -->
        <div class="country-section">
            <h2 class="mb-4">Tschechische Fenster</h2>
            <div class="window-grid">
                <div class="window-card" onclick="location.href='konfigurator.php'">
                    <img src="images/placeholder_rechteck.jpg" alt="Rechteckiges Fenster" class="window-image">
                    <h3>Rechteckig</h3>
                </div>
                <div class="window-card" onclick="location.href='konfigurator_rund.php'">
                    <img src="images/placeholder_rund.jpg" alt="Rundes Fenster" class="window-image">
                    <h3>Rund</h3>
                </div>
                <div class="window-card" onclick="location.href='konfigurator_sonderform.php'">
                    <img src="images/placeholder_sonder.jpg" alt="Sonderform" class="window-image">
                    <h3>Sonderform</h3>
                </div>
            </div>
        </div>

        <!-- Sonstige Fenster -->
        <div class="country-section">
            <h2 class="mb-4">Sonstige Fenster</h2>
            <div class="window-grid">
                <div class="window-card" onclick="location.href='konfigurator.php'">
                    <img src="images/placeholder_rechteck.jpg" alt="Rechteckiges Fenster" class="window-image">
                    <h3>Rechteckig</h3>
                </div>
                <div class="window-card" onclick="location.href='konfigurator_rund.php'">
                    <img src="images/placeholder_rund.jpg" alt="Rundes Fenster" class="window-image">
                    <h3>Rund</h3>
                </div>
                <div class="window-card" onclick="location.href='konfigurator_sonderform.php'">
                    <img src="images/placeholder_sonder.jpg" alt="Sonderform" class="window-image">
                    <h3>Sonderform</h3>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>