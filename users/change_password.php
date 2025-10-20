<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

$id_user = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

if (!$userData) {
    alert('User tidak ditemukan', 'error');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate
        if (strlen($new_password) < 6) {
            throw new Exception('Password minimal 6 karakter');
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception('Password dan konfirmasi password tidak cocok');
        }
        
        // Hash password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id_user = ?");
        $stmt->bind_param("si", $password_hash, $id_user);
        
        if ($stmt->execute()) {
            alert('Password berhasil diubah!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal update password: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Ganti Password';
include '../includes/header.php';
?>

<div class="max-w-2xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Ganti Password</h2>
            <p class="text-gray-600 mt-1">Ubah password untuk <?= htmlspecialchars($userData['nama_lengkap']) ?></p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- User Info -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 rounded-2xl p-6 text-white">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                <span class="text-2xl font-bold"><?= strtoupper(substr($userData['nama_lengkap'], 0, 2)) ?></span>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold"><?= htmlspecialchars($userData['nama_lengkap']) ?></h3>
                <p class="text-purple-100">@<?= htmlspecialchars($userData['username']) ?></p>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-white bg-opacity-20 mt-2">
                    <?= ucfirst($userData['role']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="space-y-6">
            <!-- Username (Info) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" value="<?= htmlspecialchars($userData['username']) ?>" readonly
                       class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-600 cursor-not-allowed">
            </div>

            <!-- New Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Password Baru <span class="text-red-500">*</span>
                </label>
                <input type="password" name="new_password" id="new_password" required minlength="6"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Minimal 6 karakter">
                <p class="text-xs text-gray-500 mt-1">Password minimal 6 karakter</p>
            </div>

            <!-- Confirm New Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Konfirmasi Password Baru <span class="text-red-500">*</span>
                </label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Ketik ulang password baru">
                <p class="text-xs text-gray-500 mt-1" id="passwordMatch"></p>
            </div>

            <!-- Password Strength Indicator -->
            <div id="passwordStrength" class="hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2">Kekuatan Password</label>
                <div class="flex space-x-2">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full">
                        <div id="strengthBar" class="h-full rounded-full transition-all duration-300"></div>
                    </div>
                </div>
                <p id="strengthText" class="text-xs mt-1"></p>
            </div>
        </div>

        <!-- Warning Box -->
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-yellow-800 mb-1">Perhatian!</p>
                    <p class="text-sm text-yellow-700">Setelah password diubah, user harus login ulang dengan password baru.</p>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                Ganti Password
            </button>
        </div>
    </form>
</div>

<script>
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const passwordMatch = document.getElementById('passwordMatch');
const passwordStrength = document.getElementById('passwordStrength');
const strengthBar = document.getElementById('strengthBar');
const strengthText = document.getElementById('strengthText');

// Check password match
confirmPassword.addEventListener('input', function() {
    const password = newPassword.value;
    const confirm = this.value;
    
    if (confirm) {
        if (password !== confirm) {
            this.setCustomValidity('Password tidak cocok');
            this.classList.add('border-red-500');
            passwordMatch.textContent = '❌ Password tidak cocok';
            passwordMatch.classList.add('text-red-600');
        } else {
            this.setCustomValidity('');
            this.classList.remove('border-red-500');
            this.classList.add('border-green-500');
            passwordMatch.textContent = '✓ Password cocok';
            passwordMatch.classList.remove('text-red-600');
            passwordMatch.classList.add('text-green-600');
        }
    }
});

// Password strength checker
newPassword.addEventListener('input', function() {
    const password = this.value;
    
    if (password.length > 0) {
        passwordStrength.classList.remove('hidden');
        
        let strength = 0;
        let color = '';
        let text = '';
        
        // Length
        if (password.length >= 6) strength += 25;
        if (password.length >= 8) strength += 25;
        
        // Contains number
        if (/\d/.test(password)) strength += 25;
        
        // Contains letter
        if (/[a-zA-Z]/.test(password)) strength += 25;
        
        // Set color and text
        if (strength <= 25) {
            color = 'bg-red-500';
            text = 'Lemah';
        } else if (strength <= 50) {
            color = 'bg-yellow-500';
            text = 'Sedang';
        } else if (strength <= 75) {
            color = 'bg-blue-500';
            text = 'Baik';
        } else {
            color = 'bg-green-500';
            text = 'Kuat';
        }
        
        strengthBar.style.width = strength + '%';
        strengthBar.className = 'h-full rounded-full transition-all duration-300 ' + color;
        strengthText.textContent = text;
        strengthText.className = 'text-xs mt-1 font-medium ' + (strength > 50 ? 'text-green-600' : 'text-yellow-600');
    } else {
        passwordStrength.classList.add('hidden');
    }
});
</script>

<?php include '../includes/footer.php'; ?>