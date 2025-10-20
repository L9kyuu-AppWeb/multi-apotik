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

-- TRIGGER 6: Update Total Pembelian
DELIMITER $$
CREATE TRIGGER trg_after_insert_detail_pembelian_total
AFTER INSERT ON detail_pembelian
FOR EACH ROW
BEGIN
    UPDATE pembelian
    SET total_item = total_item + 1,
        subtotal = subtotal + NEW.subtotal,
        total_bayar = (subtotal + NEW.subtotal) - diskon + pajak
    WHERE id_pembelian = NEW.id_pembelian;
END$$
DELIMITER ;

-- TRIGGER 7: Update Total Penjualan
DELIMITER $$
CREATE TRIGGER trg_after_insert_detail_penjualan_total
AFTER INSERT ON detail_penjualan
FOR EACH ROW
BEGIN
    UPDATE penjualan
    SET total_item = total_item + 1,
        subtotal = subtotal + NEW.subtotal,
        total_bayar = (subtotal + NEW.subtotal) - diskon + pajak
    WHERE id_penjualan = NEW.id_penjualan;
END$$
DELIMITER ;

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
-- DATA AWAL (SEED DATA)
-- ========================================

-- Insert Kategori Obat
INSERT INTO kategori_obat (nama_kategori, keterangan) VALUES
('Antibiotik', 'Obat untuk infeksi bakteri'),
('Analgesik', 'Obat pereda nyeri'),
('Antipiretik', 'Obat penurun panas'),
('Vitamin & Suplemen', 'Suplemen kesehatan'),
('Obat Batuk & Flu', 'Obat untuk gejala flu dan batuk');

-- Insert Kategori Pengeluaran
INSERT INTO kategori_pengeluaran (nama_kategori, keterangan) VALUES
('Listrik & Air', 'Tagihan utilitas'),
('Gaji Karyawan', 'Pengeluaran gaji bulanan'),
('Sewa Gedung', 'Biaya sewa tempat'),
('Perlengkapan', 'Alat tulis dan perlengkapan'),
('Lain-lain', 'Pengeluaran operasional lainnya');

-- Insert Sample Apotik
INSERT INTO apotik (kode_apotik, nama_apotik, alamat, no_telp, email) VALUES
('APT001', 'Apotik Sehat Sentosa', 'Jl. Merdeka No. 123, Jakarta', '021-1234567', 'sentosa@apotik.com'),
('APT002', 'Apotik Medika Farma', 'Jl. Ahmad Yani No. 45, Bandung', '022-7654321', 'medika@apotik.com');

-- Insert Sample User (password: admin123)
INSERT INTO users (id_apotik, username, password, nama_lengkap, role, no_telp, email) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', '08123456789', 'admin@apotik.com'),
(1, 'kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Apotik 1', 'kasir', '08123456790', 'kasir1@apotik.com'),
(2, 'kasir2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Apotik 2', 'kasir', '08123456791', 'kasir2@apotik.com'),
(NULL, 'manajer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Manajer Umum', 'manajer', '08123456792', 'manajer@apotik.com');