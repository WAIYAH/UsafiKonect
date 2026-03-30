<?php
/**
 * UsafiKonect - Maintenance Mode Page
 */
$maintenance_message = $message ?? 'We are currently performing scheduled maintenance. Please check back soon.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - UsafiKonect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-orange-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-lg">
        <div class="text-8xl mb-6">🧺</div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <span class="text-orange-500">Usafi</span><span class="text-blue-800">Konect</span>
        </h1>
        <div class="bg-white rounded-2xl shadow-lg p-8 mt-6">
            <i class="fas fa-tools text-4xl text-orange-500 mb-4"></i>
            <h2 class="text-xl font-semibold text-gray-700 mb-3">Under Maintenance</h2>
            <p class="text-gray-500"><?= htmlspecialchars($maintenance_message) ?></p>
            <p class="text-sm text-gray-400 mt-4">Tutarudi hivi karibuni! (We'll be back soon!)</p>
        </div>
    </div>
</body>
</html>
