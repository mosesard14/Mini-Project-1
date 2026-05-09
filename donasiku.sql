-- ============================================================
--  DonasiKu Database  v2
--  mysql -u root -p < donasiku.sql
--  Semua password seed => "password"
-- ============================================================

CREATE DATABASE IF NOT EXISTS donasiku_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE donasiku_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS donasi;
DROP TABLE IF EXISTS kampanye;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USERS
CREATE TABLE users (
  id            INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(80)      NOT NULL UNIQUE,
  password      VARCHAR(255)     NOT NULL,
  email         VARCHAR(160)     NOT NULL UNIQUE,
  telepon       VARCHAR(20)      DEFAULT NULL,
  role          ENUM('donatur','pengelola') NOT NULL DEFAULT 'donatur',
  nama_lengkap  VARCHAR(120)     DEFAULT NULL,
  nama_org      VARCHAR(160)     DEFAULT NULL,
  alamat        TEXT             DEFAULT NULL,
  foto_profil   VARCHAR(255)     NOT NULL DEFAULT 'assets/avatar1.jpeg',
  created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. KAMPANYE
CREATE TABLE kampanye (
  id            INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  pengelola_id  INT UNSIGNED     NOT NULL,
  judul         VARCHAR(255)     NOT NULL,
  cerita        TEXT             NOT NULL,
  kategori      ENUM('Bencana','Pendidikan','Kesehatan','FasilitasUmum') NOT NULL,
  lokasi        VARCHAR(160)     NOT NULL,
  target_dana   BIGINT UNSIGNED  NOT NULL DEFAULT 0,
  terkumpul     BIGINT UNSIGNED  NOT NULL DEFAULT 0,
  deadline      DATE             NOT NULL,
  foto_path     VARCHAR(255)     NOT NULL DEFAULT 'assets/contoh1.jpg',
  metode_json   JSON             DEFAULT NULL,
  no_rekening   VARCHAR(120)     DEFAULT NULL,
  no_ewallet    VARCHAR(120)     DEFAULT NULL,
  no_btc        VARCHAR(200)     DEFAULT NULL,
  qris_path     VARCHAR(255)     DEFAULT NULL,
  status        ENUM('aktif','selesai','ditutup') NOT NULL DEFAULT 'aktif',
  created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (pengelola_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. DONASI
CREATE TABLE donasi (
  id            INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  kampanye_id   INT UNSIGNED     NOT NULL,
  donatur_id    INT UNSIGNED     NOT NULL,
  nominal       BIGINT UNSIGNED  NOT NULL,
  metode        VARCHAR(30)      NOT NULL,
  pesan         TEXT             DEFAULT NULL,
  bukti_path    VARCHAR(255)     DEFAULT NULL,
  status        ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (kampanye_id) REFERENCES kampanye(id) ON DELETE CASCADE,
  FOREIGN KEY (donatur_id)  REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- SEED USERS
INSERT INTO users (username,password,email,telepon,role,nama_lengkap,nama_org,alamat,foto_profil) VALUES
('pengelola1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pengelola1@donasiku.id','0811-1111-1111','pengelola',NULL,'Yayasan Peduli Nusantara','Jl. Merdeka No.1, Jakarta Pusat','assets/avatar1.jpeg'),
('pengelola2','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','pengelola2@donasiku.id','0822-2222-2222','pengelola',NULL,'Rumah Peduli Indonesia','Jl. Sudirman No.5, Bandung','assets/avatar2.jpeg'),
('donatur1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','donatur1@gmail.com','0833-3333-3333','donatur','Budi Santoso',NULL,NULL,'assets/avatar2.jpeg'),
('donatur2','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','donatur2@gmail.com','0844-4444-4444','donatur','Siti Rahayu',NULL,NULL,'assets/avatar3.jpeg'),
('donatur3','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','donatur3@gmail.com','0855-5555-5555','donatur','Dewi Kartika',NULL,NULL,'assets/avatar1.jpeg');

-- SEED KAMPANYE
INSERT INTO kampanye (pengelola_id,judul,cerita,kategori,lokasi,target_dana,terkumpul,deadline,foto_path,metode_json,no_rekening,no_ewallet,no_btc,qris_path) VALUES
(1,'Bencana Banjir Aceh & Sumatra Utara','Dampak dari kerusakan tersebut membuat sejumlah desa di Kecamatan Ketol kembali terisolasi. Petugas mengerahkan alat berat untuk mempercepat pembangunan jembatan darurat. Selain Aceh Tengah, banjir juga melanda Kabupaten Gayo Lues. Ketinggian air setinggi lutut orang dewasa.','Bencana','Aceh, Sumatra Utara',500000000,243276519,'2026-06-27','assets/contoh1.jpg','["QRIS","Rekening","E-Wallet"]','1234-5678-9012 (BRI a.n. Yayasan Peduli Nusantara)','0811-1111-1111 (DANA)',NULL,'assets/QRIS.jpg'),
(1,'Hadapi Banjir, PMI Dorong Bantuan ke 5 Wilayah','PMI Pusat mendorong bantuan logistik dan medis ke lima wilayah terdampak banjir di pesisir Sumatra. Ratusan relawan disiagakan untuk membantu evakuasi dan distribusi sembako kepada warga yang terisolir.','Bencana','Aceh, Sumatra Utara',300000000,110800000,'2026-06-27','assets/contoh2.jpg','["QRIS","Rekening"]','9876-5432-1000 (BCA a.n. PMI Pusat)',NULL,NULL,'assets/QRIS.jpg'),
(2,'Sekolah Siswa Kurang dari 60 Orang Tidak Dapat Bantuan','Kebijakan pemerintah yang mensyaratkan minimal 60 siswa membuat ratusan sekolah kecil di pedesaan Jawa Tengah kehilangan dana BOS. Kami menggalang dana untuk subsidi operasional sekolah-sekolah terpencil tersebut.','Pendidikan','Jawa Tengah',200000000,85400000,'2026-08-01','assets/contoh3.jpg','["Rekening","E-Wallet","BTC"]','1111-2222-3333 (Mandiri a.n. Rumah Peduli Indonesia)','0822-2222-2222 (OVO)','bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',NULL),
(2,'Bantuan Air Bersih untuk Warga Terdampak Kekeringan','Kekeringan panjang melanda beberapa kabupaten di Jawa Barat. Dana digunakan untuk pengadaan tangki air, instalasi sumur bor, dan distribusi air bersih ke daerah terpencil.','Kesehatan','Jawa Barat',400000000,192500000,'2026-07-15','assets/contoh1.jpg','["QRIS","Rekening"]','5555-6666-7777 (BNI a.n. Rumah Peduli Indonesia)',NULL,NULL,'assets/QRIS.jpg'),
(1,'Penyakit Kulit Intai Warga Terdampak Banjir Aceh','Pasca banjir, penyakit kulit meningkat drastis akibat minimnya sanitasi. Donasi untuk pengadaan obat-obatan, sabun antiseptik, dan layanan dokter keliling ke daerah terdampak.','Kesehatan','Sumatra Barat',150000000,63200000,'2026-07-30','assets/contoh1.jpg','["QRIS","Rekening","E-Wallet"]','2222-3333-4444 (BRI a.n. Yayasan Peduli Nusantara)','0811-9999-9999 (GoPay)',NULL,'assets/QRIS.jpg'),
(2,'Akses Jalan 2 Desa di Murung Raya Rusak Parah','Dua desa di Kalimantan Selatan terisolir akibat kerusakan jalan 12 km. Donasi untuk perbaikan jalan darurat dan gorong-gorong.','FasilitasUmum','Kalimantan Selatan',500000000,155000000,'2026-09-20','assets/contoh1.jpg','["Rekening","BTC"]','8888-9999-0000 (BCA a.n. Rumah Peduli Indonesia)',NULL,'bc1qar0srrr7xfkvy5l643lydnw9re59gtzzwf5mdq',NULL),
(1,'Jembatan di Ogan Ilir Putus Diterjang Arus','Jembatan utama penghubung dua kecamatan di Ogan Ilir, Sumatra Selatan, putus. Warga harus memutar 40 km. Dana untuk pembangunan jembatan darurat.','FasilitasUmum','Sumatra Selatan',600000000,210000000,'2026-10-05','assets/contoh1.jpg','["QRIS","Rekening"]','3333-4444-5555 (Mandiri a.n. Yayasan Peduli Nusantara)',NULL,NULL,'assets/QRIS.jpg');

-- SEED DONASI
INSERT INTO donasi (kampanye_id,donatur_id,nominal,metode,pesan,bukti_path,status) VALUES
(1,3,500000,'Rekening','Semoga cepat pulih ya!','uploads/bukti/sample.jpg','verified'),
(1,4,250000,'QRIS','Ikut berdoa untuk korban','uploads/bukti/sample.jpg','pending'),
(1,5,1000000,'E-Wallet','','uploads/bukti/sample.jpg','pending'),
(1,3,100000,'Rekening','Semangat para relawan!','uploads/bukti/sample.jpg','rejected'),
(2,4,200000,'QRIS','Bismillah semoga bermanfaat','uploads/bukti/sample.jpg','verified'),
(2,5,300000,'Rekening','','uploads/bukti/sample.jpg','pending'),
(3,3,150000,'E-Wallet','Untuk adik-adik yang butuh ilmu','uploads/bukti/sample.jpg','verified'),
(4,4,500000,'Rekening','Semoga airnya cukup','uploads/bukti/sample.jpg','pending');
