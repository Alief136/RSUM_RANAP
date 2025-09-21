<link href="../css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/navbar,css">
<link rel="stylesheet" href="../css/pagesStyle.css">

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config/db.php'; // Asumsi $pdo sudah didefinisikan di sini (PDO connection)

date_default_timezone_set('Asia/Jakarta');

// Helper function esc (jika belum ada)
if (!function_exists('esc')) {
    function esc($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Ambil parameter dari URL / POST
$no_rawat = $_POST['no_rawat'] ?? $_GET['no_rawat'] ?? '';
$no_rkm_medis = $_POST['no_rkm_medis'] ?? $_GET['no_rkm_medis'] ?? '';

// Inisialisasi data pasien
$pasien = $_SESSION['pasien_data'] ?? ['no_rkm_medis' => '', 'nm_pasien' => '', 'tgl_lahir' => '', 'jk' => '', 'alamat' => '', 'agama' => '', 'stts_nikah' => ''];

// Kalau no_rawat kosong tapi ada no_rkm_medis → ambil rawat terakhir pasien
if ($no_rawat === '' && $no_rkm_medis !== '') {
    $sqlLast = "SELECT rp.no_rawat
        FROM reg_periksa rp
        LEFT JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat
        WHERE rp.no_rkm_medis = ?
        ORDER BY
          COALESCE(CONCAT(ki.tgl_masuk, ' ', COALESCE(ki.jam_masuk, '00:00:00')),
                  CONCAT(rp.tgl_registrasi, ' ', rp.jam_reg)) DESC
        LIMIT 1";
    try {
        $stLast = $pdo->prepare($sqlLast);
        $stLast->execute([$no_rkm_medis]);
        $no_rawat = $stLast->fetchColumn() ?: '';
    } catch (PDOException $e) {
        error_log("Error in last rawat query: " . $e->getMessage());
        $no_rawat = '';
    }
}

// Ambil identitas pasien + no_rawat
if ($no_rawat) {
    $sql = "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk, p.alamat, p.agama, p.stts_nikah
            FROM reg_periksa rp
            JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
            LEFT JOIN kamar_inap ki ON ki.no_rawat = rp.no_rawat
            WHERE (rp.no_rkm_medis = ? OR rp.no_rawat = ?)
            LIMIT 1";
    try {
        $st = $pdo->prepare($sql);
        $st->execute([$no_rkm_medis, $no_rawat]);
        $pasien = $st->fetch(PDO::FETCH_ASSOC) ?: $pasien;
        $_SESSION['pasien_data'] = $pasien;
    } catch (PDOException $e) {
        error_log("Error in main query: " . $e->getMessage());
    }
} elseif ($no_rkm_medis) {
    $sql = "SELECT no_rkm_medis, nm_pasien, tgl_lahir, jk, alamat, agama, stts_nikah
            FROM pasien
            WHERE no_rkm_medis = ?
            LIMIT 1";
    try {
        $st = $pdo->prepare($sql);
        $st->execute([$no_rkm_medis]);
        $pasien = $st->fetch(PDO::FETCH_ASSOC) ?: $pasien;
        $_SESSION['pasien_data'] = $pasien;
    } catch (PDOException $e) {
        error_log("Error in fallback query: " . $e->getMessage());
    }
}

// Hitung umur
$umur = '';
if (!empty($pasien['tgl_lahir'])) {
    $birthDate = new DateTime($pasien['tgl_lahir']);
    $today = new DateTime(date('Y-m-d'));
    $diff = $today->diff($birthDate);
    $umur = $diff->y . ' thn ' . $diff->m . ' bln ' . $diff->d . ' hr';
}

// Ambil tgl_masuk dan jam_masuk dari reg_periksa jika no_rawat ada
$tgl_masuk = '';
$jam_masuk = '';
if ($no_rawat) {
    $sql_reg = "SELECT tgl_registrasi AS tgl_masuk, jam_reg AS jam_masuk
                FROM reg_periksa
                WHERE no_rawat = ?";
    try {
        $st_reg = $pdo->prepare($sql_reg);
        $st_reg->execute([$no_rawat]);
        $data_reg = $st_reg->fetch(PDO::FETCH_ASSOC);
        if ($data_reg) {
            $tgl_masuk = $data_reg['tgl_masuk'];
            $jam_masuk = $data_reg['jam_masuk'];
        }
    } catch (PDOException $e) {
        error_log("Error fetching tgl_masuk/jam_masuk: " . $e->getMessage());
    }
}

// Explicitly set the title
$title = "Form Penolakan Tindakan Kedokteran";
require_once '../partials/header.php';

// Helper function for section headers
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

        <form method="post" action="">
            <!-- Identitas Pasien (sekarang otomatis terisi) -->
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
                                    <input type="date" class="form-control" name="tgl_kunjungan" value="<?= esc($tgl_masuk ?? '') ?>" required>
                                    <div class="invalid-feedback">Tanggal kunjungan wajib diisi.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-clock me-1"></i> Jam Kunjungan</label>
                                    <input type="time" class="form-control" name="jam_kunjungan" value="<?= esc($jam_masuk ?? '') ?>" required>
                                    <div class="invalid-feedback">Jam kunjungan wajib diisi.</div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-door-open me-1"></i> Ruang</label>
                                    <input type="text" class="form-control" name="ruang" required>
                                    <div class="invalid-feedback">Ruang wajib diisi.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-star me-1"></i> Kelas</label>
                                    <select class="form-select" name="kelas" required>
                                        <option value="" disabled selected>Pilih...</option>
                                        <option>III</option>
                                        <option>II</option>
                                        <option>I</option>
                                        <option>VIP</option>
                                    </select>
                                    <div class="invalid-feedback">Kelas wajib dipilih.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ... (kode sisanya tetap sama, seperti Dokter/Informasi, Butir Informasi, dll.) ... -->

        </form>
    </div>
</div>

<script src="../js/main.js"></script>