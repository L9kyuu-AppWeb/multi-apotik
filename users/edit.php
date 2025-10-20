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

// Get apotik list
$apotikList = $db->query("SELECT * FROM apotik WHERE status = 'aktif' ORDER BY nama_apotik");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id_apotik = !empty($_POST['id_apotik']) ? (int)$_POST['id_apotik'] : null;
        $nama_lengkap = sanitize($_POST['nama_lengkap']);
        $role = sanitize($_POST['role']);
        $no_telp = sanitize($_POST['no_telp']);
        $email = sanitize($_POST['email']);
        $status = sanitize($_POST['status']);
        
        // Validate apotik for admin & kasir
        if (in_array($role, ['admin', 'kasir']) && !$id_apotik) {
            throw new Exception('Admin dan Kasir harus memilih apotik');
        }
        
        // Manajer tidak boleh punya apotik
        if ($role === 'manajer') {
            $id_apotik = null;
        }
        
        $stmt = $db->prepare("UPDATE users SET 
            id_apotik = ?, nama_lengkap = ?, role = ?, no_telp = ?, email = ?, status = ?
            WHERE id_user = ?");
        
        $stmt->bind_param("isssssi",
            $id_apotik, $nama_lengkap, $role, $no_telp, $email, $status, $id_user
        );
        
        if ($stmt->execute()) {
            alert('Data user berhasil diupdate!', 'success');
            redirect('index.php');
        } else {
            throw new Exception('Gagal update data: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        alert('Error: ' . $e->getMessage(), 'error');
    }
}

$pageTitle = 'Edit User';
include '../includes/header.php';
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Edit User</h2>
            <p class="text-gray-600 mt-1">Ubah data pengguna sistem</p>
        </div>
        <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all">
            Kembali
        </a>
    </div>

    <!-- Form -->
    <form method="POST" class="bg-white rounded-2xl shadow-sm p-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Username (Read Only) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                <input type="text" value="<?= htmlspecialchars($userData['username']) ?>" readonly
                       class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-xl text-gray-600 cursor-not-allowed">
                <p class="text-xs text-gray-500 mt-1">Username tidak dapat diubah</p>
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Nama Lengkap <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($userData['nama_lengkap']) ?>" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Role -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Role <span class="text-red-500">*</span>
                </label>
                <select name="role" id="role" required onchange="toggleApotik()"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="admin" <?= $userData['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="kasir" <?= $userData['role'] === 'kasir' ? 'selected' : '' ?>>Kasir</option>
                    <option value="manajer" <?= $userData['role'] === 'manajer' ? 'selected' : '' ?>>Manajer</option>
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
                    <option value="<?= $apotik['id_apotik'] ?>" <?= $userData['id_apotik'] == $apotik['id_apotik'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($apotik['nama_apotik']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1" id="apotikHelp">Wajib untuk Admin & Kasir</p>
            </div>

            <!-- No Telepon -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">No. Telepon</label>
                <input type="text" name="no_telp" value="<?= htmlspecialchars($userData['no_telp']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Status -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                </label>
                <select name="status" required class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500">
                    <option value="aktif" <?= $userData['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $userData['status'] === 'nonaktif' ? 'selected' : '' ?>>Non-Aktif</option>
                </select>
            </div>
        </div>

        <!-- Change Password Link -->
        <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    <p class="text-sm font-medium text-purple-800">Ingin mengganti password user ini?</p>
                </div>
                <a href="change_password.php?id=<?= $id_user ?>" class="px-4 py-2 bg-purple-600 text-white rounded-xl text-sm font-medium hover:bg-purple-700 transition-all">
                    Ganti Password
                </a>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="index.php" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-xl font-medium hover:bg-gray-300 transition-all">
                Batal
            </a>
            <button type="submit" class="px-6 py-3 gradient-bg text-white rounded-xl font-semibold hover:shadow-lg transition-all">
                Update Data
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
        apotikHelp.textContent = 'Manajer tidak terikat apotik';
        apotikHelp.classList.add('text-green-600');
    } else {
        apotikSelect.disabled = false;
        apotikSelect.required = true;
        requiredMark.style.display = 'inline';
        apotikHelp.textContent = 'Wajib untuk Admin & Kasir';
        apotikHelp.classList.remove('text-green-600');
    }
}

// Run on page load
toggleApotik();
</script>

<?php include '../includes/footer.php'; ?>