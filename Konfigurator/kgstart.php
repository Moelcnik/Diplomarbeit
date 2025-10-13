
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurator</title>
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
    // Kategorien-Array mit Titeln und Bildern
    $categories = [
        [
            'id' => 'kategorie1',
            'title' => 'Kategorie 1',
            // 'image' => 'placeholder1.jpg'  
        ],
        [
            'id' => 'kategorie2',
            'title' => 'Kategorie 2', 
            // 'image' => 'placeholder2.jpg'  
        ],
        [
            'id' => 'kategorie3',
            'title' => 'Kategorie 3',
            // 'image' => 'placeholder3.jpg'  
        ]
    ];
?>
        <div class="category-grid">
            <?php foreach($categories as $category): ?>
                <a href="konfigurator.php?category=<?php echo $category['id']; ?>" class="category-card">
                    <div class="category-image" style="display: flex; align-items: center; justify-content: center; background-color: #ddd;">
                        <span><?php echo $category['title']; ?></span>
                    </div>
                    <h2 class="category-title"><?php echo $category['title']; ?></h2>
                </a>
            <?php endforeach; ?>
        </div>
</body>
</html>