<?php
// admin/mahasiswa/index.php
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
$token = generate_csrf_token();
$stmt_dosen = $pdo->query("SELECT id_dosen, nama_dosen FROM dosen ORDER BY nama_dosen ASC");
$data_dosen = $stmt_dosen->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700;800&display=swap" rel="stylesheet">
<div class="container-fluid py-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-navy mb-1"><i class="fa-solid fa-address-book me-2"></i>Direktori Data Mahasiswa</h3>
            <p class="text-secondary small mb-0">Kelola informasi profil, indeks prestasi (IPK), berkas foto, dan otentikasi login mahasiswa.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" id="btn-hapus-massal" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm fw-semibold d-none">
                <i class="fa-solid fa-trash-can me-1"></i> Hapus Terpilih (<span id="jumlah-terpilih">0</span>)
            </button>
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalImportCSVMahasiswa">
                <i class="fa-solid fa-file-excel me-1"></i> Impor via CSV
            </button>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 bg-navy border-0 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTambahMahasiswa">
                <i class="fa-solid fa-user-plus me-1"></i> Tambah Mahasiswa Baru
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: #ffffff;">
        <div class="row g-3 align-items-center">
            <div class="col-12">
                <form id="form-filter-mhs" class="row g-2" onsubmit="return false;">

                    <div class="col-sm-7 position-relative">
                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary opacity-50">
                            <i class="fa-solid fa-magnifying-glass small"></i>
                        </span>
                        <input type="text" id="search-mhs" class="form-control rounded-pill ps-5 py-2.5 bg-light border-0 small text-dark"
                            placeholder="Ketik keyword nama atau NIM mahasiswa..." autocomplete="off" style="font-size: 13px;">
                    </div>

                    <div class="col-sm-5">
                        <select id="filter-angkatan" class="form-select rounded-pill py-2.5 bg-light border-0 small text-dark" style="font-size: 13px;">
                            <option value="">Tampilkan Semua Angkatan</option>
                            <option value="2022">Angkatan 2022</option>
                            <option value="2023">Angkatan 2023</option>
                            <option value="2024">Angkatan 2024</option>
                            <option value="2025">Angkatan 2025</option>
                        </select>
                    </div>

                </form>
            </div>
        </div>

        <hr class="my-3 opacity-25 border-secondary">

        <div class="d-flex flex-wrap gap-1 align-items-center justify-content-between overflow-x-auto pb-1 style-scrollbar">
            <style>
                .style-scrollbar::-webkit-scrollbar {
                    height: 4px;
                }

                .style-scrollbar::-webkit-scrollbar-thumb {
                    background: #cbd5e1;
                    border-radius: 10px;
                }

                .btn-mhs-letter {
                    font-size: 11px;
                    width: 32px;
                    height: 32px;
                    padding: 0;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    transition: all 0.2s;
                    cursor: pointer;
                }

                .btn-mhs-letter.active {
                    background-color: #245358 !important;
                    color: #ffffff !important;
                    font-weight: bold;
                }
            </style>

            <div class="d-flex gap-1">
                <button type="button" class="btn btn-light text-secondary rounded-circle btn-mhs-letter active" data-letter="ALL">ALL</button>
                <?php
                foreach (range('A', 'Z') as $char) {
                    echo '<button type="button" class="btn btn-light text-secondary rounded-circle btn-mhs-letter mx-0.5" data-letter="' . $char . '">' . $char . '</button>';
                }
                ?>
            </div>

            <div class="form-check ms-3 mt-2 mt-md-0 me-2 bg-light p-2 px-3 rounded-pill border">
                <input class="form-check-input style-pointer ms-0 me-2" type="checkbox" id="check-all-mhs">
                <label class="form-check-label small fw-bold text-secondary style-pointer shadow-none" for="check-all-mhs">Pilih Semua Halaman Ini</label>
            </div>
        </div>
    </div>

    <div id="mhs-directory-container">
        <div class="text-center py-5">
            <div class="spinner-border text-success" role="status"></div>
            <p class="text-muted mt-2 small">Sedang memuat data direktori mahasiswa...</p>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahMahasiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header text-white py-3 bg-navy border-0">
                <div class="d-flex align-items-center">
                    <div class="bg-white text-navy rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-user-graduate fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size:14px;">Formulir Registrasi Mahasiswa Baru</h5>
                        <small class="text-white-50 small" style="font-size: 11px;">Sistem otomatis membuatkan sandi bawaan login.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-tambah-mhs" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $token; ?>">
                <input type="hidden" name="action" value="create">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Nomor Induk Mahasiswa (NIM) *</label>
                            <input type="text" name="nim" class="form-control rounded-3 bg-white text-dark small" required placeholder="Contoh: 2201010034">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Nama Lengkap Mahasiswa *</label>
                            <input type="text" name="nama_mahasiswa" class="form-control rounded-3 bg-white text-dark small" required placeholder="Nama tanpa gelar akademik">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" class="form-select rounded-3 text-dark small" required>
                                <option value="" selected disabled>-- Pilih Jenis Kelamin --</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control rounded-3 bg-white text-dark small" placeholder="Kota Lahir">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control rounded-3 bg-white text-dark small">
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold mb-1">Alamat Domisili Rumah</label>
                            <textarea name="alamat" class="form-control rounded-3 bg-white text-dark small" rows="2" placeholder="Alamat lengkap tempat tinggal saat ini..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Email Mahasiswa *</label>
                            <input type="email" name="email" class="form-control rounded-3 bg-white text-dark small" required placeholder="contoh@kampus.ac.id">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Status Akademik Mahasiswa *</label>
                            <select name="status_mahasiswa" class="form-select rounded-3 text-dark small" required>
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Non-Aktif">Non-Aktif</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Jurusan (Kunci) *</label>
                            <input type="text" name="jurusan" class="form-control rounded-3 bg-light text-muted small" value="Ilmu Komputer" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Program Studi (Prodi) *</label>
                            <select name="prodi" class="form-select rounded-3 text-dark small" required>
                                <option value="" selected disabled>-- Pilih Prodi --</option>
                                <option value="D3 Manajemen Informatika">D3 Manajemen Informatika</option>
                                <option value="S1 Sistem Informasi">S1 Sistem Informasi</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-secondary small fw-bold mb-1">Foto Profil (Opsional)</label>
                            <input type="file" name="foto" class="form-control rounded-3 bg-white text-dark small" accept="image/*">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">ID Semester Masuk *</label>
                            <input type="number" name="semester_saat_ini" class="form-control rounded-3 bg-white text-dark small" value="1" min="1" max="14" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">ID Kelas</label>
                            <input type="number" name="id_kelas" class="form-control rounded-3 bg-white text-dark small" placeholder="Masukkan ID Kelas">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Dosen Wali</label>
                            <select name="id_dosen_wali" class="form-select rounded-3 text-dark small">
                                <option value="" selected>-- Pilih Dosen Wali --</option>
                                <?php
                                if (!empty($data_dosen)) {
                                    foreach ($data_dosen as $dosen) {
                                        echo '<option value="' . $dosen['id_dosen'] . '">' . htmlspecialchars($dosen['nama_dosen']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">IPK Saat Ini *</label>
                            <input type="number" step="0.01" name="ipk" class="form-control rounded-3 bg-white text-dark small" value="0.00" min="0.00" max="4.00" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-secondary small fw-bold mb-1">Angkatan *</label>
                            <input type="number" name="angkatan" class="form-control rounded-3 bg-white text-dark small" required placeholder="Contoh: 2026">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm bg-navy border-0 rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan & Daftarkan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailMhs" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 py-3 bg-navy text-white">
                <h5 class="modal-title fw-bold small text-uppercase"><i class="fa-solid fa-id-card me-2"></i>Profil Detail Mahasiswa</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="konten-detail-mhs"></div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditMahasiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header text-white py-3 border-0" style="background-color: #0f172a !important;">
                <h5 class="modal-title fw-bold small text-uppercase"><i class="fa-solid fa-user-gear me-2"></i>Edit Informasi Profil Mahasiswa</h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-edit-mhs" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $token; ?>">
                <input type="hidden" name="action" value="update">

                <input type="hidden" name="id_mahasiswa" id="edit-id">
                <input type="hidden" name="id_user" id="edit-id-user">

                <div class="modal-body p-4 row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">NIM *</label>
                        <input type="text" name="nim" id="edit-nim" class="form-control rounded-3 bg-light text-muted small" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Nama Lengkap Mahasiswa *</label>
                        <input type="text" name="nama_mahasiswa" id="edit-nama" class="form-control rounded-3 bg-white text-dark small" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" id="edit-jk" class="form-select rounded-3 text-dark small" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" id="edit-tempat" class="form-control rounded-3 bg-white text-dark small">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="edit-tgl" class="form-control rounded-3 bg-white text-dark small">
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Alamat Lengkap</label>
                        <textarea name="alamat" id="edit-alamat" class="form-control rounded-3 bg-white text-dark small" rows="2"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Email *</label>
                        <input type="email" name="email" id="edit-email" class="form-control rounded-3 bg-light text-muted small" readonly>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Status Akademik *</label>
                        <select name="status_mahasiswa" id="edit-status" class="form-select rounded-3 text-dark small" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Cuti">Cuti</option>
                            <option value="Non-Aktif">Non-Aktif</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Jurusan *</label>
                        <input type="text" name="jurusan" id="edit-jurusan" class="form-control rounded-3 bg-white text-dark small" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Program Studi (Prodi) *</label>
                        <select name="prodi" id="edit-prodi" class="form-select rounded-3 text-dark small" required>
                            <option value="D3 Manajemen Informatika">D3 Manajemen Informatika</option>
                            <option value="S1 Sistem Informasi">S1 Sistem Informasi</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Perbarui Berkas Foto Profil (Opsional)</label>
                        <input type="file" name="foto" class="form-control rounded-3 bg-white text-dark small" accept="image/*">
                        <small class="text-muted d-block mt-1" style="font-size: 11px;">Biarkan kosong jika tidak ingin mengganti foto yang sudah ada.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">ID Semester Masuk *</label>
                        <input type="number" name="semester_saat_ini" id="edit-semester-masuk" class="form-control rounded-3 bg-white text-dark small" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">ID Kelas</label>
                        <input type="number" name="id_kelas" id="edit-kelas" class="form-control rounded-3 bg-white text-dark small">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">ID Dosen Wali</label>
                        <input type="number" name="id_dosen_wali" id="edit-dosen-wali" class="form-control rounded-3 bg-white text-dark small">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">IPK Akumulatif *</label>
                        <input type="number" step="0.01" name="ipk" id="edit-ipk" class="form-control rounded-3 bg-white text-dark small" required min="0.00" max="4.00">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-secondary">Angkatan *</label>
                        <input type="number" name="angkatan" id="edit-angkatan" class="form-control rounded-3 bg-white text-dark small" required>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning btn-sm text-dark rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImportCSVMahasiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">

            <div class="modal-header border-0 py-3 text-white" style="background-color: #198754;">
                <h5 class="modal-title fw-bold" style="font-size: 15px;">
                    <i class="fa-solid fa-file-excel me-2"></i>Impor Data Mahasiswa via CSV
                </h5>
                <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
            </div>

            <form action="proses_import.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 bg-white">

                    <div class="p-3 mb-4" style="background-color: #fef9e7; border-radius: 8px;">
                        <h6 class="fw-bold mb-2" style="color: #6a5716; font-size: 14px;">
                            <i class="fa-solid fa-circle-exclamation me-1"></i>Aturan Struktur File:
                        </h6>
                        <ol class="mb-0 ps-3 text-secondary" style="font-size: 13px; line-height: 1.8;">
                            <li>Susunan kolom wajib: <strong class="text-dark">NIM, Nama Mahasiswa, Email, No HP</strong>.</li>
                            <li>Format berkas wajib ekstensi <strong class="text-dark">.csv (Comma delimited)</strong>.</li>
                            <li>Kata sandi otomatis diatur: <code style="color: #d63384; font-size: 13px;">[NIM]123</code>.</li>
                        </ol>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-secondary mb-2" style="font-size: 14px;">Pilih File CSV Mahasiswa</label>
                        <input type="file" name="file_csv" class="form-control rounded-2 text-dark" accept=".csv" required>
                    </div>

                </div>

                <div class="modal-footer border-0 py-3" style="background-color: #f8f9fa;">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal" style="background-color: #6c757d; border: none;">
                        Batal
                    </button>
                    <button type="submit" name="btn_import_mahasiswa" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" style="background-color: #198754; border: none;">
                        Mulai Impor Data
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let currentLetter = 'ALL';
    let currentSearch = '';
    let currentAngkatan = '';
    let currentPage = 1;

    function loadMahasiswa() {
        $.ajax({
            url: 'fetch_mahasiswa.php',
            type: 'GET',
            data: {
                letter: currentLetter,
                search: currentSearch,
                angkatan: currentAngkatan,
                page: currentPage
            },
            success: function(html) {
                $('#mhs-directory-container').html(html);
                $('#check-all-mhs').prop('checked', false);
                toggleTombolHapusMassal();
            }
        });
    }

    function toggleTombolHapusMassal() {
        let terpilih = $('.check-item-mhs:checked').length;
        if (terpilih > 0) {
            $('#jumlah-terpilih').text(terpilih);
            $('#btn-hapus-massal').removeClass('d-none');
        } else {
            $('#btn-hapus-massal').addClass('d-none');
        }
    }

    $(document).ready(function() {
        loadMahasiswa();

        $('.btn-mhs-letter').click(function() {
            $('.btn-mhs-letter').removeClass('active');
            $(this).addClass('active');
            currentLetter = $(this).data('letter');
            currentPage = 1;
            loadMahasiswa();
        });

        $('#search-mhs').on('keyup input', function() {
            currentSearch = $(this).val();
            currentPage = 1;
            loadMahasiswa();
        });

        $('#filter-angkatan').change(function() {
            currentAngkatan = $(this).val();
            currentPage = 1;
            loadMahasiswa();
        });

        $(document).on('click', '.mhs-page-link', function(e) {
            e.preventDefault();
            currentPage = $(this).data('page');
            loadMahasiswa();
        });

        // SELECT ALL CHECKBOX MASTER
        $('#check-all-mhs').change(function() {
            let status = $(this).is(':checked');
            $('.check-item-mhs').prop('checked', status);
            toggleTombolHapusMassal();
        });

        // CHECKBOX PER BARIS TABEL
        $(document).on('change', '.check-item-mhs', function() {
            let totalItem = $('.check-item-mhs').length;
            let totalChecked = $('.check-item-mhs:checked').length;
            
            if (totalItem === totalChecked) {
                $('#check-all-mhs').prop('checked', true);
            } else {
                $('#check-all-mhs').prop('checked', false);
            }
            toggleTombolHapusMassal();
        });

        // PROSES HAPUS MASSAL TERPILIH
        $('#btn-hapus-massal').click(function() {
            let listId = [];
            $('.check-item-mhs:checked').each(function() {
                listId.push($(this).val());
            });

            if (listId.length === 0) return;

            Swal.fire({
                title: 'Hapus Semua Terpilih?',
                text: "Sebanyak " + listId.length + " data mahasiswa akan dihapus permanen sekaligus dari sistem beserta akun loginnya!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus Semua!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'proses.php',
                        type: 'POST',
                        data: {
                            action: 'delete_massal',
                            ids: listId,
                            csrf_token: '<?= $token; ?>'
                        },
                        success: function(res) {
                            let data = (typeof res === 'object') ? res : JSON.parse(res);
                            if (data.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil Dihapus!',
                                    text: data.message,
                                    confirmButtonColor: '#245358'
                                });
                                loadMahasiswa();
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        }
                    });
                }
            });
        });

        // MODAL DETAIL
        $(document).on('click', '.btn-detail-mhs', function() {
            let id = $(this).data('id');
            $('#konten-detail-mhs').html('<div class="text-center p-3"><div class="spinner-border spinner-border-sm text-success"></div></div>');
            $('#modalDetailMhs').modal('show');
            $.ajax({
                url: 'get_mahasiswa.php',
                type: 'GET',
                data: { id: id },
                success: function(res) {
                    $('#konten-detail-mhs').html(res);
                }
            });
        });

        // TAMBAH DATA MAHASISWA
        $('#form-tambah-mhs').submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                url: 'proses.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    let data = (typeof res === 'object') ? res : JSON.parse(res);
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            confirmButtonColor: '#245358'
                        });
                        $('#modalTambahMahasiswa').modal('hide');
                        $('#form-tambah-mhs')[0].reset();
                        loadMahasiswa();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: data.message,
                            confirmButtonColor: '#245358'
                        });
                    }
                }
            });
        });

        // TRIGER TOMBOL EDIT DARI MODAL DETAIL
        $(document).on('click', '#btn-buka-edit-modal', function() {
            $('#modalDetailMhs').modal('hide');
            $('#edit-id').val($(this).data('id'));
            $('#edit-nim').val($(this).data('id')); // placeholder NIM sewaktu ditarik
            $('#edit-nama').val($(this).data('nama'));
            $('#edit-jk').val($(this).data('jk'));
            $('#edit-tempat').val($(this).data('tempat'));
            $('#edit-tgl').val($(this).data('tgl'));
            $('#edit-alamat').val($(this).data('alamat'));
            $('#edit-semester-masuk').val($(this).data('semester'));
            $('#edit-ipk').val($(this).data('ipk'));
            $('#edit-status').val($(this).data('status'));
            $('#edit-prodi').val($(this).data('prodi'));
            
            // Mengisi field tambahan dari data attribute
            $('#edit-jurusan').val($(this).data('jurusan') || 'Ilmu Komputer');
            $('#edit-email').val($(this).data('email') || '');
            
            // Ambil NIM asli ter-update dari baris tabel jika ada
            let rowNim = $(this).closest('.modal-content').find('.badge').text().replace('NIM. ', '').trim();
            $('#edit-nim').val(rowNim);

            setTimeout(function() {
                $('#modalEditMahasiswa').modal('show');
            }, 400);
        });

        // SIMPAN EDIT DATA MAHASISWA
        $('#form-edit-mhs').submit(function(e) {
            e.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                url: 'proses.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(res) {
                    let data = (typeof res === 'object') ? res : JSON.parse(res);
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            confirmButtonColor: '#245358'
                        });
                        $('#modalEditMahasiswa').modal('hide');
                        loadMahasiswa();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message,
                            confirmButtonColor: '#245358'
                        });
                    }
                }
            });
        });

        // HAPUS SATU DATA MAHASISWA
        $(document).on('click', '.btn-hapus-mhs', function(e) {
            e.stopPropagation();
            let id_mhs = $(this).data('id');
            let nama = $(this).data('nama');
            Swal.fire({
                title: 'Hapus data?',
                text: "Data '" + nama + "' akan dihapus permanen beserta akun loginnya!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'proses.php',
                        type: 'POST',
                        data: {
                            action: 'delete',
                            id_mahasiswa: id_mhs,
                            csrf_token: '<?= $token; ?>'
                        },
                        success: function(res) {
                            let data = (typeof res === 'object') ? res : JSON.parse(res);
                            if (data.status === 'success') {
                                Swal.fire('Terhapus!', data.message, 'success');
                                loadMahasiswa();
                            } else {
                                Swal.fire('Gagal!', data.message, 'error');
                            }
                        }
                    });
                }
            });
        });
    });
</script>
<style>.style-pointer { cursor: pointer; }</style>
<?php require_once '../../templates/footer.php'; ?>