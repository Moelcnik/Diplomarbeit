
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurator</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .category-card {
            text-decoration: none;
            color: inherit;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .category-card:hover {
            transform: scale(1.02);
            text-decoration: none;
            color: inherit;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .category-image {
            height: 200px;
            width: 100%;
        }
        .category-title {
            padding: 10px;
            margin: 0;
            text-align: center;
        }
    </style>
</head>
<body>
<?php
    // Kategorien-Array mit Titeln und Verlinkungen
    $categories = [
        [
            'id' => 'fenster',
            'title' => 'Fenster',
            'link' => 'konfigurator', // Link zum Fensterkonfigurator
            // 'image' => 'placeholder1.jpg'  
        ],
        [
            'id' => 'kategorie2',
            'title' => 'Kategorie 2', 
            'link' => '#', // Platzhalter für weitere Kategorien
            // 'image' => 'placeholder2.jpg'  
        ],
        [
            'id' => 'kategorie3',
            'title' => 'Kategorie 3',
            'link' => '#', // Platzhalter für weitere Kategorien
            // 'image' => 'placeholder3.jpg'  
        ]
    ];
?>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Produktkonfigurator</h1>
        <div class="category-grid">
            <?php foreach($categories as $category): ?>
                <a href="<?php echo $category['link']; ?>" class="category-card">
                    <div class="category-image" style="display: flex; align-items: center; justify-content: center; background-color: #f8f9fa;">
                        <span class="h3"><?php echo $category['title']; ?></span>
                    </div>
                    <h2 class="category-title"><?php echo $category['title']; ?></h2>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>