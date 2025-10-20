<?php
define('APP_ACCESS', true);
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $db = db();
        $stmt = $db->prepare("SELECT u.*, a.nama_apotik FROM users u 
                              LEFT JOIN apotik a ON u.id_apotik = a.id_apotik 
                              WHERE u.username = ? AND u.status = 'aktif'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['id_apotik'] = $user['id_apotik'];
                $_SESSION['nama_apotik'] = $user['nama_apotik'];
                
                // Update last login
                $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
                $updateStmt->bind_param("i", $user['id_user']);
                $updateStmt->execute();
                
                // Log aktivitas
                $logStmt = $db->prepare("INSERT INTO log_aktivitas (id_user, id_apotik, tipe_aktivitas, aksi, ip_address, keterangan) 
                                        VALUES (?, ?, 'Login', 'create', ?, 'User login ke sistem')");
                $ip = $_SERVER['REMOTE_ADDR'];
                $logStmt->bind_param("iis", $user['id_user'], $user['id_apotik'], $ip);
                $logStmt->execute();
                
                redirect('dashboard.php');
            } else {
                $error = 'Password salah';
            }
        } else {
            $error = 'Username tidak ditemukan atau akun tidak aktif';
        }
    }
}

$timeout = isset($_GET['timeout']) ? true : false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 gradient-bg rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2"><?= APP_NAME ?></h2>
                <p class="text-gray-600">Sistem Informasi Terintegrasi</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login ke Akun Anda</h3>

                <?php if ($timeout): ?>
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-lg mb-4">
                    <p class="font-medium">Sesi Anda telah berakhir. Silakan login kembali.</p>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-4">
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <input type="text" name="username" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Masukkan username" required autofocus>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <input type="password" name="password" class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Masukkan password" required>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                        Login
                    </button>
                </form>

                <!-- Demo Info -->
                <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                    <p class="text-sm font-semibold text-blue-800 mb-2">🔑 Demo Credentials:</p>
                    <div class="space-y-1 text-xs text-blue-700">
                        <p><strong>Admin:</strong> admin / admin123</p>
                        <p><strong>Kasir:</strong> kasir1 / admin123</p>
                        <p><strong>Manajer:</strong> manajer / admin123</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-gray-500 text-sm mt-8">
                &copy; 2025 <?= APP_NAME ?>. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>