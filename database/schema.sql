-- =============================================
-- DigiTamu TIK - Database Schema (PostgreSQL)
-- =============================================

-- Drop existing tables if needed (untuk fresh install)
-- DROP TABLE IF EXISTS kunjungan, janji_temu, peminjaman, tamu, users, ref_keperluan, ref_unit_kerja, ref_fasilitas, ref_divisi CASCADE;

-- =============================================
-- ENUM TYPES
-- =============================================
DO $$ BEGIN
    CREATE TYPE user_role AS ENUM ('admin', 'staff');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE status_permohonan AS ENUM ('pending', 'disetujui', 'ditolak');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

DO $$ BEGIN
    CREATE TYPE status_kunjungan AS ENUM ('di_ruangan', 'selesai');
EXCEPTION
    WHEN duplicate_object THEN null;
END $$;

-- =============================================
-- 1. Tabel Users (Admin & Staff)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'staff',
    foto_path VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

CREATE INDEX IF NOT EXISTS idx_users_deleted_at ON users(deleted_at);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- =============================================
-- 2. Tabel Referensi Unit Kerja / Instansi
-- =============================================
CREATE TABLE IF NOT EXISTS ref_unit_kerja (
    id SERIAL PRIMARY KEY,
    nama_unit VARCHAR(150) NOT NULL,
    kode_unit VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- =============================================
-- 3. Tabel Referensi Keperluan Kunjungan
-- =============================================
CREATE TABLE IF NOT EXISTS ref_keperluan (
    id SERIAL PRIMARY KEY,
    nama_keperluan VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- =============================================
-- 4. Tabel Referensi Divisi/Staff (untuk Janji Temu)
-- =============================================
CREATE TABLE IF NOT EXISTS ref_divisi (
    id SERIAL PRIMARY KEY,
    nama_divisi VARCHAR(150) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- =============================================
-- 5. Tabel Referensi Fasilitas (untuk Peminjaman)
-- =============================================
CREATE TABLE IF NOT EXISTS ref_fasilitas (
    id SERIAL PRIMARY KEY,
    nama_fasilitas VARCHAR(150) NOT NULL,
    jenis VARCHAR(50), -- 'ruangan', 'peralatan'
    deskripsi TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- =============================================
-- 6. Tabel Tamu
-- =============================================
CREATE TABLE IF NOT EXISTS tamu (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    no_hp VARCHAR(20),
    instansi VARCHAR(150),
    tipe VARCHAR(20) DEFAULT 'eksternal', -- 'internal' atau 'eksternal'
    foto VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tamu_nama ON tamu(nama);

-- =============================================
-- 7. Tabel Kunjungan (Buku Tamu On-Site)
-- =============================================
CREATE TABLE IF NOT EXISTS kunjungan (
    id SERIAL PRIMARY KEY,
    id_tamu INTEGER NOT NULL REFERENCES tamu(id) ON DELETE CASCADE,
    id_keperluan INTEGER REFERENCES ref_keperluan(id) ON DELETE SET NULL,
    detail_keperluan TEXT,
    alasan_kunjungan TEXT,
    waktu_masuk TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waktu_keluar TIMESTAMP NULL,
    status status_kunjungan DEFAULT 'di_ruangan',
    staff_checkout INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_kunjungan_waktu ON kunjungan(waktu_masuk);
CREATE INDEX IF NOT EXISTS idx_kunjungan_status ON kunjungan(status);

-- =============================================
-- 8. Tabel Janji Temu (Online Appointment)
-- =============================================
CREATE TABLE IF NOT EXISTS janji_temu (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    id_divisi INTEGER REFERENCES ref_divisi(id) ON DELETE SET NULL,
    tanggal_waktu TIMESTAMP NOT NULL,
    topik_diskusi TEXT,
    status status_permohonan DEFAULT 'pending',
    catatan_staff TEXT,
    diproses_oleh INTEGER REFERENCES users(id) ON DELETE SET NULL,
    tanggal_diproses TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_janji_temu_status ON janji_temu(status);
CREATE INDEX IF NOT EXISTS idx_janji_temu_tanggal ON janji_temu(tanggal_waktu);

-- =============================================
-- 9. Tabel Peminjaman Fasilitas
-- =============================================
CREATE TABLE IF NOT EXISTS peminjaman (
    id SERIAL PRIMARY KEY,
    nama_peminjam VARCHAR(100) NOT NULL,
    instansi VARCHAR(150),
    id_fasilitas INTEGER REFERENCES ref_fasilitas(id) ON DELETE SET NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    surat_pengantar_path VARCHAR(255),
    status status_permohonan DEFAULT 'pending',
    catatan_staff TEXT,
    diproses_oleh INTEGER REFERENCES users(id) ON DELETE SET NULL,
    tanggal_diproses TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_peminjaman_status ON peminjaman(status);
CREATE INDEX IF NOT EXISTS idx_peminjaman_tanggal ON peminjaman(tanggal_mulai);

-- =============================================
-- DATA AWAL (SEEDER)
-- =============================================

-- Insert Admin & Staff Default (password: 'password')
INSERT INTO users (nama, email, password, role) VALUES
('Kepala UPA TIK', 'admin@tik.unila.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Staff TIK', 'staff@tik.unila.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Petugas Front Office', 'frontoffice@tik.unila.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff')
ON CONFLICT (email) DO NOTHING;

-- Insert Unit Kerja
INSERT INTO ref_unit_kerja (nama_unit, kode_unit) VALUES
('Rektorat', 'REK'),
('Fakultas Teknik', 'FT'),
('Fakultas MIPA', 'FMIPA'),
('Fakultas Ekonomi dan Bisnis', 'FEB'),
('Fakultas Hukum', 'FH'),
('Fakultas Pertanian', 'FP'),
('Fakultas Keguruan dan Ilmu Pendidikan', 'FKIP'),
('Fakultas Ilmu Sosial dan Ilmu Politik', 'FISIP'),
('Fakultas Kedokteran', 'FK'),
('UPA TIK', 'TIK'),
('Mahasiswa', 'MHS'),
('Umum', 'UMM')
ON CONFLICT DO NOTHING;

-- Insert Keperluan Kunjungan
INSERT INTO ref_keperluan (nama_keperluan, deskripsi) VALUES
('Reset Password SSO', 'Permintaan reset password Single Sign-On'),
('Layanan Email', 'Bantuan terkait email institusi'),
('Konsultasi Jaringan', 'Konsultasi masalah jaringan'),
('Layanan Server', 'Permintaan terkait server'),
('Kunjungan Data Center', 'Akses ke ruang server/data center'),
('Lainnya', 'Keperluan lain')
ON CONFLICT DO NOTHING;

-- Insert Divisi (untuk Janji Temu)
INSERT INTO ref_divisi (nama_divisi, deskripsi) VALUES
('Kepala UPA TIK', 'Pimpinan UPA TIK'),
('Divisi Infrastruktur', 'Jaringan dan Data Center'),
('Divisi Pengembangan Sistem', 'Aplikasi dan Website'),
('Divisi Layanan', 'Helpdesk dan Support'),
('Administrasi', 'Bagian Administrasi')
ON CONFLICT DO NOTHING;

-- Insert Fasilitas (untuk Peminjaman)
INSERT INTO ref_fasilitas (nama_fasilitas, jenis, deskripsi) VALUES
('Lab Komputer 1', 'ruangan', 'Kapasitas 40 orang'),
('Lab Komputer 2', 'ruangan', 'Kapasitas 30 orang'),
('Lab Multimedia', 'ruangan', 'Kapasitas 20 orang'),
('Ruang Rapat TIK', 'ruangan', 'Kapasitas 15 orang'),
('Proyektor Portable', 'peralatan', 'Proyektor untuk presentasi'),
('Kamera DSLR', 'peralatan', 'Untuk dokumentasi'),
('Laptop Cadangan', 'peralatan', 'Laptop untuk kegiatan')
ON CONFLICT DO NOTHING;
