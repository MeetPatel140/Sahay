<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahay - Local Help Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-2xl font-bold text-center mb-6">Sahay</h1>
            
            <form id="loginForm" method="POST" action="api/login.php">
                <div class="mb-4">
                    <input type="tel" name="phone" placeholder="Phone Number" 
                           class="w-full p-3 border rounded-lg" required>
                </div>
                <div class="mb-4">
                    <input type="password" name="password" placeholder="Password" 
                           class="w-full p-3 border rounded-lg" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white p-3 rounded-lg hover:bg-blue-600">
                    Login
                </button>
            </form>
            
            <div class="mt-4 text-center">
                <a href="#" class="text-blue-500">New User? Register</a>
            </div>
        </div>
    </div>
</body>
</html>