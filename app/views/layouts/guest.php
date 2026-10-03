<?php
/** @var string $content */
$pageTitle = $title ?? 'Generator Fuel Tracker';
$appName = (string) config('app.name', 'Generator Fuel Tracker');
?>
<!DOCTYPE html>
<html lang="en" class="h-full antialiased">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \App\Core\Helpers::e($pageTitle) ?> · <?= \App\Core\Helpers::e($appName) ?></title>
    <link rel="stylesheet" href="<?= asset('tailwind.min.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-outfit { font-family: 'Outfit', sans-serif; }
    </style>
</head>

<body class="h-full bg-white text-slate-900">
    <?= $content ?>
</body>

</html>