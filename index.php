<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sahayak - Local Help Network</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <h1 class="text-3xl font-bold text-center mb-6 text-blue-600">Sahayak</h1>
            
            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">Invalid credentials</div>
            <?php endif; ?>
            
            <?php if(isset($_GET['registered'])): ?>
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">Registration successful! Please login.</div>
            <?php endif; ?>
            
            <div id="loginForm">
                <form method="POST" action="api/login.php">
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
                    <button onclick="showRegister()" class="text-blue-500 hover:underline">New User? Register</button>
                </div>
            </div>
            
            <div id="registerForm" style="display:none;">
                <form method="POST" action="api/register.php">
                    <div class="mb-4">
                        <input type="text" name="full_name" placeholder="Full Name" 
                               class="w-full p-3 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <input type="tel" name="phone" placeholder="Phone Number" 
                               class="w-full p-3 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <input type="password" name="password" placeholder="Password" 
                               class="w-full p-3 border rounded-lg" required>
                    </div>
                    <div class="mb-4">
                        <select name="user_type" class="w-full p-3 border rounded-lg" required>
                            <option value="">Select Role</option>
                            <option value="customer">Customer (Need Help)</option>
                            <option value="helper">Helper (Provide Help)</option>
                        </select>
                    </div>
                    
                    <div id="helperFields" style="display:none;">
                        <div class="mb-4">
                            <input type="text" name="skill_tags" placeholder="Skills (e.g., Electrician, Plumber)" 
                                   class="w-full p-3 border rounded-lg">
                        </div>
                        <div class="mb-4">
                            <input type="number" name="base_rate" placeholder="Rate per hour (₹)" 
                                   class="w-full p-3 border rounded-lg">
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-green-500 text-white p-3 rounded-lg hover:bg-green-600">
                        Register
                    </button>
                </form>
                
                <div class="mt-4 text-center">
                    <button onclick="showLogin()" class="text-blue-500 hover:underline">Already have account? Login</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showRegister() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('registerForm').style.display = 'block';
        }
        
        function showLogin() {
            document.getElementById('registerForm').style.display = 'none';
            document.getElementById('loginForm').style.display = 'block';
        }
        
        document.querySelector('select[name="user_type"]').addEventListener('change', function() {
            const helperFields = document.getElementById('helperFields');
            if (this.value === 'helper') {
                helperFields.style.display = 'block';
                helperFields.querySelectorAll('input').forEach(input => input.required = true);
            } else {
                helperFields.style.display = 'none';
                helperFields.querySelectorAll('input').forEach(input => input.required = false);
            }
        });
    </script>
</body>
</html>