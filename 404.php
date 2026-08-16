<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>404 — сторінку не знайдено | Hydrophob</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .errorPage{
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
            background: #121212;
            color: #fff;
            text-align: center;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
        }
        .errorPage__logo img{
            max-width: 220px;
        }
        .errorPage__code{
            font-size: clamp(120px, 24vw, 280px);
            font-weight: 900;
            line-height: 1;
            color: transparent;
            -webkit-text-stroke: 2px #1D9CB2;
            letter-spacing: 8px;
        }
        .errorPage__title{
            font-size: clamp(24px, 4vw, 40px);
            font-weight: 700;
        }
        .errorPage__descr{
            font-size: 18px;
            line-height: 28px;
            opacity: .7;
            max-width: 480px;
        }
        .errorPage .btn{
            margin-top: 8px;
        }
    </style>
</head>
<body class="body">
    <main class="errorPage">
        <a class="errorPage__logo" href="index.php"><img src="img/logo.svg" alt="Hydrophob"></a>
        <p class="errorPage__code">404</p>
        <h1 class="errorPage__title">Сторінку не знайдено</h1>
        <p class="errorPage__descr">Схоже, ця сторінка скотилася з сайту, як крапля з гідрофобного покриття. Поверніться на головну — там усе на місці.</p>
        <a class="btn" href="index.php">На головну</a>
    </main>
</body>
</html>
