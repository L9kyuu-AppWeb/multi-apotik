<?php
define('APP_ACCESS', true);
require_once '../config.php';
checkRole(['admin']);

$db = db();

// Get apotik list
$apotikList = $db->query("SELECT * FROM apotik WHERE status = 'aktif' ORDER BY nama_apotik");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_apotik = !empty($_POST['id_apotik']) ? (int)$_POST['id_apotik'] : null;
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $role = sanitize($_POST['role']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        $status = sanitize($_POST['status']);
        
        // Validate
        if (strlen($username) < 4) {
            throw new Exception('Username minimal 4 karakter');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('Password minimal 6 karakter');
        }
        
        if ($password !== $confirm_password) {
            throw new Exception('Password dan konfirmasi password tidak cocok');
        }
        
        // Check if username exists
        $checkStmt = $db->prepare("SELECT id_user FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception('Username sudah digunakan');
        }
        
        // Validate apotik for admin & kasir
        if (in_array($role, ['admin', 'kasir']) && !$id_apotik) {
            throw new Exception('Admin dan Kasir harus memilih apotik');
        }
        
        // Manajer tidak boleh punya apotik
        if ($role === 'manajer') {
            $id_apotik = null;
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $db->prepare("INSERT INTO users (
            id_apotik, username, password, nama_lengkap, role, no_telp, email, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("isssssss",
            $id_apotik, $username, $password_hash, $nama_lengkap, 
            $role, $no_telp, $email, $status
        );
        
        if ($stmt->execute()) {
            alert('User berhasil ditambahkan!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal menyimpan data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Tambah User';
include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Tambah User Baru</h2>
            <p class="text-gray-600 mt-1">Buat akun pengguna sistem</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Username -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Username <span class="text-red-500">*</span>
                </label>
                <input type="text" name="username" required minlength="4"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Minimal 4 karakter">
                <p class="text-xs text-gray-500 mt-1">Username untuk login</p>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_lengkap" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Nama lengkap user">
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Password <span class="text-red-500">*</span>
                </label>
                <input type="password" name="password" id="password" required minlength="6"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Minimal 6 karakter">
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Konfirmasi Password <span class="text-red-500">*</span>
                </label>
                <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="Ketik ulang password">
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role" id="role" required onchange="toggleApotik()"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">- Pilih Role -</option>
                    <option value="admin">Admin (Kelola Apotik)</option>
                    <option value="kasir">Kasir (Transaksi)</option>
                    <option value="manajer">Manajer (Laporan Semua)</option>
                </select>
            </div>

            <!-- Apotik -->
            <div id="apotikContainer">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Apotik <span class="text-red-500" id="requiredApotik">*</span>
                </label>
                <select name="id_apotik" id="id_apotik"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="">- Pilih Apotik -</option>
                    <?php while ($apotik = $apotikList->fetch_assoc()): ?>
                    <option value="<?= $apotik['id_apotik'] ?>">
                        <?= htmlspecialchars($apotik['nama_apotik']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1" id="apotikHelp">Wajib untuk Admin & Kasir</p>
            </div>

            <!-- No Telepon -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                <input type="text" name="no_telp"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="08123456789">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500"
                       placeholder="user@email.com">
            </div>

            <!-- Status -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Non-Aktif</option>
                </select>
            </div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm font-medium text-blue-800 mb-1">Hak Akses Role:</p>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li><strong>Admin:</strong> Kelola master data, obat, transaksi pembelian & penjualan untuk 1 apotik</li>
                        <li><strong>Kasir:</strong> Hanya transaksi penjualan untuk 1 apotik</li>
                        <li><strong>Manajer:</strong> Akses laporan & statistik semua apotik (tidak terikat apotik)</li>
                    </ul>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Simpan User
            </button>
        </div>
    </form>
</div>

<script>
function toggleApotik() {
    const role = document.getElementById('role').value;
    const apotikSelect = document.getElementById('id_apotik');
    const requiredMark = document.getElementById('requiredApotik');
    const apotikHelp = document.getElementById('apotikHelp');
    
    if (role === 'manajer') {
        apotikSelect.value = '';
        apotikSelect.disabled = true;
        apotikSelect.required = false;
        requiredMark.style.display = 'none';
        apotikHelp.textContent = 'Manajer tidak terikat apotik (akses semua)';
        apotikHelp.classList.add('text-green-600');
    } else {
        apotikSelect.disabled = false;
        apotikSelect.required = true;
        requiredMark.style.display = 'inline';
        apotikHelp.textContent = 'Wajib untuk Admin & Kasir';
        apotikHelp.classList.remove('text-green-600');
    }
}

// Password match validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    
    if (confirm && password !== confirm) {
        this.setCustomValidity('Password tidak cocok');
        this.classList.add('border-red-500');
    } else {
        this.setCustomValidity('');
        this.classList.remove('border-red-500');
    }
});
</script>

<?php include '../includes/footer.php'; ?>