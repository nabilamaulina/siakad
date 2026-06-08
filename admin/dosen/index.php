<?php
// admin/dosen/index.php
require_once '../../templates/header.php';
require_once '../../templates/sidebar.php';
require_once '../../config/database.php';

// Proteksi halaman: Hanya boleh diakses oleh Admin
middleware(['admin']); 

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
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght=400;500;600;700&display=swap" rel="stylesheet">
<div class="container-fluid py-3" style="font-family: 'Plus Jakarta Sans', sans-serif;">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-navy mb-1"><i class="fa-solid fa-chalkboard-user me-2"></i>Direktori Data Dosen</h3>
            <p class="text-secondary small mb-0">Kelola, cari berdasarkan abjad, dan penuhi administrasi data profil dosen SOBAT IK.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" id="btn-hapus-massal-dosen" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm fw-semibold d-none">
                <i class="fa-solid fa-trash-can me-1"></i> Hapus Terpilih (<span id="jumlah-terpilih-dosen">0</span>)
            </button>
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalImportCSVDosen">
                <i class="fa-solid fa-file-import me-1.5"></i>Impor via CSV
            </button>
            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 bg-navy border-0 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDosen">
                <i class="fa-solid fa-plus me-1.5"></i>Tambah Dosen Baru
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" style="background: #ffffff;">
        <div class="row g-3 align-items-center">
            
            <div class="col-12">
                <form id="form-filter-dosen" class="row g-2" onsubmit="return false;">
                    
                    <div class="col-sm-7 position-relative">
                        <span class="position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary opacity-50">
                            <i class="fa-solid fa-magnifying-glass small"></i>
                        </span>
                        <input type="text" id="searchDosen" class="form-control rounded-pill ps-5 py-2.5 bg-light border-0 small text-dark" 
                               placeholder="Ketik keyword nama dosen..." autocomplete="off" style="font-size: 13px;">
                    </div>
                    
                    <div class="col-sm-5">
                        <select id="filterJabatan" class="form-select rounded-pill py-2.5 bg-light border-0 small text-dark" style="font-size: 13px;">
                            <option value="">Semua Jabatan Fungsional</option>
                            <option value="Asisten Ahli">Asisten Ahli</option>
                            <option value="Lektor">Lektor</option>
                            <option value="Lektor Kepala">Lektor Kepala</option>
                            <option value="Profesor">Profesor</option>
                        </select>
                    </div>

                </form>
            </div>

        </div>

        <hr class="my-3 opacity-25 border-secondary">
            
        <div class="d-flex flex-wrap gap-1 align-items-center justify-content-between overflow-x-auto pb-1 style-scrollbar">
            <style>
                .style-scrollbar::-webkit-scrollbar { height: 4px; }
                .style-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
                
                .btn-letter-filter { font-size: 11px; width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; cursor: pointer; }
                .btn-letter-filter.active { background-color: #245358 !important; color: #ffffff !important; font-weight: bold; }
                .style-pointer { cursor: pointer; }
            </style>
            
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-light text-secondary rounded-circle btn-letter-filter active" data-letter="ALL">ALL</button>
                
                <?php 
                foreach (range('A', 'Z') as $char) {
                    echo '<button type="button" class="btn btn-light text-secondary rounded-circle btn-letter-filter mx-0.5" data-letter="'.$char.'">'.$char.'</button>';
                }
                ?>
            </div>

            <div class="form-check ms-3 mt-2 mt-md-0 me-2 bg-light p-2 px-3 rounded-pill border">
                <input class="form-check-input style-pointer ms-0 me-2" type="checkbox" id="check-all-dosen">
                <label class="form-check-label small fw-bold text-secondary style-pointer shadow-none" for="check-all-dosen">Pilih Semua Halaman Ini</label>
            </div>
        </div>
    </div>

    <!-- Container Utama Render Tabel Dosen -->
    <div id="direktoriDosenContainer"></div>
</div>

<!-- MODAL DETAIL DOSEN -->
<div class="modal fade" id="modalDetailDosen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-navy text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold small"><i class="fa-solid fa-id-card me-2"></i>Profil Detail Dosen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center" id="detailDosenBody"></div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH DOSEN -->
<div class="modal fade" id="modalDosen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formTambahDosen" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-navy text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold small"><i class="fa-solid fa-user-plus me-2"></i>Tambah Data & Akun Dosen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="csrf_token" value="<?= $token; ?>">
                
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">NIDN (Username Login)</label>
                    <input type="text" name="nidn" class="form-control rounded-3 bg-white text-dark small" placeholder="Contoh: 0025088901" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">Nama Lengkap Dosen</label>
                    <input type="text" name="nama_dosen" class="form-control rounded-3 bg-white text-dark small" placeholder="Nama lengkap beserta gelar akademik" required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label small fw-bold text-secondary">Jabatan Fungsional</label>
                    <select name="jabatan" class="form-select rounded-3 text-dark small" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="Asisten Ahli">Asisten Ahli</option>
                        <option value="Lektor">Lektor</option>
                        <option value="Lektor Kepala">Lektor Kepala</option>
                        <option value="Profesor">Profesor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">Email Resmi</label>
                    <input type="email" name="email" class="form-control rounded-3 bg-white text-dark small" placeholder="contoh@kampus.ac.id">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">No. Handphone (WA)</label>
                    <input type="text" name="no_hp" class="form-control rounded-3 bg-white text-dark small" placeholder="Contoh: 08123456789">
                </div>
                <div class="alert alert-info border-0 rounded-3 shadow-sm mb-0 p-2.5 small">
                    <i class="fa-solid fa-circle-info me-1.5 text-primary"></i>
                    <strong>Sistem Otomatis:</strong> Akun login dosen otomatis aktif dengan password default: <code>[NIDN]123</code>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold">Simpan & Daftarkan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT DOSEN -->
<div class="modal fade" id="modalEditDosen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditDosen" enctype="multipart/form-data" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-dark text-white border-0 rounded-top-4" style="background-color: #0f172a !important;">
                <h5 class="modal-title fw-bold small"><i class="fa-solid fa-user-pen me-2"></i>Ubah Data Profil Dosen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="csrf_token" value="<?= $token; ?>">
                <input type="hidden" name="id_dosen" id="edit_id_dosen">
                
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">NIDN (Tidak dapat diubah)</label>
                    <input type="text" id="edit_nidn" class="form-control rounded-3 bg-light text-muted small" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">Nama Lengkap Dosen</label>
                    <input type="text" name="nama_dosen" id="edit_nama_dosen" class="form-control rounded-3 bg-white text-dark small" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-secondary">Jabatan Fungsional</label>
                    <select name="jabatan" id="edit_jabatan" class="form-select rounded-3 text-dark small" required>
                        <option value="">-- Pilih Jabatan --</option>
                        <option value="Asisten Ahli">Asisten Ahli</option>
                        <option value="Lektor">Lektor</option>
                        <option value="Lektor Kepala">Lektor Kepala</option>
                        <option value="Profesor">Profesor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">Email Resmi</label>
                    <input type="email" name="email" id="edit_email" class="form-control rounded-3 bg-white text-dark small">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">No. Handphone (WA)</label>
                    <input type="text" name="no_hp" id="edit_no_hp" class="form-control rounded-3 bg-white text-dark small">
                </div>
                <div class="mb-3">
                    <label class="form-label text-secondary small fw-bold">Perbarui Berkas Foto Profil (Opsional)</label>
                    <input type="file" name="foto_input" class="form-control rounded-3 bg-white text-dark small" accept="image/*">
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 fw-semibold" style="background-color: #0f172a;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL IMPORT CSV DOSEN -->
<div class="modal fade" id="modalImportCSVDosen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="proses_import.php" method="POST" enctype="multipart/form-data" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-success text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold small"><i class="fa-solid fa-file-excel me-2"></i>Impor Data Dosen via CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="csrf_token" value="<?= $token; ?>">
                
                <div class="alert alert-warning border-0 rounded-3 shadow-sm mb-3 p-3">
                    <h6 class="fw-bold small mb-1"><i class="fa-solid fa-circle-exclamation me-1.5"></i>Aturan Struktur File:</h6>
                    <ol class="small text-muted ps-3 mb-0">
                        <li>Susunan kolom wajib: <b>NIDN, Nama Dosen, Email, No HP</b>.</li>
                        <li>Format berkas wajib ekstensi <b>.csv (Comma delimited)</b>.</li>
                        <li>Kata sandi otomatis diatur: <code>[NIDN]123</code>.</li>
                    </ol>
                </div>

                <div class="mb-0">
                    <label class="form-label text-secondary small fw-bold">Pilih File CSV Dosen</label>
                    <input type="file" name="file_csv" class="form-control text-dark small" accept=".csv" required>
                </div>
            </div>
            <div class="modal-footer bg-light border-0 rounded-bottom-4">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="btn_import_dosen" class="btn btn-success btn-sm rounded-pill px-4">Mulai Impor Data</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    let currentLetter = 'ALL';

    function loadDosen(letter = 'ALL', search = '', jabatan = '') {
        $.ajax({
            url: 'fetch_dosen.php',
            type: 'GET',
            data: { letter: letter, search: search, jabatan: jabatan },
            success: function(html) {
                $('#direktoriDosenContainer').html(html);
                $('#check-all-dosen').prop('checked', false);
                toggleTombolHapusMassalDosen();
            }
        });
    }

    function toggleTombolHapusMassalDosen() {
        let terpilih = $('.check-item-dosen:checked').length;
        if (terpilih > 0) {
            $('#jumlah-terpilih-dosen').text(terpilih);
            $('#btn-hapus-massal-dosen').removeClass('d-none');
        } else {
            $('#btn-hapus-massal-dosen').addClass('d-none');
        }
    }

    loadDosen();

    $('.btn-letter-filter').on('click', function() {
        $('.btn-letter-filter').removeClass('active');
        $(this).addClass('active');
        
        currentLetter = $(this).data('letter');
        loadDosen(currentLetter, $('#searchDosen').val(), $('#filterJabatan').val());
    });

    $('#searchDosen').on('keyup', function() {
        loadDosen(currentLetter, $(this).val(), $('#filterJabatan').val());
    });

    $('#filterJabatan').on('change', function() {
        loadDosen(currentLetter, $('#searchDosen').val(), $(this).val());
    });

    // LOGIKA MASTER SELECT ALL DOSEN
    $('#check-all-dosen').change(function() {
        let status = $(this).is(':checked');
        $('.check-item-dosen').prop('checked', status);
        toggleTombolHapusMassalDosen();
    });

    // LOGIKA INDIVIDUAL CHECKBOX DOSEN
    $(document).on('change', '.check-item-dosen', function() {
        let totalItem = $('.check-item-dosen').length;
        let totalChecked = $('.check-item-dosen:checked').length;
        
        if (totalItem === totalChecked) {
            $('#check-all-dosen').prop('checked', true);
        } else {
            $('#check-all-dosen').prop('checked', false);
        }
        toggleTombolHapusMassalDosen();
    });

    // PROSES AJAX DELETE MASSAL DOSEN
    $('#btn-hapus-massal-dosen').click(function() {
        let listId = [];
        $('.check-item-dosen:checked').each(function() {
            listId.push($(this).val());
        });

        if (listId.length === 0) return;

        Swal.fire({
            title: 'Hapus Semua Terpilih?',
            text: "Sebanyak " + listId.length + " data dosen akan dihapus permanen beserta akun loginnya dari sistem!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'proses.php',
                    type: 'POST',
                    data: {
                        action: 'delete_massal_dosen',
                        ids: listId,
                        csrf_token: '<?= $token; ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire('Berhasil!', response.message, 'success');
                            loadDosen(currentLetter, $('#searchDosen').val(), $('#filterJabatan').val());
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // TRIGGER MODAL DETAIL DOSEN
    $(document).on('click', '.btn-detail-dosen', function() {
        let idDsn = $(this).data('id');
        $.ajax({
            url: 'fetch_dosen.php',
            type: 'GET',
            data: { get_detail: true, id_dosen: idDsn },
            dataType: 'json',
            success: function(data) {
                let htmlDetail = `
                    <img src="../../assets/uploads/profile/${data.foto || 'default.png'}" class="rounded-circle mb-3 border shadow-sm" width="110" height="110" onerror="this.src='https://cdn-icons-png.flaticon.com/512/3135/3135715.png'">
                    <h5 class="fw-bold text-navy mb-1">${data.nama_dosen}</h5>
                    <span class="badge bg-primary rounded-pill px-3 mb-2" style="font-size: 11px;">NIDN: ${data.nidn}</span>
                    <div class="mb-3"><span class="badge bg-info rounded-pill px-3 text-dark" style="font-size: 11px;">${data.jabatan || 'Belum Ada Jabatan'}</span></div>
                    <hr class="my-3 opacity-25">
                    <table class="table table-sm text-start table-borderless small px-2">
                        <tr><td width="38%" class="text-secondary fw-semibold">Email Resmi</td><td>: ${data.email || '-'}</td></tr>
                        <tr><td class="text-secondary fw-semibold">No. HP / WA</td><td>: ${data.no_hp || '-'}</td></tr>
                        <tr><td class="text-secondary fw-semibold">ID Akun User</td><td>: <span class="badge bg-light text-dark border">ID-${data.id_user}</span></td></tr>
                    </table>
                `;
                $('#detailDosenBody').html(htmlDetail);
                $('#modalDetailDosen').modal('show');
            }
        });
    });

    // TRIGGER MODAL EDIT DOSEN
    $(document).on('click', '.btn-edit-dosen', function(e) {
        e.stopPropagation();
        let idDsn = $(this).data('id');
        $.ajax({
            url: 'fetch_dosen.php',
            type: 'GET',
            data: { get_detail: true, id_dosen: idDsn },
            dataType: 'json',
            success: function(data) {
                $('#edit_id_dosen').val(data.id_dosen);
                $('#edit_nidn').val(data.nidn);
                $('#edit_nama_dosen').val(data.nama_dosen);
                $('#edit_jabatan').val(data.jabatan);
                $('#edit_email').val(data.email);
                $('#edit_no_hp').val(data.no_hp);
                $('#modalEditDosen').modal('show');
            }
        });
    });

    // AJAX PROSES TAMBAH DOSEN
    $('#formTambahDosen').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'proses.php',
            type: 'POST',
            data: $(this).serialize() + '&action=create',
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message, confirmButtonColor: '#0f172a' }).then(() => { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message, confirmButtonColor: '#0f172a' });
                }
            }
        });
    });

    // AJAX PROSES EDIT DOSEN
    $('#formEditDosen').on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.append('action', 'update');
        $.ajax({
            url: 'proses.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: response.message, confirmButtonColor: '#0f172a' }).then(() => { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message, confirmButtonColor: '#0f172a' });
                }
            }
        });
    });

    // AJAX PROSES HAPUS DOSEN INDIVIDUAL
    $(document).on('click', '.btn-hapus-dosen', function(e) {
        e.stopPropagation(); 
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var token = '<?= $token; ?>';

        Swal.fire({
            title: 'Apakah Anda yakin?',
            text: "Menghapus " + nama + " juga akan melenyapkan akun login dosen tersebut secara permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus Akun!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'proses.php',
                    type: 'POST',
                    data: { action: 'delete', id_dosen: id, csrf_token: token },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            Swal.fire('Terhapus!', response.message, 'success').then(() => {
                                loadDosen(currentLetter, $('#searchDosen').val(), $('#filterJabatan').val());
                            });
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>

<?php require_once '../../templates/footer.php'; ?>