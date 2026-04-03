<?php http_response_code(404); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | UsafiKonect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-8xl mb-6">🧦</div>
        <h1 class="text-6xl font-extrabold text-gray-200 mb-2">404</h1>
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Page Not Found</h2>
        <p class="text-gray-600 mb-8">Looks like this page went missing — just like that one sock. Let's get you back on track!</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="/usafikonect/" class="px-6 py-3 bg-orange-500 text-white font-semibold rounded-xl hover:bg-orange-600 transition-all shadow-md">
                <i class="fas fa-home mr-2"></i> Go Home
            </a>
            <a href="/usafikonect/contact.php" class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all">
                <i class="fas fa-headset mr-2"></i> Contact Support
            </a>
        </div>
    </div>
</body>
</html>
