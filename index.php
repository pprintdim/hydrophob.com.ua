<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <title>Hydrophob</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="body"> 
    <?php
    $IMAGES = json_decode(file_get_contents(__DIR__ . '/images.json'), true);
    if (!is_array($IMAGES)) { $IMAGES = []; }
    require __DIR__ . '/sections/header.php';
    ?>

    <main class="main">
<?php
        require __DIR__ . '/sections/hero.php';
        require __DIR__ . '/sections/popup-video.php';
        require __DIR__ . '/sections/popup-photo.php';
        require __DIR__ . '/sections/popup-product.php';
        require __DIR__ . '/sections/about.php';
        require __DIR__ . '/sections/action.php';
        require __DIR__ . '/sections/images-block.php';
        require __DIR__ . '/sections/product.php';
        require __DIR__ . '/sections/info-block.php';
        require __DIR__ . '/sections/reviews.php';
        require __DIR__ . '/sections/guarantee.php';
        require __DIR__ . '/sections/faq.php';
        require __DIR__ . '/sections/delivery.php';
        require __DIR__ . '/sections/contacts.php';
        require __DIR__ . '/sections/cart.php';
    ?>
    </main>

    <?php require __DIR__ . '/sections/footer.php'; ?>

    <script src="https://maps.googleapis.com/maps/api/js?key=API_HERE&libraries=maps,marker&v=beta&callback=initMap&loading=async" async defer></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>