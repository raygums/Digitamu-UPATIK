-- =====================================================
-- SCHEMA UPDATE - DigiTamu UPA TIK
-- Tambahan tabel untuk fitur Janji Temu & Peminjaman
-- =====================================================

-- Tabel Fasilitas
CREATE TABLE IF NOT EXISTS fasilitas (
    id_fasilitas SERIAL PRIMARY KEY,
    nama_fasilitas VARCHAR(150) NOT NULL,
    jenis VARCHAR(50),
    deskripsi TEXT,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

COMMENT ON TABLE fasilitas IS 'Master data fasilitas yang dapat dipinjam';
COMMENT ON COLUMN fasilitas.jenis IS 'Tipe: ruangan, peralatan, dll';
COMMENT ON COLUMN fasilitas.is_available IS 'Status ketersediaan fasilitas';

-- Tabel Janji Temu
CREATE TABLE IF NOT EXISTS janji_temu (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    id_unit_kerja INTEGER REFERENCES unit_kerja(id) ON DELETE SET NULL,
    tanggal_waktu TIMESTAMP NOT NULL,
    topik_diskusi TEXT,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'disetujui', 'ditolak')),
    catatan_staff TEXT,
    diproses_oleh INTEGER REFERENCES users(id) ON DELETE SET NULL,
    tanggal_diproses TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE janji_temu IS 'Data janji temu online dengan staf/unit';
COMMENT ON COLUMN janji_temu.status IS 'Status: pending, disetujui, ditolak';
COMMENT ON COLUMN janji_temu.diproses_oleh IS 'User ID staff yang memproses';

-- Index untuk performa query janji_temu
CREATE INDEX idx_janji_temu_status ON janji_temu(status);
CREATE INDEX idx_janji_temu_tanggal ON janji_temu(tanggal_waktu);
CREATE INDEX idx_janji_temu_unit ON janji_temu(id_unit_kerja);

-- Tabel Peminjaman Fasilitas
CREATE TABLE IF NOT EXISTS peminjaman (
    id SERIAL PRIMARY KEY,
    nama_peminjam VARCHAR(100) NOT NULL,
    instansi VARCHAR(150),
    id_fasilitas INTEGER REFERENCES fasilitas(id) ON DELETE SET NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    surat_pengantar_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'disetujui', 'ditolak')),
    catatan_staff TEXT,
    diproses_oleh INTEGER REFERENCES users(id) ON DELETE SET NULL,
    tanggal_diproses TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

COMMENT ON TABLE peminjaman IS 'Data peminjaman fasilitas TIK';
COMMENT ON COLUMN peminjaman.surat_pengantar_path IS 'Path file surat pengantar';
COMMENT ON COLUMN peminjaman.status IS 'Status: pending, disetujui, ditolak';

-- Index untuk performa query peminjaman
CREATE INDEX idx_peminjaman_status ON peminjaman(status);
CREATE INDEX idx_peminjaman_tanggal_mulai ON peminjaman(tanggal_mulai);
CREATE INDEX idx_peminjaman_tanggal_selesai ON peminjaman(tanggal_selesai);
CREATE INDEX idx_peminjaman_fasilitas ON peminjaman(id_fasilitas);

-- =====================================================
-- DATA AWAL FASILITAS (OPSIONAL)
-- =====================================================

INSERT INTO fasilitas (nama_fasilitas, jenis, deskripsi, is_available) VALUES
('Lab Komputer 1', 'ruangan', 'Kapasitas 40 orang, PC 40 unit, AC, Proyektor', TRUE),
('Lab Komputer 2', 'ruangan', 'Kapasitas 30 orang, PC 30 unit, AC, Proyektor', TRUE),
('Lab Multimedia', 'ruangan', 'Kapasitas 20 orang, PC editing, Kamera, Proyektor', TRUE),
('Lab Jaringan', 'ruangan', 'Kapasitas 25 orang, Perangkat jaringan lengkap', TRUE),
('Ruang Rapat TIK', 'ruangan', 'Kapasitas 15 orang, Meja meeting, Proyektor, AC', TRUE),
('Aula TIK', 'ruangan', 'Kapasitas 100 orang, Sound system, Proyektor', TRUE),
('Proyektor Portable 1', 'peralatan', 'Proyektor EPSON EB-S41, 3300 lumens', TRUE),
('Proyektor Portable 2', 'peralatan', 'Proyektor BenQ MH530, 3300 lumens', TRUE),
('Kamera DSLR Canon', 'peralatan', 'Canon EOS 80D + Lensa 18-135mm', TRUE),
('Kamera DSLR Nikon', 'peralatan', 'Nikon D7500 + Lensa 18-140mm', TRUE),
('Tripod Professional', 'peralatan', 'Tripod Manfrotto 190X, max height 170cm', TRUE),
('Microphone Wireless', 'peralatan', 'Shure BLX288/PG58, 2 mic handheld', TRUE),
('Laptop Cadangan 1', 'peralatan', 'Lenovo ThinkPad, i5, RAM 8GB, SSD 256GB', TRUE),
('Laptop Cadangan 2', 'peralatan', 'ASUS VivoBook, i5, RAM 8GB, SSD 512GB', TRUE),
('Speaker Portable', 'peralatan', 'JBL Partybox 100, Bluetooth, 160W', TRUE)
ON CONFLICT DO NOTHING;

-- =====================================================
-- SELESAI
-- =====================================================
-- Total tabel ditambahkan: 3 (fasilitas, janji_temu, peminjaman)
-- Total index ditambahkan: 7
-- Sample data fasilitas: 15 item
-- =====================================================
