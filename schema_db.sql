-- Database Schema Sistem Informasi Multi Apotik
-- Created: 2025

-- 1. Tabel Apotik (Cabang)
CREATE TABLE apotik (
    id_apotik INT PRIMARY KEY AUTO_INCREMENT,
    kode_apotik VARCHAR(20) UNIQUE NOT NULL,
    nama_apotik VARCHAR(100) NOT NULL,
    alamat TEXT,
    no_telp VARCHAR(20),
    email VARCHAR(100),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tabel User (Pengguna)
CREATE TABLE users (
    id_user INT PRIMARY KEY AUTO_INCREMENT,
    id_apotik INT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir', 'manajer') NOT NULL,
    no_telp VARCHAR(20),
    email VARCHAR(100),
    foto_profile VARCHAR(255),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_apotik) REFERENCES apotik(id_apotik) ON DELETE SET NULL
);

-- 3. Tabel Supplier
CREATE TABLE supplier (
    id_supplier INT PRIMARY KEY AUTO_INCREMENT,
    kode_supplier VARCHAR(20) UNIQUE NOT NULL,
    nama_supplier VARCHAR(100) NOT NULL,
    alamat TEXT,
    no_telp VARCHAR(20),
    email VARCHAR(100),
    contact_person VARCHAR(100),
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Tabel Pelanggan
CREATE TABLE pelanggan (
    id_pelanggan INT PRIMARY KEY AUTO_INCREMENT,
    no_identitas VARCHAR(50),
    nama_pelanggan VARCHAR(100) NOT NULL,
    jenis_kelamin ENUM('L', 'P'),
    tanggal_lahir DATE,
    alamat TEXT,
    no_telp VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. Tabel Dokter
CREATE TABLE dokter (
    id_dokter INT PRIMARY KEY AUTO_INCREMENT,
    kode_dokter VARCHAR(20) UNIQUE NOT NULL,
    nama_dokter VARCHAR(100) NOT NULL,
    spesialis VARCHAR(100),
    no_str VARCHAR(50),
    no_telp VARCHAR(20),
    alamat TEXT,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 6. Tabel Kategori Obat
CREATE TABLE kategori_obat (
    id_kategori INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 7. Tabel Obat
CREATE TABLE obat (
    id_obat INT PRIMARY KEY AUTO_INCREMENT,
    id_apotik INT NOT NULL,
    id_kategori INT,
    kode_obat VARCHAR(50) UNIQUE NOT NULL,
    nama_obat VARCHAR(200) NOT NULL,
    jenis_obat VARCHAR(100),
    satuan VARCHAR(20) NOT NULL,
    harga_beli DECIMAL(15,2) DEFAULT 0,
    harga_jual DECIMAL(15,2) NOT NULL,
    margin_persen DECIMAL(5,2),
    aturan_pakai TEXT,
    dosis VARCHAR(100),
    efek_samping TEXT,
    golongan ENUM('bebas', 'bebas_terbatas', 'keras', 'narkotika') DEFAULT 'bebas',
    perlu_resep BOOLEAN DEFAULT FALSE,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_apotik) REFERENCES apotik(id_apotik) ON DELETE CASCADE,
    FOREIGN KEY (id_kategori) REFERENCES kategori_obat(id_kategori) ON DELETE SET NULL
);

-- 8. Tabel Batch Obat (Stok per Batch)
CREATE TABLE batch_obat (
    id_batch INT PRIMARY KEY AUTO_INCREMENT,
    id_obat INT NOT NULL,
    no_batch VARCHAR(100) NOT NULL,
    tanggal_produksi DATE,
    tanggal_kadaluarsa DATE NOT NULL,
    stok_awal INT NOT NULL,
    stok_sisa INT NOT NULL,
    harga_beli_per_unit DECIMAL(15,2),
    status ENUM('tersedia', 'habis', 'expired', 'rusak') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat) ON DELETE CASCADE,
    UNIQUE KEY unique_batch (id_obat, no_batch)
);

-- 9. Tabel Pembelian (Header)
CREATE TABLE pembelian (
    id_pembelian INT PRIMARY KEY AUTO_INCREMENT,
    id_apotik INT NOT NULL,
    id_supplier INT NOT NULL,
    id_user INT,
    no_faktur VARCHAR(100) UNIQUE NOT NULL,
    tanggal_pembelian DATE NOT NULL,
    tanggal_jatuh_tempo DATE,
    total_item INT DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    pajak DECIMAL(15,2) DEFAULT 0,
    total_bayar DECIMAL(15,2) DEFAULT 0,
    status_pembayaran ENUM('lunas', 'belum_lunas', 'kredit') DEFAULT 'lunas',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_apotik) REFERENCES apotik(id_apotik) ON DELETE CASCADE,
    FOREIGN KEY (id_supplier) REFERENCES supplier(id_supplier) ON DELETE RESTRICT,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
);

-- 10. Tabel Detail Pembelian
CREATE TABLE detail_pembelian (
    id_detail_pembelian INT PRIMARY KEY AUTO_INCREMENT,
    id_pembelian INT NOT NULL,
    id_obat INT NOT NULL,
    id_batch INT,
    qty INT NOT NULL,
    harga_beli DECIMAL(15,2) NOT NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pembelian) REFERENCES pembelian(id_pembelian) ON DELETE CASCADE,
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat) ON DELETE RESTRICT,
    FOREIGN KEY (id_batch) REFERENCES batch_obat(id_batch) ON DELETE SET NULL
);

-- 11. Tabel Resep Dokter
CREATE TABLE resep (
    id_resep INT PRIMARY KEY AUTO_INCREMENT,
    id_apotik INT NOT NULL,
    id_dokter INT,
    id_pelanggan INT,
    no_resep VARCHAR(100) UNIQUE NOT NULL,
    tanggal_resep DATE NOT NULL,
    diagnosa TEXT,
    keterangan TEXT,
    status ENUM('pending', 'diproses', 'selesai') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_apotik) REFERENCES apotik(id_apotik) ON DELETE CASCADE,
    FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter) ON DELETE SET NULL,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE SET NULL
);

-- 12. Tabel Detail Resep
CREATE TABLE detail_resep (
    id_detail_resep INT PRIMARY KEY AUTO_INCREMENT,
    id_resep INT NOT NULL,
    id_obat INT NOT NULL,
    qty INT NOT NULL,
    aturan_pakai TEXT,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_resep) REFERENCES resep(id_resep) ON DELETE CASCADE,
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat) ON DELETE RESTRICT
);

-- 13. Tabel Penjualan (Header)
CREATE TABLE penjualan (
    id_penjualan INT PRIMARY KEY AUTO_INCREMENT,
    id_apotik INT NOT NULL,
    id_user INT,
    id_pelanggan INT,
    id_resep INT,
    no_transaksi VARCHAR(100) UNIQUE NOT NULL,
    tanggal_penjualan DATETIME NOT NULL,
    tipe_penjualan ENUM('bebas', 'resep') DEFAULT 'bebas',
    total_item INT DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    pajak DECIMAL(15,2) DEFAULT 0,
    total_bayar DECIMAL(15,2) DEFAULT 0,
    jumlah_dibayar DECIMAL(15,2) DEFAULT 0,
    kembalian DECIMAL(15,2) DEFAULT 0,
    metode_pembayaran ENUM('tunai', 'debit', 'kredit', 'transfer', 'ewallet') DEFAULT 'tunai',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_apotik) REFERENCES apotik(id_apotik) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id_pelanggan) ON DELETE SET NULL,
    FOREIGN KEY (id_resep) REFERENCES resep(id_resep) ON DELETE SET NULL
);

-- 14. Tabel Detail Penjualan
CREATE TABLE detail_penjualan (
    id_detail_penjualan INT PRIMARY KEY AUTO_INCREMENT,
    id_penjualan INT NOT NULL,
    id_obat INT NOT NULL,
    id_batch INT NOT NULL,
    qty INT NOT NULL,
    harga_jual DECIMAL(15,2) NOT NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id_penjualan) ON DELETE CASCADE,
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat) ON DELETE RESTRICT,
    FOREIGN KEY (id_batch) REFERENCES batch_obat(id_batch) ON DELETE RESTRICT
);

-- 15. Tabel Kategori Pengeluaran
CREATE TABLE kategori_pengeluaran (
    id_kategori_pengeluaran INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 16. Tabel Pengeluaran Operasional
CREATE TABLE pengeluaran (
    id_pengeluaran INT PRIMARY KEY AUTO_INCREMENT,
    id_apotik INT NOT NULL,
    id_kategori_pengeluaran INT,
    id_user INT,
    no_pengeluaran VARCHAR(100) UNIQUE NOT NULL,
    tanggal_pengeluaran DATE NOT NULL,
    nama_pengeluaran VARCHAR(200) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    bukti_pengeluaran VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_apotik) REFERENCES apotik(id_apotik) ON DELETE CASCADE,
    FOREIGN KEY (id_kategori_pengeluaran) REFERENCES kategori_pengeluaran(id_kategori_pengeluaran) ON DELETE SET NULL,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL
);

-- 17. Tabel Log Aktivitas
CREATE TABLE log_aktivitas (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    id_user INT,
    id_apotik INT,
    tipe_aktivitas VARCHAR(100) NOT NULL,
    tabel_terkait VARCHAR(100),
    id_record INT,
    aksi ENUM('create', 'update', 'delete') NOT NULL,
    data_lama TEXT,
    data_baru TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL,
    FOREIGN KEY (id_apotik) REFERENCES apotik(id_apotik) ON DELETE SET NULL
);

-- ========================================
-- TRIGGER SECTION
-- ========================================

-- TRIGGER 1: Update Stok saat Pembelian (Insert Detail Pembelian)
DELIMITER $$
CREATE TRIGGER trg_after_insert_detail_pembelian
AFTER INSERT ON detail_pembelian
FOR EACH ROW
BEGIN
    -- Update stok batch obat
    UPDATE batch_obat 
    SET stok_sisa = stok_sisa + NEW.qty,
        status = 'tersedia',
        updated_at = CURRENT_TIMESTAMP
    WHERE id_batch = NEW.id_batch;
    
    -- Log aktivitas
    INSERT INTO log_aktivitas (id_user, tipe_aktivitas, tabel_terkait, id_record, aksi, keterangan)
    VALUES (
        (SELECT id_user FROM pembelian WHERE id_pembelian = NEW.id_pembelian),
        'Pembelian Obat',
        'batch_obat',
        NEW.id_batch,
        'update',
        CONCAT('Stok bertambah +', NEW.qty, ' dari pembelian #', NEW.id_pembelian)
    );
END$$
DELIMITER ;

-- TRIGGER 2: Update Stok saat Penjualan (Insert Detail Penjualan)
DELIMITER $$
CREATE TRIGGER trg_after_insert_detail_penjualan
AFTER INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
    -- Update stok batch obat
    UPDATE batch_obat 
    SET stok_sisa = stok_sisa - NEW.qty,
        status = CASE 
            WHEN (stok_sisa - NEW.qty) <= 0 THEN 'habis'
            ELSE 'tersedia'
        END,
        updated_at = CURRENT_TIMESTAMP
    WHERE id_batch = NEW.id_batch;
    
    -- Log aktivitas
    INSERT INTO log_aktivitas (id_user, tipe_aktivitas, tabel_terkait, id_record, aksi, keterangan)
    VALUES (
        (SELECT id_user FROM penjualan WHERE id_penjualan = NEW.id_penjualan),
        'Penjualan Obat',
        'batch_obat',
        NEW.id_batch,
        'update',
        CONCAT('Stok berkurang -', NEW.qty, ' dari penjualan #', NEW.id_penjualan)
    );
END$$
DELIMITER ;

-- TRIGGER 3: Cek Obat Expired sebelum Penjualan
DELIMITER $$
CREATE TRIGGER trg_before_insert_detail_penjualan
BEFORE INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
    DECLARE tgl_expired DATE;
    DECLARE batch_status VARCHAR(20);
    
    -- Ambil tanggal kadaluarsa batch
    SELECT tanggal_kadaluarsa, status INTO tgl_expired, batch_status
    FROM batch_obat
    WHERE id_batch = NEW.id_batch;
    
    -- Cek apakah batch expired atau tidak tersedia
    IF tgl_expired < CURDATE() OR batch_status = 'expired' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Batch obat sudah kadaluarsa!';
    END IF;
    
    IF batch_status = 'habis' OR batch_status = 'rusak' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Batch obat tidak tersedia!';
    END IF;
END$$
DELIMITER ;

-- TRIGGER 4: Update Status Batch Expired Otomatis
DELIMITER $$
CREATE TRIGGER trg_check_expired_batch
BEFORE UPDATE ON batch_obat
FOR EACH ROW
BEGIN
    IF NEW.tanggal_kadaluarsa < CURDATE() AND NEW.status != 'expired' THEN
        SET NEW.status = 'expired';
    END IF;
END$$
DELIMITER ;

-- TRIGGER 5: Log saat Hapus Data Penting
DELIMITER $$
CREATE TRIGGER trg_before_delete_obat
BEFORE DELETE ON obat
FOR EACH ROW
BEGIN
    INSERT INTO log_aktivitas (tipe_aktivitas, tabel_terkait, id_record, aksi, data_lama, keterangan)
    VALUES (
        'Hapus Obat',
        'obat',
        OLD.id_obat,
        'delete',
        CONCAT('Kode: ', OLD.kode_obat, ', Nama: ', OLD.nama_obat),
        'Data obat dihapus dari sistem'
    );
END$$
DELIMITER ;

-- -- TRIGGER 6: Update Total Pembelian
-- DELIMITER $$
-- CREATE TRIGGER trg_after_insert_detail_pembelian_total
-- AFTER INSERT ON detail_pembelian
-- FOR EACH ROW
-- BEGIN
--     UPDATE pembelian
--     SET total_item = total_item + 1,
--         subtotal = subtotal + NEW.subtotal,
--         total_bayar = (subtotal + NEW.subtotal) - diskon + pajak
--     WHERE id_pembelian = NEW.id_pembelian;
-- END$$
-- DELIMITER ;

-- -- TRIGGER 7: Update Total Penjualan
-- DELIMITER $$
-- CREATE TRIGGER trg_after_insert_detail_penjualan_total
-- AFTER INSERT ON detail_penjualan
-- FOR EACH ROW
-- BEGIN
--     UPDATE penjualan
--     SET total_item = total_item + 1,
--         subtotal = subtotal + NEW.subtotal,
--         total_bayar = (subtotal + NEW.subtotal) - diskon + pajak
--     WHERE id_penjualan = NEW.id_penjualan;
-- END$$
-- DELIMITER ;

-- ========================================
-- VIEW SECTION
-- ========================================

-- VIEW 1: Stok Obat Per Apotik
CREATE VIEW v_stok_obat AS
SELECT 
    o.id_apotik,
    ap.nama_apotik,
    o.id_obat,
    o.kode_obat,
    o.nama_obat,
    o.satuan,
    o.harga_jual,
    COALESCE(SUM(b.stok_sisa), 0) as total_stok,
    COUNT(b.id_batch) as jumlah_batch,
    MIN(b.tanggal_kadaluarsa) as expired_terdekat
FROM obat o
LEFT JOIN batch_obat b ON o.id_obat = b.id_obat AND b.status = 'tersedia'
LEFT JOIN apotik ap ON o.id_apotik = ap.id_apotik
GROUP BY o.id_obat;

-- VIEW 2: Obat Mendekati Expired (30 hari)
CREATE VIEW v_obat_expired_warning AS
SELECT 
    ap.nama_apotik,
    o.kode_obat,
    o.nama_obat,
    b.no_batch,
    b.tanggal_kadaluarsa,
    b.stok_sisa,
    DATEDIFF(b.tanggal_kadaluarsa, CURDATE()) as hari_tersisa
FROM batch_obat b
JOIN obat o ON b.id_obat = o.id_obat
JOIN apotik ap ON o.id_apotik = ap.id_apotik
WHERE b.tanggal_kadaluarsa BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  AND b.status = 'tersedia'
  AND b.stok_sisa > 0
ORDER BY b.tanggal_kadaluarsa ASC;

-- ========================================
-- SEED DATA - Sistem Multi Apotik
-- Data sample untuk testing sistem
-- ========================================

-- Insert Sample Apotik
INSERT INTO apotik (kode_apotik, nama_apotik, alamat, no_telp, email, status) VALUES
('APT001', 'Apotik Sehat Sentosa', 'Jl. Merdeka No. 123, Jakarta Pusat', '021-12345678', 'sentosa@apotik.com', 'aktif'),
('APT002', 'Apotik Medika Farma', 'Jl. Ahmad Yani No. 45, Bandung', '022-87654321', 'medika@apotik.com', 'aktif'),
('APT003', 'Apotik Husada Jaya', 'Jl. Sudirman No. 89, Surabaya', '031-55667788', 'husada@apotik.com', 'aktif');

-- Insert Sample Users (password: admin123)
-- Hash generated using: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO users (id_apotik, username, password, nama_lengkap, role, no_telp, email, status) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Sentosa', 'admin', '08123456789', 'admin@apotik.com', 'aktif'),
(1, 'kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Apotik Sentosa', 'kasir', '08123456790', 'kasir1@apotik.com', 'aktif'),
(2, 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator Medika', 'admin', '08123456791', 'admin2@apotik.com', 'aktif'),
(2, 'kasir2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Apotik Medika', 'kasir', '08123456792', 'kasir2@apotik.com', 'aktif'),
(NULL, 'manajer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manajer Umum', 'manajer', '08123456793', 'manajer@apotik.com', 'aktif');

-- Insert Kategori Obat
INSERT INTO kategori_obat (nama_kategori, keterangan) VALUES
('Antibiotik', 'Obat untuk infeksi bakteri'),
('Analgesik', 'Obat pereda nyeri'),
('Antipiretik', 'Obat penurun panas'),
('Vitamin & Suplemen', 'Suplemen kesehatan dan vitamin'),
('Obat Batuk & Flu', 'Obat untuk gejala flu dan batuk'),
('Antasida', 'Obat untuk masalah pencernaan'),
('Antibiotik Topikal', 'Salep antibiotik untuk luka'),
('Antihistamin', 'Obat untuk alergi');

-- Insert Sample Supplier
INSERT INTO supplier (kode_supplier, nama_supplier, alamat, no_telp, email, contact_person, status) VALUES
('SUP001', 'PT. Kimia Farma', 'Jl. Veteran No. 9, Jakarta', '021-3847001', 'info@kimiafarma.co.id', 'Budi Santoso', 'aktif'),
('SUP002', 'PT. Kalbe Farma', 'Jl. Let. Jend. Suprapto, Jakarta', '021-4200888', 'info@kalbe.co.id', 'Dewi Lestari', 'aktif'),
('SUP003', 'PT. Sanbe Farma', 'Jl. Ciwidey, Bandung', '022-5400001', 'info@sanbe.co.id', 'Andi Wijaya', 'aktif'),
('SUP004', 'PT. Tempo Scan Pacific', 'Jl. Industri Raya, Bekasi', '021-8830000', 'info@tempo.co.id', 'Maya Sari', 'aktif');

-- Insert Sample Pelanggan
INSERT INTO pelanggan (no_identitas, nama_pelanggan, jenis_kelamin, tanggal_lahir, alamat, no_telp, email) VALUES
('3201012345670001', 'Ahmad Rizki', 'L', '1985-05-15', 'Jl. Anggrek No. 12, Jakarta', '08111234567', 'ahmad.rizki@email.com'),
('3201012345670002', 'Siti Nurhaliza', 'P', '1990-08-22', 'Jl. Melati No. 45, Jakarta', '08129876543', 'siti.nur@email.com'),
('3201012345670003', 'Budi Hartono', 'L', '1975-12-10', 'Jl. Mawar No. 78, Jakarta', '08139998877', 'budi.h@email.com'),
('3201012345670004', 'Dewi Kusuma', 'P', '1988-03-25', 'Jl. Dahlia No. 23, Jakarta', '08145556666', 'dewi.k@email.com'),
('3201012345670005', 'Eko Prasetyo', 'L', '1995-07-18', 'Jl. Kenanga No. 67, Jakarta', '08156667777', 'eko.p@email.com');

-- Insert Sample Dokter
INSERT INTO dokter (kode_dokter, nama_dokter, spesialis, no_str, no_telp, alamat, status) VALUES
('DOK001', 'dr. Agus Salim, Sp.PD', 'Sp.PD (Penyakit Dalam)', '12345/KKI/2018', '08211234567', 'RS Harapan Kita, Jakarta', 'aktif'),
('DOK002', 'dr. Ratna Sari, Sp.A', 'Sp.A (Anak)', '12346/KKI/2019', '08221234568', 'RS Bunda Jakarta', 'aktif'),
('DOK003', 'dr. Hendra Wijaya, Sp.OG', 'Sp.OG (Kandungan)', '12347/KKI/2017', '08231234569', 'RS Hermina, Jakarta', 'aktif'),
('DOK004', 'dr. Linda Kusuma', 'Umum', '12348/KKI/2020', '08241234570', 'Klinik Sehat Sentosa', 'aktif'),
('DOK005', 'dr. Bambang Sutrisno, Sp.JP', 'Sp.JP (Jantung)', '12349/KKI/2016', '08251234571', 'RS Jantung Jakarta', 'aktif');

-- Insert Sample Obat untuk Apotik 1
INSERT INTO obat (id_apotik, id_kategori, kode_obat, nama_obat, jenis_obat, satuan, harga_beli, harga_jual, margin_persen, aturan_pakai, dosis, efek_samping, golongan, perlu_resep, status) VALUES
-- Apotik Sentosa (id_apotik = 1)
(1, 1, 'OBT00001', 'Amoxicillin 500mg', 'Kapsul', 'Kapsul', 500, 750, 50, 'Diminum 3x sehari sesudah makan', '3x1', 'Mual, diare, alergi', 'keras', 1, 'aktif'),
(1, 2, 'OBT00002', 'Paracetamol 500mg', 'Tablet', 'Tablet', 300, 450, 50, 'Diminum 3-4x sehari sesudah makan', '3x1', 'Jarang, jika berlebihan dapat merusak hati', 'bebas', 0, 'aktif'),
(1, 5, 'OBT00003', 'OBH Combi Batuk Flu', 'Sirup', 'Botol', 15000, 22500, 50, 'Diminum 3x sehari 1 sendok makan', '3x1 sdm', 'Mengantuk', 'bebas', 0, 'aktif'),
(1, 4, 'OBT00004', 'Vitamin C 1000mg', 'Tablet', 'Tablet', 2000, 3000, 50, 'Diminum 1x sehari', '1x1', 'Jarang terjadi', 'bebas', 0, 'aktif'),
(1, 6, 'OBT00005', 'Promag Tablet', 'Tablet', 'Tablet', 400, 600, 50, 'Diminum saat gejala muncul', '3x1', 'Konstipasi ringan', 'bebas', 0, 'aktif'),
(1, 1, 'OBT00006', 'Ciprofloxacin 500mg', 'Tablet', 'Tablet', 2000, 3000, 50, 'Diminum 2x sehari', '2x1', 'Mual, pusing', 'keras', 1, 'aktif'),
(1, 7, 'OBT00007', 'Bioplacenton Gel', 'Gel', 'Tube', 12000, 18000, 50, 'Oleskan pada luka 2-3x sehari', 'Topikal', 'Jarang terjadi', 'bebas', 0, 'aktif'),
(1, 8, 'OBT00008', 'Cetirizine 10mg', 'Tablet', 'Tablet', 500, 750, 50, 'Diminum 1x sehari malam', '1x1', 'Mengantuk', 'keras', 0, 'aktif'),
(1, 2, 'OBT00009', 'Ibuprofen 400mg', 'Tablet', 'Tablet', 700, 1050, 50, 'Diminum 3x sehari sesudah makan', '3x1', 'Sakit perut, mual', 'keras', 0, 'aktif'),
(1, 5, 'OBT00010', 'Komix Herbal', 'Sachet', 'Sachet', 2000, 3000, 50, 'Seduh dengan air panas', '3x1', 'Jarang terjadi', 'bebas', 0, 'aktif'),

-- Apotik Medika (id_apotik = 2)
(2, 1, 'OBT00011', 'Amoxicillin 500mg', 'Kapsul', 'Kapsul', 500, 750, 50, 'Diminum 3x sehari sesudah makan', '3x1', 'Mual, diare, alergi', 'keras', 1, 'aktif'),
(2, 2, 'OBT00012', 'Paracetamol 500mg', 'Tablet', 'Tablet', 300, 450, 50, 'Diminum 3-4x sehari sesudah makan', '3x1', 'Jarang, jika berlebihan dapat merusak hati', 'bebas', 0, 'aktif'),
(2, 4, 'OBT00013', 'Vitamin B Complex', 'Tablet', 'Tablet', 3000, 4500, 50, 'Diminum 1x sehari', '1x1', 'Jarang terjadi', 'bebas', 0, 'aktif'),
(2, 5, 'OBT00014', 'Woods Peppermint', 'Sirup', 'Botol', 18000, 27000, 50, 'Diminum 3x sehari', '3x1 sdm', 'Mengantuk ringan', 'bebas', 0, 'aktif'),
(2, 2, 'OBT00015', 'Asam Mefenamat 500mg', 'Tablet', 'Tablet', 800, 1200, 50, 'Diminum 3x sehari sesudah makan', '3x1', 'Mual, sakit perut', 'keras', 0, 'aktif');

-- Insert Sample Batch Obat dengan variasi expired date
INSERT INTO batch_obat (id_obat, no_batch, tanggal_produksi, tanggal_kadaluarsa, stok_awal, stok_sisa, harga_beli_per_unit, status) VALUES
-- Amoxicillin (Stok normal)
(1, 'BATCH202401', '2024-01-15', '2026-01-15', 500, 450, 500, 'tersedia'),
(1, 'BATCH202402', '2024-02-10', '2026-02-10', 300, 300, 500, 'tersedia'),

-- Paracetamol (Stok banyak)
(2, 'BATCH202403', '2024-03-01', '2027-03-01', 1000, 850, 300, 'tersedia'),
(2, 'BATCH202404', '2024-04-15', '2027-04-15', 800, 800, 300, 'tersedia'),

-- OBH Combi (Expired soon - 20 hari lagi)
(3, 'BATCH202310', '2023-10-01', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 100, 45, 15000, 'tersedia'),
(3, 'BATCH202405', '2024-05-01', '2026-05-01', 150, 150, 15000, 'tersedia'),

-- Vitamin C (Stok normal)
(4, 'BATCH202406', '2024-06-01', '2026-06-01', 500, 380, 2000, 'tersedia'),

-- Promag (Stok menipis)
(5, 'BATCH202407', '2024-07-01', '2026-07-01', 100, 8, 400, 'tersedia'),

-- Ciprofloxacin
(6, 'BATCH202408', '2024-08-01', '2026-08-01', 200, 180, 2000, 'tersedia'),

-- Bioplacenton
(7, 'BATCH202409', '2024-09-01', '2026-09-01', 100, 75, 12000, 'tersedia'),

-- Cetirizine (Expired soon - 15 hari lagi)
(8, 'BATCH202311', '2023-11-01', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 150, 50, 500, 'tersedia'),
(8, 'BATCH202410', '2024-10-01', '2026-10-01', 200, 200, 500, 'tersedia'),

-- Ibuprofen
(9, 'BATCH202411', '2024-11-01', '2026-11-01', 300, 250, 700, 'tersedia'),

-- Komix Herbal (Stok habis)
(10, 'BATCH202312', '2023-12-01', '2025-12-01', 200, 0, 2000, 'habis'),
(10, 'BATCH202412', '2024-12-01', '2026-12-01', 300, 300, 2000, 'tersedia'),

-- Batch untuk Apotik 2
(11, 'BATCH202413', '2024-01-15', '2026-01-15', 400, 350, 500, 'tersedia'),
(12, 'BATCH202414', '2024-03-01', '2027-03-01', 900, 800, 300, 'tersedia'),
(13, 'BATCH202415', '2024-06-01', '2026-06-01', 300, 280, 3000, 'tersedia'),
(14, 'BATCH202416', '2024-05-01', '2026-05-01', 120, 95, 18000, 'tersedia'),
(15, 'BATCH202417', '2024-11-01', '2026-11-01', 250, 220, 800, 'tersedia');

-- Insert Kategori Pengeluaran
INSERT INTO kategori_pengeluaran (nama_kategori, keterangan) VALUES
('Listrik & Air', 'Tagihan utilitas bulanan'),
('Gaji Karyawan', 'Pengeluaran gaji dan tunjangan'),
('Sewa Gedung', 'Biaya sewa tempat usaha'),
('Perlengkapan', 'Alat tulis dan perlengkapan kantor'),
('Transportasi', 'Biaya transportasi operasional'),
('Pemeliharaan', 'Biaya pemeliharaan dan perbaikan'),
('Telekomunikasi', 'Telepon, internet, dll'),
('Lain-lain', 'Pengeluaran operasional lainnya');

-- Insert Sample Pengeluaran
INSERT INTO pengeluaran (id_apotik, id_kategori_pengeluaran, id_user, no_pengeluaran, tanggal_pengeluaran, nama_pengeluaran, jumlah, keterangan) VALUES
(1, 1, 1, 'OUT00001', '2024-10-01', 'Listrik Bulan September', 1500000, 'Pembayaran listrik bulan September 2024'),
(1, 1, 1, 'OUT00002', '2024-10-01', 'Air PDAM Bulan September', 300000, 'Pembayaran air bulan September 2024'),
(1, 2, 1, 'OUT00003', '2024-10-05', 'Gaji Karyawan September', 15000000, 'Gaji 5 karyawan bulan September'),
(1, 3, 1, 'OUT00004', '2024-10-01', 'Sewa Gedung Oktober', 8000000, 'Sewa gedung bulan Oktober 2024'),
(1, 4, 1, 'OUT00005', '2024-10-03', 'Pembelian Alat Tulis', 250000, 'Kertas, pulpen, tinta printer'),
(2, 1, 3, 'OUT00006', '2024-10-01', 'Listrik Bulan September', 1200000, 'Pembayaran listrik bulan September 2024'),
(2, 2, 3, 'OUT00007', '2024-10-05', 'Gaji Karyawan September', 12000000, 'Gaji 4 karyawan bulan September');

-- Insert Sample Resep (2 resep pending, 1 selesai)
INSERT INTO resep (id_apotik, id_dokter, id_pelanggan, no_resep, tanggal_resep, diagnosa, keterangan, status) VALUES
(1, 1, 1, 'RSP000001', '2024-10-01', 'Infeksi Saluran Kemih', 'Kontrol 3 hari lagi', 'selesai'),
(1, 2, 2, 'RSP000002', '2024-10-04', 'Demam dan Flu', 'Istirahat yang cukup', 'pending'),
(1, 4, 3, 'RSP000003', '2024-10-05', 'Alergi Kulit', 'Hindari makanan pemicu alergi', 'pending');

-- Insert Detail Resep
INSERT INTO detail_resep (id_resep, id_obat, qty, aturan_pakai, keterangan) VALUES
-- Resep 1
(1, 6, 10, '2x1 sehari selama 5 hari', 'Harus dihabiskan'),
(1, 2, 15, '3x1 jika demam', 'Diminum sesudah makan'),

-- Resep 2
(2, 2, 10, '3x1 sehari', 'Diminum sesudah makan'),
(2, 3, 1, '3x1 sendok makan', 'Kocok sebelum diminum'),
(2, 4, 30, '1x1 sehari pagi', 'Untuk daya tahan tubuh'),

-- Resep 3
(3, 8, 7, '1x1 malam sebelum tidur', 'Diminum 7 hari'),
(3, 7, 1, 'Oleskan 2-3x sehari', 'Pada area yang gatal');

-- Insert Sample Penjualan (beberapa transaksi)
INSERT INTO penjualan (id_apotik, id_user, id_pelanggan, id_resep, no_transaksi, tanggal_penjualan, tipe_penjualan, total_item, subtotal, diskon, pajak, total_bayar, jumlah_dibayar, kembalian, metode_pembayaran) VALUES
(1, 2, 1, 1, 'TRX00000001', '2024-10-01 10:30:00', 'resep', 2, 11250, 0, 0, 11250, 15000, 3750, 'tunai'),
(1, 2, NULL, NULL, 'TRX00000002', '2024-10-01 14:15:00', 'bebas', 3, 24000, 0, 0, 24000, 24000, 0, 'tunai'),
(1, 2, 2, NULL, 'TRX00000003', '2024-10-02 09:20:00', 'bebas', 2, 23400, 0, 0, 23400, 25000, 1600, 'tunai'),
(1, 2, NULL, NULL, 'TRX00000004', '2024-10-03 16:45:00', 'bebas', 1, 450, 0, 0, 450, 500, 50, 'tunai'),
(1, 2, 3, NULL, 'TRX00000005', '2024-10-04 11:00:00', 'bebas', 4, 28200, 1000, 0, 27200, 30000, 2800, 'tunai'),
(2, 4, NULL, NULL, 'TRX00000006', '2024-10-01 13:30:00', 'bebas', 2, 28500, 0, 0, 28500, 30000, 1500, 'tunai'),
(2, 4, NULL, NULL, 'TRX00000007', '2024-10-02 10:15:00', 'bebas', 1, 450, 0, 0, 450, 500, 50, 'tunai');

-- Insert Detail Penjualan
-- TRX00000001 (Resep 1)
INSERT INTO detail_penjualan (id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal) VALUES
(1, 6, 6, 10, 3000, 0, 3000),
(1, 2, 3, 15, 450, 0, 6750);

-- TRX00000002
INSERT INTO detail_penjualan (id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal) VALUES
(2, 3, 5, 1, 22500, 0, 22500),
(2, 4, 7, 1, 3000, 0, 3000);

-- TRX00000003
INSERT INTO detail_penjualan (id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal) VALUES
(3, 2, 3, 20, 450, 0, 9000),
(3, 7, 9, 1, 18000, 0, 18000);

-- TRX00000004
INSERT INTO detail_penjualan (id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal) VALUES
(4, 2, 3, 1, 450, 0, 450);

-- TRX00000005
INSERT INTO detail_penjualan (id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal) VALUES
(5, 2, 3, 10, 450, 0, 4500),
(5, 3, 5, 1, 22500, 0, 22500),
(5, 8, 10, 1, 750, 0, 750),
(5, 5, 8, 1, 600, 0, 600);

-- TRX00000006 (Apotik 2)
INSERT INTO detail_penjualan (id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal) VALUES
(6, 11, 16, 10, 750, 0, 7500),
(6, 14, 19, 1, 27000, 0, 27000);

-- TRX00000007 (Apotik 2)
INSERT INTO detail_penjualan (id_penjualan, id_obat, id_batch, qty, harga_jual, diskon, subtotal) VALUES
(7, 12, 17, 1, 450, 0, 450);

-- ========================================
-- SUMMARY DATA
-- ========================================
-- Total Apotik: 3
-- Total Users: 5 (1 manajer, 2 admin, 2 kasir)
-- Total Obat: 15 (10 di Apotik 1, 5 di Apotik 2)
-- Total Batch: 20
-- Total Pelanggan: 5
-- Total Dokter: 5
-- Total Supplier: 4
-- Total Resep: 3 (1 selesai, 2 pending)
-- Total Penjualan: 7 transaksi
-- Total Pengeluaran: 7 item

-- NOTES:
-- 1. Ada obat yang expired soon (15-20 hari) untuk testing alert
-- 2. Ada stok menipis (Promag: 8 unit) untuk testing warning
-- 3. Ada stok habis (Komix batch lama) untuk testing FEFO
-- 4. Password semua user: admin123
-- 5. Margin obat default 50%

SELECT 'Seed data berhasil diimport!' as Status;
SELECT '✓ Apotik: 3' as Info UNION ALL
SELECT '✓ Users: 5 (password: admin123)' UNION ALL
SELECT '✓ Obat: 15 dengan batch' UNION ALL
SELECT '✓ Pelanggan: 5' UNION ALL
SELECT '✓ Dokter: 5' UNION ALL
SELECT '✓ Supplier: 4' UNION ALL
SELECT '✓ Transaksi: 7' UNION ALL
SELECT '✓ Resep: 3 (2 pending)' UNION ALL
SELECT '' UNION ALL
SELECT 'Login dengan:' UNION ALL
SELECT 'Admin: admin / admin123' UNION ALL
SELECT 'Kasir: kasir1 / admin123' UNION ALL
SELECT 'Manajer: manajer / admin123';