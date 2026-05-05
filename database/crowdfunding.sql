CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL
);

CREATE TABLE penyelenggara (
    id_penyelenggara INT PRIMARY KEY AUTO_INCREMENT,
    nama_kantor VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_telepon VARCHAR(15),
    alamat TEXT
);

CREATE TABLE donatur (
    id_donatur INT PRIMARY KEY AUTO_INCREMENT,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_telepon VARCHAR(15)
);

CREATE TABLE kampanye (
    id_kampanye INT PRIMARY KEY AUTO_INCREMENT,
    id_penyelenggara INT,
    judul_kampanye VARCHAR(255) NOT NULL,
    kategori VARCHAR(50),
    lokasi VARCHAR(100),
    target_dana DECIMAL(15, 2) NOT NULL,
    dana_terkumpul DECIMAL(15, 2) DEFAULT 0,
    batas_waktu DATE NOT NULL,
    gambar_poster VARCHAR(255),
    deskripsi TEXT,
    FOREIGN KEY (id_penyelenggara) REFERENCES penyelenggara(id_penyelenggara) ON DELETE CASCADE
);

CREATE TABLE donasi (
    id_donasi INT PRIMARY KEY AUTO_INCREMENT,
    id_kampanye INT,
    id_donatur INT,
    nominal_donasi DECIMAL(15, 2) NOT NULL CHECK (nominal_donasi >= 10000),
    metode_pembayaran VARCHAR(50),
    pesan_dukungan TEXT,
    bukti_transfer VARCHAR(255),
    status ENUM('PENDING', 'VERIFIED', 'REJECTED') DEFAULT 'PENDING',
    tgl_donasi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kampanye) REFERENCES kampanye(id_kampanye),
    FOREIGN KEY (id_donatur) REFERENCES donatur(id_donatur)
);