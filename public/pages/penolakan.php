<link href="../css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/navbar.css">
<link rel="stylesheet" href="../css/pagesStyle.css">

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../action/get_pasien_info.php'; // Load helper dari action/

// Ambil parameter dari URL/POST
$no_rawat = $_POST['no_rawat'] ?? $_GET['no_rawat'] ?? $_SESSION['no_rawat'] ?? '';
$no_rkm_medis = $_POST['no_rkm_medis'] ?? $_GET['no_rkm_medis'] ?? $_SESSION['no_rkm_medis'] ?? '';

// Validasi: jika no_rawat dan no_rkm_medis kosong, redirect ke list_pasien.php
if (empty($no_rawat) && empty($no_rkm_medis)) {
    header('Location: list_pasien.php?status=error&message=' . urlencode('Nomor rawat atau nomor rekam medis tidak ditemukan.'));
    exit;
}

// Ambil data pasien menggunakan helper
$data = getPatientData($pdo, $no_rawat, $no_rkm_medis);
$pasien = $data['pasien'];
$umur = $data['umur'];

// Validasi: jika no_rawat masih kosong setelah getPatientData, redirect
if (empty($pasien['no_rawat']) && empty($no_rawat)) {
    header('Location: list_pasien.php?status=error&message=' . urlencode('Data pasien tidak valid atau nomor rawat tidak ditemukan.'));
    exit;
}

// Set judul halaman
$title = "Form Penolakan Tindakan Kedokteran";
require_once '../partials/header.php';

// Helper untuk section header
function section($title)
{
    return "<h5 class='mt-4 mb-3 fw-bold border-bottom pb-2'>$title</h5>";
}
?>

<div class="container my-4">
    <div class="card shadow p-4 form-title-card visible">
        <div class="card-header text-white d-flex align-items-center justify-content-center mb-4"
            style="background-color: #c50202ff !important; color: #f5f5f5 !important;">
            <i class="fas fa-file-medical me-2" style="color: #f5f5f5 !important;"></i>
            <h4 class="mb-0 fw-bold"><?= htmlspecialchars($title) ?></h4>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?= $_GET['status'] === 'success' ? 'success' : 'danger' ?>">
                <?= esc(urldecode($_GET['message'] ?? 'Unknown error')) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="../action/save_penolakan.php">
            <input type="hidden" name="no_rawat" value="<?= esc($no_rawat) ?>">
            <input type="hidden" name="no_rkm_medis" value="<?= esc($no_rkm_medis) ?>">
            <!-- Identitas Pasien -->
            <?= section("Identitas Pasien") ?>
            <div class="row mb-3 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 identitas-card visible">
                        <div class="card-header bg-gray text-white d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <h6 class="mb-0 fw-bold">Informasi Pribadi</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-user me-1"></i> Nama</label>
                                    <input type="text" class="form-control" name="nama_pasien" value="<?= esc($pasien['nm_pasien'] ?? '') ?>" readonly required>
                                    <div class="invalid-feedback">Nama wajib diisi.</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-calendar-alt me-1"></i> Tanggal Lahir</label>
                                    <input type="date" class="form-control" name="tgl_lahir" value="<?= esc($pasien['tgl_lahir'] ?? '') ?>" readonly required>
                                    <div class="invalid-feedback">Tanggal lahir wajib diisi.</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-child me-1"></i> Umur</label>
                                    <input type="text" class="form-control" name="umur" value="<?= esc($umur) ?>" placeholder="th/bln" readonly required>
                                    <div class="invalid-feedback">Umur wajib diisi.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-home me-1"></i> Alamat</label>
                                    <input type="text" class="form-control" name="alamat" value="<?= esc($pasien['alamat'] ?? '') ?>" readonly required>
                                    <div class="invalid-feedback">Alamat wajib diisi.</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-venus-mars me-1"></i> Jenis Kelamin</label>
                                    <select class="form-select" name="sex" disabled required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option value="L" <?= ($pasien['jk'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= ($pasien['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                    <div class="invalid-feedback">Jenis kelamin wajib dipilih.</div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-id-card me-1"></i> No Rekam Medis</label>
                                    <input type="text" class="form-control" name="no_rm" value="<?= esc($pasien['no_rkm_medis'] ?? '') ?>" readonly required>
                                    <div class="invalid-feedback">No RM wajib diisi.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-calendar-check me-1"></i> Tanggal Kunjungan</label>
                                    <input type="date" class="form-control" name="tgl_kunjungan" value="<?= esc($pasien['tgl_masuk'] ?? '') ?>" required>
                                    <div class="invalid-feedback">Tanggal kunjungan wajib diisi.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-clock me-1"></i> Jam Kunjungan</label>
                                    <input type="time" class="form-control" name="jam_kunjungan" value="<?= esc($pasien['jam_masuk'] ?? '') ?>" required>
                                    <div class="invalid-feedback">Jam kunjungan wajib diisi.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-door-open me-1"></i> Ruang</label>
                                    <select class="form-select" name="ruang" id="ruang_dropdown" required>
                                        <option value="" disabled <?= empty($_POST['ruang']) ? 'selected' : '' ?>>Pilih Ruang</option>
                                        <?php
                                        try {

                                            $stmt_kamar = $pdo->query("SELECT kd_kamar, kelas FROM kamar WHERE statusdata = '1' ORDER BY kd_kamar");
                                            $kamar_list = $stmt_kamar->fetchAll(PDO::FETCH_ASSOC);

                                            $grouped_kamar = [];
                                            foreach ($kamar_list as $kamar) {

                                                $prefix = preg_match('/^([A-Z]+)/i', $kamar['kd_kamar'], $matches) ? $matches[1] : 'Lainnya';
                                                $grouped_kamar[$prefix][] = $kamar;
                                            }

                                            ksort($grouped_kamar);

                                            foreach ($grouped_kamar as $prefix => $kamar_group) {
                                                echo "<optgroup label='" . esc($prefix) . "'>";
                                                foreach ($kamar_group as $kamar) {
                                                    $selected = ($_POST['ruang'] ?? '') === $kamar['kd_kamar'] ? 'selected' : '';
                                                    echo "<option value='" . esc($kamar['kd_kamar']) . "' data-kelas='" . esc($kamar['kelas']) . "' $selected>" . esc($kamar['kd_kamar']) . "</option>";
                                                }
                                                echo "</optgroup>";
                                            }
                                        } catch (PDOException $e) {
                                            error_log("Error fetching ruang: " . $e->getMessage());
                                            echo "<option value=''>Error: Tidak dapat memuat data ruang</option>";
                                        }
                                        ?>
                                    </select>
                                    <div class="invalid-feedback">Ruang wajib dipilih.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-star me-1"></i> Kelas</label>
                                    <input type="text" class="form-control" name="kelas" id="kelas_input" readonly placeholder="otomatis terisi" required>
                                    <div class="invalid-feedback">Kelas wajib diisi.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Dokter / Informasi -->
            <?= section("Dokter / Informasi") ?>
            <div class="row mb-3 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 identitas-card visible">
                        <div class="card-header bg-gray text-white d-flex align-items-center">
                            <i class="fas fa-user-md me-2"></i>
                            <h6 class="mb-0 fw-bold">Informasi Dokter</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-user-md me-1"></i> Dokter Pelaksana Tindakan</label>
                                    <input type="text" class="form-control" name="dokter_pelaksana" required>
                                    <div class="invalid-feedback">Dokter pelaksana wajib diisi.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-info-circle me-1"></i> Pemberi Informasi</label>
                                    <input type="text" class="form-control" name="pemberi_informasi" required>
                                    <div class="invalid-feedback">Pemberi informasi wajib diisi.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-user-check me-1"></i> Penerima Informasi</label>
                                    <input type="text" class="form-control" name="penerima_informasi" required>
                                    <div class="invalid-feedback">Penerima informasi wajib diisi.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Butir Informasi -->
            <?= section("Butir Informasi") ?>
            <div class="medical-table-container">
                <table class="table-medical">
                    <thead>
                        <tr>
                            <th style="width:5%">No</th>
                            <th style="width:30%">Jenis Informasi</th>
                            <th>Isi Informasi</th>
                            <th style="width:10%">Tanda (✔)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $butir = [
                            "Diagnosis (WD & DD)",
                            "Dasar Diagnosis",
                            "Tindakan Kedokteran",
                            "Indikasi Tindakan",
                            "Tata Cara",
                            "Tujuan",
                            "Risiko",
                            "Komplikasi",
                            "Prognosis",
                            "Alternatif & Risiko",
                            "Lain-lain"
                        ];
                        $no = 1;
                        foreach ($butir as $b) {
                            $name = strtolower(str_replace([' ', '(', ')', '&', '-'], ['_', '', '', '_', ''], $b));
                            echo "
                                <tr>
                                    <td class='text-center'>$no</td>
                                    <td class='label-col'>$b</td>
                                    <td class='input-col'><textarea class='form-control' rows='2' name='$name'></textarea></td>
                                    <td class='text-center' style='vertical-align: middle; padding: 8px;'>
                                        <div style='display: flex; justify-content: center; align-items: center; height: 100%;'>
                                            <input type='checkbox' class='form-check-input table-checkbox' name='check_$name' style='transform: scale(1.8); position: relative; opacity: 1; margin: 0;'>
                                        </div>
                                    </td>
                                </tr>";
                            $no++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Pernyataan setelah Butir Informasi -->
            <?= section("Pernyataan") ?>
            <div class="row mb-3 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 subjektif-card visible">
                        <div class="card-header bg-info text-white d-flex align-items-center">
                            <i class="fas fa-file-signature me-2"></i>
                            <h6 class="mb-0 fw-bold">Pernyataan</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">
                                Dengan ini menyatakan bahwa saya telah menjelaskan hal-hal di atas secara benar dan jelas serta memberikan kesempatan untuk bertanya/diskusi kepada pasien dan/atau keluarganya sehingga telah memahaminya.
                            </p>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="ttd_dokter" id="ttd_dokter" required>
                                <label class="form-check-label fw-bold text-info" for="ttd_dokter">
                                    <i class="fas fa-user-md me-1"></i> Saya selaku <u>Dokter/Petugas</u> telah memberikan penjelasan.
                                </label>
                                <div class="invalid-feedback">Wajib mencentang pernyataan dokter.</div>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="ttd_pasien" id="ttd_pasien" required>
                                <label class="form-check-label fw-bold text-info" for="ttd_pasien">
                                    <i class="fas fa-user me-1"></i> Saya selaku <u>Pasien/Keluarga</u> telah menerima informasi.
                                </label>
                                <div class="invalid-feedback">Wajib mencentang pernyataan pasien.</div>
                            </div>
                            <p class="small fst-italic mt-3">
                                * Bila pasien tidak kompeten atau tidak mau menerima informasi, maka penerima informasi adalah wali atau keluarga terdekat.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Penolakan Anestesi -->
            <?= section("Penolakan Anestesi") ?>
            <div class="row mb-3 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 subjektif-card visible">
                        <div class="card-header bg-info text-white d-flex align-items-center">
                            <i class="fas fa-signature me-2"></i>
                            <h6 class="mb-0 fw-bold">Yang Bertanda Tangan</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-info"><i class="fas fa-user me-1"></i> Nama</label>
                                <input type="text" class="form-control" name="nama_penolakan" required>
                                <div class="invalid-feedback">Nama wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-info"><i class="fas fa-home me-1"></i> Alamat</label>
                                <input type="text" class="form-control" name="alamat_penolakan" required>
                                <div class="invalid-feedback">Alamat wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-info"><i class="fas fa-calendar-alt me-1"></i> Tanggal Lahir</label>
                                <input type="date" class="form-control" name="tgl_lahir_penolakan" required>
                                <div class="invalid-feedback">Tanggal lahir wajib diisi.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pasien yang Ditindak -->
            <div class="row mb-3 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 subjektif-card visible">
                        <div class="card-header bg-info text-white d-flex align-items-center">
                            <i class="fas fa-user-injured me-2"></i>
                            <h6 class="mb-0 fw-bold">Pasien yang Ditindak</h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label fw-bold text-info mb-3"><i class="fas fa-check-circle me-1"></i> Dengan ini menyatakan PENOLAKAN untuk dilakukannya tindakan anestesi terhadap:</label>
                            <div class="d-flex flex-wrap gap-3 mb-3">
                                <?php
                                $opsi = ["Saya", "Anak", "Istri", "Suami", "Orang Tua", "Lain-lain"];
                                foreach ($opsi as $o) {
                                    $id = strtolower(str_replace(" ", "_", $o));
                                    echo "
                                    <div class='form-check'>
                                        <input class='form-check-input' type='radio' name='terhadap' id='$id' value='$o' required>
                                        <label class='form-check-label text-info' for='$id'>$o</label>
                                    </div>";
                                }
                                ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-info"><i class="fas fa-user me-1"></i> Nama Pasien yang Ditindak</label>
                                <input type="text" class="form-control" name="nama_tindakan" required>
                                <div class="invalid-feedback">Nama pasien wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-info"><i class="fas fa-home me-1"></i> Alamat Pasien yang Ditindak</label>
                                <input type="text" class="form-control" name="alamat_tindakan" required>
                                <div class="invalid-feedback">Alamat pasien wajib diisi.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-info"><i class="fas fa-calendar-alt me-1"></i> Tanggal Lahir Pasien yang Ditindak</label>
                                <input type="date" class="form-control" name="tgl_lahir_tindakan" required>
                                <div class="invalid-feedback">Tanggal lahir pasien wajib diisi.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tanda Tangan -->
            <?= section("Tanda Tangan") ?>
            <div class="row mb-3 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-signature me-2"></i>
                            <h6 class="mb-0 fw-bold">Tanda Tangan</h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-orange"><i class="fas fa-user me-1"></i> Yang Menyatakan</label>
                                    <div style="border:1px dashed #ccc; height:80px; background:#f8f9fa;"></div>
                                    <input type="text" class="form-control mt-2" name="nama_yang_menyatakan" placeholder="Nama..." required>
                                    <div class="invalid-feedback">Nama wajib diisi.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-orange"><i class="fas fa-user-friends me-1"></i> Keluarga / Wali</label>
                                    <div style="border:1px dashed #ccc; height:80px; background:#f8f9fa;"></div>
                                    <input type="text" class="form-control mt-2" name="nama_wali" placeholder="Nama..." required>
                                    <div class="invalid-feedback">Nama wajib diisi.</div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-orange"><i class="fas fa-user-nurse me-1"></i> Perawat</label>
                                    <div style="border:1px dashed #ccc; height:80px; background:#f8f9fa;"></div>
                                    <input type="text" class="form-control mt-2" name="nama_perawat" placeholder="Nama..." required>
                                    <div class="invalid-feedback">Nama wajib diisi.</div>
                                </div>
                            </div>

                            <!-- TANGGAL + JAM -->
                            <div class="text-center mt-3">
                                <div class="row justify-content-center">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label fw-bold text-orange">
                                            <i class="fas fa-calendar-alt me-1"></i> Tanggal
                                        </label>
                                        <input type="date" class="form-control" name="tgl_surat" required>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label fw-bold text-orange">
                                            <i class="fas fa-clock me-1"></i> Jam
                                        </label>
                                        <input type="time" class="form-control" name="jam_surat" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol -->
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary">Simpan Penolakan</button>
                <button type="reset" class="btn btn-warning">Reset Form</button>
                <a href="list_pasien.php" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ruangDropdown = document.getElementById('ruang_dropdown');
        const kelasInput = document.getElementById('kelas_input');

        // Fungsi untuk mengisi kolom kelas berdasarkan pilihan ruang
        function updateKelas() {
            const selectedOption = ruangDropdown.options[ruangDropdown.selectedIndex];
            const kelas = selectedOption ? selectedOption.getAttribute('data-kelas') : '';
            kelasInput.value = kelas || ''; // Isi kolom kelas atau 
        }

        updateKelas();


        ruangDropdown.addEventListener('change', updateKelas);
    });
</script>
<script src="../js/main.js"></script>