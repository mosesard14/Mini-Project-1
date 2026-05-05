<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda Donasi</title>
    <link rel="stylesheet" href="../style/main.css">
</head>
<body>

    <div class="container">
        <header class="banner">
                <div class="banner-content">
                    <h1>Buka Donasi,<br>Beri Donasi</h1>
                    <button class="btn-main-donasi">+ Buka Donasi</button>
                </div>
                <div class="banner-overlay"></div>
            </header>

            <section class="search-section">
                <br><br>
                <label>Cari Kampanye</label>
                <div class="search-bar">
                    <input type="text" placeholder="">
                    <button class="btn-search">Cari 🔍</button>
                </div>
            </section>

            <div class="filter-wrapper">
        
                <input type="radio" id="filter-semua" name="kategori" checked>
                <input type="radio" id="filter-bencana" name="kategori">
                <input type="radio" id="filter-pendidikan" name="kategori">
                <input type="radio" id="filter-kesehatan" name="kategori">
                <input type="radio" id="filter-fasilitas" name="kategori">
                
                <section class="content-group">
                    <h3>Temukan Kampanye</h3>

                    <div class="form-group">
                        <label for="lokasi">Lokasi</label>
                        <select id="lokasi">
                            <option value="">Semua Lokasi</option>
                            <option value="aceh">Aceh</option>
                            <option value="sumut">Sumatera Utara</option>
                            <option value="jakarta">DKI Jakarta</option>
                            <option value="papua">Yogyakarta</option>
                            <option value="papua">Jawa Barat</option>
                            <option value="papua">Kalimantan Selatan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="rentang_dana">Target Dana</label>
                        <select id="rentang_dana">
                            <option value="">Semua Rentang</option>
                            <option value="under_50">< Rp 50 Juta</option>
                            <option value="50_to_100">Rp 50 Juta - Rp 100 Juta</option>
                            <option value="100_to_500">Rp 100 Juta - Rp 500 Juta</option>
                            <option value="above_500">> Rp 500 Juta</option>
                        </select>
                    </div>

                    <div class="filter-controls">
                        <label for="filter-semua">Semua</label>
                        <label for="filter-bencana">Bencana Alam</label>
                        <label for="filter-pendidikan">Pendidikan</label>
                        <label for="filter-kesehatan">Kesehatan</label>
                        <label for="filter-fasilitas">Fasilitas Umum</label>
                    </div>

                <div class="card-grid">
                    <div class="card item-bencana">
                        <div class="card-img" style="background-image: url('../assets/contoh1.jpg');">
                            <span class="tag">#Bencana</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>
                        </div>
                        <div class="card-footer">
                            <p>Bencana Banjir menimpa Aceh, Sumatra Utara, dan Sumatra Barat</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                            
                        </div>
                    </div>
                    <div class="card item-bencana">
                        <div class="card-img" style="background-image: url('../assets/contoh2.jpg');">
                            <span class="tag">#Bencana</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>
                        </div>
                        <div class="card-footer">
                            <p>Hadapi Banjir, PMI Pusat Dorong Bantuan ke 5 Wilayah</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                        </div>
                    </div>
                    <div class="card item-pendidikan">
                        <div class="card-img" style="background-image: url('../assets/contoh3.jpg');">
                            <span class="tag">#Pendidikan</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>                   
                        </div>
                        <div class="card-footer">
                            <p>Sekolah dengan Siswa dibawah 60 Orang Tidak Mendapatkan Bantuan dari Pusat</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                        </div>
                    </div>
                    <div class="card item-kesehatan">
                        <div class="card-img" style="background-image: url('../assets/contoh1.jpg');">
                            <span class="tag">#KesehatanLingkungan</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>                   
                        </div>
                        <div class="card-footer">
                            <p>Bantuan Air bersih yang Terdampak Kekeringan</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                        </div>
                    </div>
                    <div class="card item-kesehatan">
                        <div class="card-img" style="background-image: url('../assets/contoh1.jpg');">
                            <span class="tag">#KesehatanLingkungan</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>                   
                        </div>
                        <div class="card-footer">
                            <p>Penyakit Kulit Intai Warga Terdampak Banjir Aceh-Sumatera</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                        </div>
                    </div>
                    <div class="card item-pendidikan">
                        <div class="card-img" style="background-image: url('../assets/contoh3.jpg');">
                            <span class="tag">#Pendidikan</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>                   
                        </div>
                        <div class="card-footer">
                            <p>Penyebab Siswa Miskin dan Rentan Miskin Gagal Memeroleh PIP</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                        </div>
                    </div>
                    <div class="card item-fasilitas">
                        <div class="card-img" style="background-image: url('../assets/contoh1.jpg');">
                            <span class="tag">#FasilitasUmum</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>                   
                        </div>
                        <div class="card-footer">
                            <p>Akses Jalan 2 Desa di Mura Rusak Parah, warga kesulitan</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                        </div>
                    </div>
                    <div class="card item-fasilitas">
                        <div class="card-img" style="background-image: url('../assets/contoh1.jpg');">
                            <span class="tag">#FasilitasUmum</span>
                            <span class="lokasi">Aceh, Sumatera Utara</span>
                            <a href="detailDonasi.html" class="btn-card-donasi">Detail</a>                   
                        </div>
                        <div class="card-footer">
                            <p>Jembatan di Ogan Ilir Putus Diterjang Arus</p>
                            <hr class="card-line">
                            <span class="label-stat">Terkumpul : </span>
                            <span class="value-terkumpul">Rp 243.276.519</span>
                            <br class="br-card">
                            <span class="label-dari">Dari : </span>
                            <span class="value-dari">Rp 500.000.000</span>
                            <p class="deadline">27/06/2026</p>
                        </div>
                    </div>
                </div>
                </section>
            </div>
        </div>
    </div>

<footer class="footer">
    <p>&copy; 2026 moses hervian listyan. ini cuman footer doang.</p>
</footer>
</body>
</html>