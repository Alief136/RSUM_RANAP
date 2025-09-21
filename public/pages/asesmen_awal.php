<link href="../css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/navbar,css">
<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config/db.php';
date_default_timezone_set('Asia/Jakarta');

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

$title = "Form Asesmen Awal Medis Rawat Inap - UGD Dewasa";
require_once '../partials/header.php';

function section($title)
{
    return "<h5 class='mt-4 mb-2 fw-bold border-bottom pb-2'>$title</h5>";
}
?>

<link rel="stylesheet" href="../css/pagesStyle.css">

<div class="container my-4">
    <div class="card shadow p-4 form-title-card visible">
        <div class="card-header text-white d-flex align-items-center justify-content-center mb-4"
            style="background-color: #000000ff !important; color: #f5f5f5 !important;">
            <i class="fas fa-file-medical me-2"></i>
            <h4 class="mb-0 fw-bold"><?= $title ?></h4>
        </div>

        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?= $_GET['status'] === 'success' ? 'success' : 'danger' ?>">
                <?= esc(urldecode($_GET['message'] ?? 'Unknown error')) ?>
            </div>
        <?php endif; ?>

        <form id="asesmenForm" method="post" action="../action/save_asesmen_awal_medis_ranap.php">
            <input type="hidden" name="no_rawat" value="<?= esc($no_rawat) ?>">

            <?php
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
            ?>

            <?= section("Identitas Pasien") ?>
            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 identitas-card visible">
                        <div class="card-header bg-gray text-white d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <h6 class="mb-0 fw-bold">Informasi Pribadi</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-user me-1"></i> Nama</label>
                                    <input type="text" class="form-control" name="nama" value="<?= esc($pasien['nm_pasien'] ?? '') ?>" readonly disabled>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-calendar-alt me-1"></i> Tanggal Lahir</label>
                                    <input type="date" class="form-control" name="tgl_lahir" id="tgl_lahir_input" value="<?= esc($pasien['tgl_lahir'] ?? '') ?>" readonly disabled>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-venus-mars me-1"></i> Jenis Kelamin</label>
                                    <select class="form-select" name="jk" disabled>
                                        <option value="" disabled <?= empty($pasien['jk']) ? 'selected' : '' ?>>Pilih...</option>
                                        <option value="L" <?= ($pasien['jk'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="P" <?= ($pasien['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-child me-1"></i> Umur</label>
                                    <input type="text" class="form-control" name="umur" id="umur_input" value="<?= esc($umur) ?>" placeholder="... bln/thn" readonly disabled>
                                </div>
                                <div class="col-md-9 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-home me-1"></i> Alamat</label>
                                    <input type="text" class="form-control" name="alamat" value="<?= esc($pasien['alamat'] ?? '') ?>" readonly disabled>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-pray me-1"></i> Agama</label>
                                    <select class="form-select" name="agama" disabled>
                                        <option value="" disabled <?= empty($pasien['agama']) ? 'selected' : '' ?>>Pilih...</option>
                                        <option value="Islam" <?= ($pasien['agama'] ?? '') === 'Islam' ? 'selected' : '' ?>>Islam</option>
                                        <option value="Kristen" <?= ($pasien['agama'] ?? '') === 'Kristen' ? 'selected' : '' ?>>Kristen</option>
                                        <option value="Katolik" <?= ($pasien['agama'] ?? '') === 'Katolik' ? 'selected' : '' ?>>Katolik</option>
                                        <option value="Hindu" <?= ($pasien['agama'] ?? '') === 'Hindu' ? 'selected' : '' ?>>Hindu</option>
                                        <option value="Buddha" <?= ($pasien['agama'] ?? '') === 'Buddha' ? 'selected' : '' ?>>Buddha</option>
                                        <option value="Konghucu" <?= ($pasien['agama'] ?? '') === 'Konghucu' ? 'selected' : '' ?>>Konghucu</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-ring me-1"></i> Status Perkawinan</label>
                                    <select class="form-select" name="status" disabled>
                                        <option value="" disabled <?= empty($pasien['stts_nikah']) ? 'selected' : '' ?>>Pilih...</option>
                                        <option value="K" <?= ($pasien['stts_nikah'] ?? '') === 'MENIKAH' ? 'selected' : '' ?>>K</option>
                                        <option value="BK" <?= ($pasien['stts_nikah'] ?? '') === 'BELUM MENIKAH' ? 'selected' : '' ?>>BK</option>
                                        <option value="C" <?= ($pasien['stts_nikah'] ?? '') === 'CERAI HIDUP' ? 'selected' : '' ?>>C</option>
                                        <option value="J" <?= ($pasien['stts_nikah'] ?? '') === 'JANDA' ? 'selected' : '' ?>>J</option>
                                        <option value="D" <?= ($pasien['stts_nikah'] ?? '') === 'DUDA' ? 'selected' : '' ?>>D</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-calendar-check me-1"></i> Tanggal Masuk</label>
                                    <input type="date" class="form-control" name="tgl_masuk" value="<?= esc($_POST['tgl_masuk'] ?? $tgl_masuk ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-clock me-1"></i> Jam Masuk</label>
                                    <input type="time" class="form-control" name="jam_masuk" value="<?= esc($_POST['jam_masuk'] ?? $jam_masuk ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Petugas Penanggung Jawab") ?>
            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 identitas-card visible">
                        <div class="card-header bg-gray text-white d-flex align-items-center">
                            <i class="fas fa-user-md me-2"></i>
                            <h6 class="mb-0 fw-bold">Petugas Penanggung Jawab</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-id-card me-1"></i> Kode Dokter</label>
                                    <select name="kd_dokter" id="kd_dokter" class="form-control" required>
                                        <option value="">Pilih Dokter</option>
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter");
                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo "<option value='" . htmlspecialchars($row['kd_dokter']) . "'>" . htmlspecialchars($row['nm_dokter']) . " (" . htmlspecialchars($row['kd_dokter']) . ")</option>";
                                            }
                                        } catch (PDOException $e) {
                                            error_log("Error fetching dokter: " . $e->getMessage());
                                            echo "<option value=''>Error: Tidak dapat memuat data dokter</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-user-nurse me-1"></i> Perawat</label>
                                    <select name="nip_perawat" id="nip_perawat" class="form-control" required>
                                        <option value="">Pilih Perawat</option>
                                        <?php
                                        try {
                                            $stmt = $pdo->query("SELECT nip, nama FROM petugas WHERE status='1' ORDER BY nama");
                                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                echo "<option value='" . htmlspecialchars($row['nip']) . "'>" . htmlspecialchars($row['nama']) . " (" . htmlspecialchars($row['nip']) . ")</option>";
                                            }
                                        } catch (PDOException $e) {
                                            error_log("Error fetching perawat: " . $e->getMessage());
                                            echo "<option value=''>Error: Tidak dapat memuat data perawat</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-2 d-flex align-items-start">
                <div class="col-md-12 mb-3">
                    <div class="card p-3 identitas-card visible">
                        <div class="card-header bg-gray text-white d-flex align-items-center">
                            <i class="fas fa-hospital me-2"></i>
                            <h6 class="mb-0 fw-bold">Detail Penerimaan</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-id-card me-1"></i> No Rekam Medis</label>
                                    <input type="text" class="form-control" name="no_rkm_medis" value="<?= esc($pasien['no_rkm_medis'] ?? '') ?>" readonly>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-file-medical me-1"></i> No Rawat</label>
                                    <input type="text" class="form-control" name="no_rawat_display" value="<?= esc($no_rawat) ?>" readonly>
                                </div>
                                <!-- Bagian Detail Penerimaan (hanya Ruang dan Kelas yang diubah) -->
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-door-open me-1"></i> Ruang</label>
                                    <select class="form-select" name="ruang" id="ruang_dropdown">
                                        <option value="" disabled <?= empty($_POST['ruang']) ? 'selected' : '' ?>>Pilih Ruang</option>
                                        <?php
                                        try {
                                            // Ambil semua kamar dan kelompokkan berdasarkan prefiks kd_kamar
                                            $stmt_kamar = $pdo->query("SELECT kd_kamar, kelas FROM kamar WHERE statusdata = '1' ORDER BY kd_kamar");
                                            $kamar_list = $stmt_kamar->fetchAll(PDO::FETCH_ASSOC);

                                            // Kelompokkan kamar berdasarkan prefiks kd_kamar
                                            $grouped_kamar = [];
                                            foreach ($kamar_list as $kamar) {
                                                // Ekstrak prefiks (misal, "IRNA" dari "IRNA-02")
                                                $prefix = preg_match('/^([A-Z]+)/i', $kamar['kd_kamar'], $matches) ? $matches[1] : 'Lainnya';
                                                $grouped_kamar[$prefix][] = $kamar;
                                            }

                                            // Urutkan prefiks
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
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-star me-1"></i> Kelas</label>
                                    <select class="form-select" name="kelas" id="kelas_dropdown">
                                        <option value="" disabled <?= empty($_POST['kelas']) ? 'selected' : '' ?>>Pilih...</option>
                                        <option value="III">III</option>
                                        <option value="II">II</option>
                                        <option value="I">I</option>
                                        <option value="VIP">VIP</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mb-3">
                    <div class="card p-3 identitas-card visible">
                        <div class="card-header bg-gray text-white d-flex align-items-center">
                            <i class="fas fa-ambulance me-2"></i>
                            <h6 class="mb-0 fw-bold">Detail Transportasi</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-user-plus me-1"></i> Dikirim oleh</label>
                                    <select class="form-select" name="dikirim_oleh">
                                        <option value="" disabled <?= empty($_POST['dikirim_oleh']) ? 'selected' : '' ?>>Pilih...</option>
                                        <option value="Sendiri" <?= ($_POST['dikirim_oleh'] ?? '') === 'Sendiri' ? 'selected' : '' ?>>Sendiri</option>
                                        <option value="Dokter/Bidan" <?= ($_POST['dikirim_oleh'] ?? '') === 'Dokter/Bidan' ? 'selected' : '' ?>>Dokter/Bidan</option>
                                        <option value="RS/PKM/BP" <?= ($_POST['dikirim_oleh'] ?? '') === 'RS/PKM/BP' ? 'selected' : '' ?>>RS/PKM/BP</option>
                                        <option value="Perusahaan" <?= ($_POST['dikirim_oleh'] ?? '') === 'Perusahaan' ? 'selected' : '' ?>>Perusahaan</option>
                                        <option value="Lainnya" <?= ($_POST['dikirim_oleh'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-users me-1"></i> Diantar oleh</label>
                                    <select class="form-select" name="diantar_oleh">
                                        <option value="" disabled <?= empty($_POST['diantar_oleh']) ? 'selected' : '' ?>>Pilih...</option>
                                        <option value="Sendiri" <?= ($_POST['diantar_oleh'] ?? '') === 'Sendiri' ? 'selected' : '' ?>>Sendiri</option>
                                        <option value="Keluarga" <?= ($_POST['diantar_oleh'] ?? '') === 'Keluarga' ? 'selected' : '' ?>>Keluarga</option>
                                        <option value="Polisi" <?= ($_POST['diantar_oleh'] ?? '') === 'Polisi' ? 'selected' : '' ?>>Polisi</option>
                                        <option value="Lainnya" <?= ($_POST['diantar_oleh'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label fw-bold text-gray"><i class="fas fa-car me-1"></i> Kendaraan Pengantar</label>
                                    <select class="form-select" name="kendaraan_pengantar">
                                        <option value="" disabled <?= empty($_POST['kendaraan_pengantar']) ? 'selected' : '' ?>>Pilih...</option>
                                        <option value="Ambulance" <?= ($_POST['kendaraan_pengantar'] ?? '') === 'Ambulance' ? 'selected' : '' ?>>Ambulance</option>
                                        <option value="Umum" <?= ($_POST['kendaraan_pengantar'] ?? '') === 'Umum' ? 'selected' : '' ?>>Umum</option>
                                        <option value="Pribadi" <?= ($_POST['kendaraan_pengantar'] ?? '') === 'Pribadi' ? 'selected' : '' ?>>Pribadi</option>
                                        <option value="Lainnya" <?= ($_POST['kendaraan_pengantar'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Prioritas Pasien & Kebutuhan Pasien") ?>
            <div class="row mb-4 d-flex align-items-stretch">
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="radio" name="prioritas" value="Prioritas 0" <?= isset($_POST['prioritas']) && $_POST['prioritas'] == 'Prioritas 0' ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-skull-crossbones me-2"></i>
                                <h6 class="mb-0">Prioritas 0</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0 small">Pasien sudah meninggal</p>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="radio" name="prioritas" value="Prioritas 1" <?= isset($_POST['prioritas']) && $_POST['prioritas'] == 'Prioritas 1' ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-ambulance me-2"></i>
                                <h6 class="mb-0">Prioritas 1</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2 small">Pilih Prioritas 1</p>
                                <ul class="priority-list">
                                    <li>Tersedak</li>
                                    <li>Cidera Kepala Berat</li>
                                    <li>Kejang</li>
                                    <li>Penurunan Kesadaran</li>
                                    <li>Kelainan Persalinan</li>
                                    <li>Serangan Jantung</li>
                                    <li>Lain - lain</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="radio" name="prioritas" value="Prioritas 2" <?= isset($_POST['prioritas']) && $_POST['prioritas'] == 'Prioritas 2' ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-medkit me-2"></i>
                                <h6 class="mb-0">Prioritas 2</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2 small">Pilih Prioritas 2</p>
                                <ul class="priority-list">
                                    <li>Luka Bakar</li>
                                    <li>Cidera Kepala Sedang</li>
                                    <li>Dehidrasi</li>
                                    <li>Muntah Terus menerus</li>
                                    <li>Hipertensi</li>
                                    <li>Trauma sedang</li>
                                    <li>Lain - lain</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="radio" name="prioritas" value="Prioritas 3" <?= isset($_POST['prioritas']) && $_POST['prioritas'] == 'Prioritas 3' ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-band-aid me-2"></i>
                                <h6 class="mb-0">Prioritas 3</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2 small">Pilih Prioritas 3</p>
                                <ul class="priority-list">
                                    <li>Dislokasi</li>
                                    <li>Patah Tulang tertutup</li>
                                    <li>Nyeri minimal</li>
                                    <li>Luka Minor / Lecet</li>
                                    <li>Muntah Tanpa dehidrasi</li>
                                    <li>Lain - lain</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            <div class="row mb-4 d-flex align-items-stretch">
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="checkbox" name="kebutuhan[]" value="Preventif" <?= isset($_POST['kebutuhan']) && in_array('Preventif', $_POST['kebutuhan']) ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-shield-alt me-2"></i>
                                <h6 class="mb-0">Preventif</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0 small">Preventif</p>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="checkbox" name="kebutuhan[]" value="Kuratif" <?= isset($_POST['kebutuhan']) && in_array('Kuratif', $_POST['kebutuhan']) ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-hand-holding-medical me-2"></i>
                                <h6 class="mb-0">Kuratif</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0 small">Kuratif</p>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="checkbox" name="kebutuhan[]" value="Rehabilitatif" <?= isset($_POST['kebutuhan']) && in_array('Rehabilitatif', $_POST['kebutuhan']) ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-heartbeat me-2"></i>
                                <h6 class="mb-0">Rehabilitatif</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0 small">Rehabilitatif</p>
                            </div>
                        </div>
                    </label>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="compact-selectable-card">
                        <input type="checkbox" name="kebutuhan[]" value="Paliatif" <?= isset($_POST['kebutuhan']) && in_array('Paliatif', $_POST['kebutuhan']) ? 'checked' : '' ?>>
                        <div class="card card-with-equal-height prioritas-card shadow-sm">
                            <div class="card-header bg-red text-white d-flex align-items-center">
                                <i class="fas fa-hand-holding-heart me-2"></i>
                                <h6 class="mb-0">Paliatif</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0 small">Paliatif</p>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <?= section("Survey Primer") ?>
            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-primer-card visible">
                        <div class="card-header bg-purple text-white d-flex align-items-center">
                            <i class="fas fa-lungs me-2"></i>
                            <h6 class="mb-0 fw-bold">Jalan Napas</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $jalan_napas_options = [
                                    "Paten",
                                    "Obstruksi partial",
                                    "Stridor",
                                    "Snoring",
                                    "Gurgling",
                                    "Obstruksi total",
                                    "Trauma jalan napas",
                                    "Risiko aspirasi",
                                    "Perdarahan / muntahan",
                                    "Benda asing"
                                ];
                                foreach ($jalan_napas_options as $option) {
                                    $is_checked = in_array($option, $_POST['jalan_napas'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="checkbox" name="jalan_napas[]" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <div style="margin-top: auto;"><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>
                                <hr class="my-2">
                                <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-check-circle me-1"></i> Kesimpulan</label>
                                <div class="row">
                                    <?php
                                    $kesimpulan_jalan_napas_options = ["Aman", "Mengancam nyawa"];
                                    foreach ($kesimpulan_jalan_napas_options as $option) {
                                        $is_checked = ($_POST['kesimpulan_jalan_napas'] ?? '') === $option ? 'checked' : '';
                                    ?>
                                        <div class="col-md-12 mb-2">
                                            <label class="compact-selectable-card">
                                                <input type="radio" name="kesimpulan_jalan_napas" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                                <div class="compact-card-content"><?= esc($option) ?></div>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-primer-card visible">
                        <div class="card-header bg-purple text-white d-flex align-items-center">
                            <i class="fas fa-wind me-2"></i>
                            <h6 class="mb-0 fw-bold">Pernapasan</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $pernapasan_options = [
                                    "Paten",
                                    "Tidak Spontan",
                                    "Reguler",
                                    "Irreguler",
                                    "Gerakan Dada Simetris",
                                    "Gerakan Dada Asimetris",
                                    "Jejas Dinding Dada"
                                ];
                                foreach ($pernapasan_options as $option) {
                                    $is_checked = in_array($option, $_POST['pernapasan'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="checkbox" name="pernapasan[]" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-lungs me-1"></i> Tipe Pernapasan</label>
                            <div class="row">
                                <?php
                                $tipe_pernapasan_options = [
                                    "Normal",
                                    "Takipneu",
                                    "Kussmaul",
                                    "Biot",
                                    "Hiperventilasi",
                                    "Cheyne Stoke",
                                    "Apneustic"
                                ];
                                foreach ($tipe_pernapasan_options as $option) {
                                    $is_checked = in_array($option, $_POST['tipe_pernapasan'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="checkbox" name="tipe_pernapasan[]" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-stethoscope me-1"></i> Auskultasi</label>
                            <div class="row">
                                <?php
                                $auskultasi_options = ["Rhonki", "Wheezing"];
                                foreach ($auskultasi_options as $option) {
                                    $is_checked = in_array($option, $_POST['auskultasi_pernapasan'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="checkbox" name="auskultasi_pernapasan[]" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <div style="margin-top: auto;">
                                <hr class="my-2">
                                <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-check-circle me-1"></i> Kesimpulan</label>
                                <div class="row">
                                    <?php
                                    $kesimpulan_pernapasan_options = ["Aman", "Mengancam nyawa"];
                                    foreach ($kesimpulan_pernapasan_options as $option) {
                                        $is_checked = ($_POST['kesimpulan_pernapasan'] ?? '') === $option ? 'checked' : '';
                                    ?>
                                        <div class="col-md-12 mb-2">
                                            <label class="compact-selectable-card">
                                                <input type="radio" name="kesimpulan_pernapasan" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                                <div class="compact-card-content"><?= esc($option) ?></div>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-primer-card visible">
                        <div class="card-header bg-purple text-white d-flex align-items-center">
                            <i class="fas fa-heartbeat me-2"></i>
                            <h6 class="mb-0 fw-bold">Sirkulasi</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $sirkulasi_options = [
                                    "Nadi Kuat",
                                    "Nadi Lemah",
                                    "Reguler",
                                    "Irreguler"
                                ];
                                foreach ($sirkulasi_options as $option) {
                                    $is_checked = in_array($option, $_POST['sirkulasi'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="checkbox" name="sirkulasi[]" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-hand-holding-heart me-1"></i> Kulit / Mukosa</label>
                            <div class="row">
                                <?php
                                $kulit_mukosa_options = [
                                    "Normal",
                                    "Pucat",
                                    "Jaundice",
                                    "Sianosis",
                                    "Berkeringat"
                                ];
                                foreach ($kulit_mukosa_options as $option) {
                                    $is_checked = in_array($option, $_POST['kulit_mukosa'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="checkbox" name="kulit_mukosa[]" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-thermometer me-1"></i> Akral</label>
                            <div class="row">
                                <?php
                                $akral_options = ["Hangat", "Dingin", "Kering", "Basah"];
                                foreach ($akral_options as $option) {
                                    $is_checked = in_array($option, $_POST['akral'] ?? []) ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="checkbox" name="akral[]" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-stopwatch me-1"></i> CRT</label>
                            <div class="row">
                                <?php
                                $crt_options = ["< 2 Detik", "> 2 Detik"];
                                foreach ($crt_options as $option) {
                                    $is_checked = ($_POST['crt'] ?? '') === $option ? 'checked' : '';
                                ?>
                                    <div class="col-md-12 mb-2">
                                        <label class="compact-selectable-card">
                                            <input type="radio" name="crt" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                            <div class="compact-card-content"><?= esc($option) ?></div>
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            <div style="margin-top: auto;"> <br>
                                <hr class="my-2">
                                <label class="form-label fw-bold text-purple mt-2"><i class="fas fa-check-circle me-1"></i> Kesimpulan</label>
                                <div class="row">
                                    <?php
                                    $kesimpulan_sirkulasi_options = ["Aman", "Mengancam nyawa"];
                                    foreach ($kesimpulan_sirkulasi_options as $option) {
                                        $is_checked = ($_POST['kesimpulan_sirkulasi'] ?? '') === $option ? 'checked' : '';
                                    ?>
                                        <div class="col-md-12 mb-2">
                                            <label class="compact-selectable-card">
                                                <input type="radio" name="kesimpulan_sirkulasi" value="<?= esc($option) ?>" <?= $is_checked ?>>
                                                <div class="compact-card-content"><?= esc($option) ?></div>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Tanda Vital") ?>
            <div class="row mb-2">
                <div class="col-12">
                    <div class="card p-3 vital-signs-card">
                        <div class="card-header bg-purple text-white d-flex align-items-center">
                            <i class="fas fa-vials me-2"></i>
                            <h6 class="mb-0 fw-bold">Tanda Vital</h6>
                        </div>
                        <div class="card-body vital-signs-input-group">
                            <div class="row">
                                <div class="col-md col-6 mb-2">
                                    <label class="form-label fw-bold text-purple">
                                        <i class="fas fa-brain me-1"></i> GCS
                                    </label>
                                    <input type="number" class="form-control" name="gcs" placeholder="...">
                                </div>
                                <div class="col-md col-6 mb-2">
                                    <label class="form-label fw-bold text-purple">
                                        <i class="fas fa-heartbeat me-1"></i> TD (mmHg)
                                    </label>
                                    <input type="number" class="form-control" name="td" placeholder="...">
                                </div>
                                <div class="col-md col-6 mb-2">
                                    <label class="form-label fw-bold text-purple">
                                        <i class="fas fa-pulse me-1"></i> Nadi (/menit)
                                    </label>
                                    <input type="number" class="form-control" name="nadi" placeholder="...">
                                </div>
                                <div class="col-md col-6 mb-2">
                                    <label class="form-label fw-bold text-purple">
                                        <i class="fas fa-lungs me-1"></i> RR (/menit)
                                    </label>
                                    <input type="number" class="form-control" name="rr" placeholder="...">
                                </div>
                                <div class="col-md col-6 mb-2">
                                    <label class="form-label fw-bold text-purple">
                                        <i class="fas fa-thermometer-half me-1"></i> Suhu (°C)
                                    </label>
                                    <input type="number" class="form-control" name="suhu" placeholder="...">
                                </div>
                                <div class="col-md col-6 mb-2">
                                    <label class="form-label fw-bold text-purple">
                                        <i class="fas fa-tint me-1"></i> SpO2 (%)
                                    </label>
                                    <input type="number" class="form-control" name="spo2" placeholder="...">
                                </div>
                                <div class="col-md col-6 mb-2">
                                    <label class="form-label fw-bold text-purple">
                                        <i class="fas fa-weight me-1"></i> BB (kg)
                                    </label>
                                    <input type="number" class="form-control" name="bb" placeholder="...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Subjektif") ?>
            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-6">
                    <div class="card p-3 h-100 subjektif-card">
                        <div class="card-header bg-info text-white d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <h6 class="mb-0 fw-bold">Subjektif</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-info"><i class="fas fa-comment-medical me-1"></i> Keluhan Utama</label>
                                <textarea class="form-control subjektif-textarea" name="keluhan_utama" rows="3" placeholder="Masukkan keluhan utama pasien..."></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-info"><i class="fas fa-history me-1"></i> Riwayat Penyakit Sekarang</label>
                                <textarea class="form-control subjektif-textarea" name="riwayat_penyakit_sekarang" rows="3" placeholder="Masukkan riwayat penyakit saat ini..."></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-info"><i class="fas fa-file-medical me-1"></i> Riwayat Penyakit Dahulu</label>
                                <textarea class="form-control subjektif-textarea" name="riwayat_penyakit_dahulu" rows="3" placeholder="Masukkan riwayat penyakit sebelumnya..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 h-100 subjektif-card">
                        <div class="card-header bg-info text-white d-flex align-items-center">
                            <i class="fas fa-user me-2"></i>
                            <h6 class="mb-0 fw-bold">Subjektif</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-info"><i class="fas fa-users me-1"></i> Riwayat Penyakit Keluarga</label>
                                <textarea class="form-control subjektif-textarea" name="riwayat_penyakit_keluarga" rows="3" placeholder="Masukkan riwayat penyakit keluarga..."></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-info"><i class="fas fa-prescription-bottle me-1"></i> Obat-obatan</label>
                                <textarea class="form-control subjektif-textarea" name="obat_obatan" rows="3" placeholder="Masukkan obat-obatan yang digunakan..."></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-info"><i class="fas fa-allergies me-1"></i> Alergi</label>
                                <textarea class="form-control subjektif-textarea" name="alergi" rows="3" placeholder="Masukkan riwayat alergi..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Survey Sekunder - Pemeriksaan Fisik (Objective)") ?>
            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-stethoscope me-2"></i>
                            <h6 class="mb-0 fw-bold">Keadaan Umum</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-heartbeat me-1"></i> Keadaan Umum</label>
                                <textarea class="form-control" rows="20" name="keadaan_umum"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-head-side-mask me-2"></i>
                            <h6 class="mb-0 fw-bold">Kepala & Wajah</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-brain me-1"></i> Kepala</label>
                                <textarea class="form-control" rows="2" name="kepala_wajah"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-eye me-1"></i> Konjungtiva</label>
                                <textarea class="form-control" rows="2" name="konjungtiva"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-eye me-1"></i> Sclera</label>
                                <textarea class="form-control" rows="2" name="sklera"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-lips me-1"></i> Bibir / Lidah</label>
                                <textarea class="form-control" rows="2" name="bibir_lidah"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-head-side-cough me-1"></i> Mukosa</label>
                                <textarea class="form-control" rows="2" name="mukosa"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-neck me-2"></i>
                            <h6 class="mb-0 fw-bold">Leher</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-neck me-1"></i> Leher</label>
                                <textarea class="form-control" rows="2" name="leher"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-lungs me-1"></i> Deviasi Trakea</label>
                                <textarea class="form-control" rows="2" name="deviasi_trakea"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-veins me-1"></i> JVP</label>
                                <textarea class="form-control" rows="2" name="jvp"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-lymph-nodes me-1"></i> LNN</label>
                                <textarea class="form-control" rows="2" name="lnn"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-thyroid me-1"></i> Tiroid</label>
                                <textarea class="form-control" rows="2" name="tiroid"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-lungs me-2"></i>
                            <h6 class="mb-0 fw-bold">Thorax</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-chest me-1"></i> Thorax</label>
                                <textarea class="form-control" rows="2" name="thorax"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-heart me-1"></i> Jantung</label>
                                <textarea class="form-control" rows="2" name="jantung"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-lungs me-1"></i> Paru</label>
                                <textarea class="form-control" rows="2" name="paru"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-abdomen me-2"></i>
                            <h6 class="mb-0 fw-bold">Abdomen & Pelvis</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-abdomen me-1"></i> Abdomen & Pelvis</label>
                                <textarea class="form-control" rows="4" name="abdomen_pelvis"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-spine me-1"></i> Punggung & Pinggang</label>
                                <textarea class="form-control" rows="4" name="punggung_pinggang"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-body me-2"></i>
                            <h6 class="mb-0 fw-bold">Genitalia & Ekstremitas</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-genitalia me-1"></i> Genitalia</label>
                                <textarea class="form-control" rows="4" name="genitalia_ekstremitas"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-bone me-1"></i> Ekstremitas</label>
                                <textarea class="form-control" rows="4" name="ekstremitas"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-clipboard-check me-2"></i>
                            <h6 class="mb-0 fw-bold">Pemeriksaan Lain</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <textarea class="form-control" rows="2" name="pemeriksaan_lain"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Pemeriksaan Penunjang") ?>
            <div class="row mb-4 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-vial me-2"></i>
                            <h6 class="mb-0 fw-bold">Laboratorium</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <textarea class="form-control" rows="2" name="laboratorium"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4 d-flex align-items-stretch">
                <div class="col-md-12">
                    <div class="card p-3 h-100 survey-sekunder-card visible">
                        <div class="card-header bg-teal text-white d-flex align-items-center">
                            <i class="fas fa-x-ray me-2"></i>
                            <h6 class="mb-0 fw-bold">Radiologi & Lain-lain</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-x-ray me-1"></i> CT Scan</label>
                                <textarea class="form-control" rows="2" name="ct_scan"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-x-ray me-1"></i> X-ray</label>
                                <textarea class="form-control" rows="2" name="x_ray"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-ultrasound me-1"></i> USG</label>
                                <textarea class="form-control" rows="2" name="usg"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-heartbeat me-1"></i> ECG</label>
                                <textarea class="form-control" rows="2" name="ecg"></textarea>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-bold text-teal"><i class="fas fa-notes-medical me-1"></i> Lain-lain</label>
                                <textarea class="form-control" rows="2" name="lain_lain_penunjang"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Assesmen & Planning") ?>
            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-6">
                    <div class="card p-3 h-100 assesmen-card">
                        <div class="card-header bg-primary text-white d-flex align-items-center">
                            <i class="fas fa-stethoscope me-2"></i>
                            <h6 class="mb-0 fw-bold">Assesmen</h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label fw-bold text-primary">Diagnosis Utama</label>
                            <textarea class="form-control mb-2 assesmen-textarea" rows="2" name="diagnosis_utama" placeholder="Masukkan diagnosis utama..."></textarea>

                            <label class="form-label fw-bold text-primary">Diagnosis Sekunder</label>
                            <textarea class="form-control assesmen-textarea" rows="6" name="diagnosis_sekunder" placeholder="Masukkan diagnosis sekunder..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 h-100 planning-card">
                        <div class="card-header bg-success text-white d-flex align-items-center">
                            <i class="fas fa-clipboard-list me-2"></i>
                            <h6 class="mb-0 fw-bold">Planning</h6>
                        </div>
                        <div class="card-body">
                            <label class="form-label fw-bold text-success">Tindakan dan Terapi</label>
                            <textarea class="form-control planning-textarea" rows="9" name="tindakan_terapi" placeholder="Masukkan tindakan dan terapi..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <?= section("Tindak Lanjut") ?>
            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-3">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-home me-2"></i>
                            <h6 class="mb-0 fw-bold">Pulang</h6>
                        </div>
                        <div class="card-body">
                            <label class="selectable-card w-100 h-100 text-center">
                                <input type="radio" class="form-check-input" name="keputusan_akhir" value="Pulang" <?= isset($_POST['keputusan_akhir']) && $_POST['keputusan_akhir'] === 'Pulang' ? 'checked' : '' ?>>
                                <div class="card-content">
                                    <span class="fw-bold">Pulang</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-ban me-2"></i>
                            <h6 class="mb-0 fw-bold">Menolak Tindakan</h6>
                        </div>
                        <div class="card-body">
                            <label class="selectable-card w-100 h-100 text-center">
                                <input type="radio" class="form-check-input" name="keputusan_akhir" value="Menolak tindakan" <?= isset($_POST['keputusan_akhir']) && $_POST['keputusan_akhir'] === 'Menolak tindakan' ? 'checked' : '' ?>>
                                <div class="card-content">
                                    <span class="fw-bold">Menolak Tindakan</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-hospital me-2"></i>
                            <h6 class="mb-0 fw-bold">MRS</h6>
                        </div>
                        <div class="card-body">
                            <label class="selectable-card w-100 h-100 text-center">
                                <input type="radio" class="form-check-input" name="keputusan_akhir" value="MRS" <?= isset($_POST['keputusan_akhir']) && $_POST['keputusan_akhir'] === 'MRS' ? 'checked' : '' ?>>
                                <div class="card-content">
                                    <span class="fw-bold">MRS</span>
                                </div>
                            </label>
                            <input type="text" class="form-control mt-1 mb-1" name="nama_ruang" id="nama_ruang" placeholder="Nama Ruang..." value="<?= esc($_POST['nama_ruang'] ?? '') ?>" disabled>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-ambulance me-2"></i>
                            <h6 class="mb-0 fw-bold">Dirujuk ke RS</h6>
                        </div>
                        <div class="card-body">
                            <label class="selectable-card w-100 h-100 text-center">
                                <input type="radio" class="form-check-input" name="keputusan_akhir" value="Dirujuk ke RS" <?= isset($_POST['keputusan_akhir']) && $_POST['keputusan_akhir'] === 'Dirujuk ke RS' ? 'checked' : '' ?>>
                                <div class="card-content">
                                    <span class="fw-bold">Dirujuk ke RS</span>
                                </div>
                            </label>
                            <input type="text" class="form-control mt-2" name="nama_rs" id="nama_rs" placeholder="Nama RS..." value="<?= esc($_POST['nama_rs'] ?? '') ?>" disabled>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-2 d-flex align-items-stretch">
                <div class="col-md-3">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-skull-crossbones me-2"></i>
                            <h6 class="mb-0 fw-bold">Meninggal</h6>
                        </div>
                        <div class="card-body">
                            <label class="selectable-card w-100 h-100 text-center">
                                <input type="radio" class="form-check-input" name="keputusan_akhir" value="Meninggal" <?= isset($_POST['keputusan_akhir']) && $_POST['keputusan_akhir'] === 'Meninggal' ? 'checked' : '' ?>>
                                <div class="card-content">
                                    <span class="fw-bold">Meninggal</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-heart-broken me-2"></i>
                            <h6 class="mb-0 fw-bold">DOA</h6>
                        </div>
                        <div class="card-body">
                            <label class="selectable-card w-100 h-100 text-center">
                                <input type="radio" class="form-check-input" name="keputusan_akhir" value="DOA" <?= isset($_POST['keputusan_akhir']) && $_POST['keputusan_akhir'] === 'DOA' ? 'checked' : '' ?>>
                                <div class="card-content">
                                    <span class="fw-bold">DOA</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 h-100 tindak-lanjut-card visible">
                        <div class="card-header bg-orange text-white d-flex align-items-center">
                            <i class="fas fa-user-md me-2"></i>
                            <h6 class="mb-0 fw-bold">Dokter yang Merawat / DPJP</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <select name="dokter_jaga" id="dokter_jaga" class="form-control" required>
                                    <option value="">Pilih Dokter Jaga</option>
                                    <?php
                                    try {
                                        $stmt = $pdo->query("SELECT kd_dokter, nm_dokter FROM dokter ORDER BY nm_dokter");
                                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<option value='" . htmlspecialchars($row['kd_dokter']) . "'>" . htmlspecialchars($row['nm_dokter']) . " (" . htmlspecialchars($row['kd_dokter']) . ")</option>";
                                        }
                                    } catch (PDOException $e) {
                                        error_log("Error fetching dokter_jaga: " . $e->getMessage());
                                        echo "<option value=''>Error: Tidak dapat memuat data dokter</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <button type="button" class="btn btn-info" onclick="printPDF()">Cetak PDF</button>
                <button type="reset" class="btn btn-warning">Reset Form</button>
            </div>
            <div class="button-group">
                <?php
                // Existing content of asesmen_awal.php (assumed)
                // Add this button where your other buttons are (e.g., inside or after the form)
                $no_rawat = isset($_GET['no_rawat']) ? $_GET['no_rawat'] : ''; // Ensure no_rawat is available
                ?>
                <!-- Existing buttons, e.g., Save, Cancel, etc. -->
                <a href="../pages/riwayat_pasien.php?no_rawat=<?php echo urlencode($no_rawat); ?>" class="btn">Lihat Riwayat Asesmen Pasien</a>
            </div>
    </div>
</div>
<script>
    document.getElementById('ruang_dropdown').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const kelas = selectedOption ? selectedOption.getAttribute('data-kelas') : '';
        const kelasDropdown = document.getElementById('kelas_dropdown');

        // Mapping kelas dari tabel kamar ke opsi dropdown
        const kelasMapping = {
            'Kelas 3': 'III',
            'Kelas 2': 'II',
            'Kelas 1': 'I',
            'VIP': 'VIP'
        };

        if (kelas && kelasMapping[kelas]) {
            kelasDropdown.value = kelasMapping[kelas]; // Isi otomatis kolom Kelas
        } else {
            kelasDropdown.value = ''; // Reset jika tidak ada kelas valid
        }
    });

    document.querySelectorAll('input[name=" keputusan_akhir"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const namaRuangInput = document.getElementById('nama_ruang');
            const namaRsInput = document.getElementById('nama_rs');

            // Aktifkan field nama_ruang hanya jika MRS dipilih
            if (this.value === 'MRS') {
                namaRuangInput.disabled = false;
            } else {
                namaRuangInput.disabled = true;
                namaRuangInput.value = '';
            }

            // Aktifkan field nama_rs hanya jika Dirujuk ke RS dipilih
            if (this.value === 'Dirujuk ke RS') {
                namaRsInput.disabled = false;
            } else {
                namaRsInput.disabled = true;
                namaRsInput.value = '';
            }
        });

        // Periksa status awal saat halaman dimuat
        if (radio.checked) {
            const namaRuangInput = document.getElementById('nama_ruang');
            const namaRsInput = document.getElementById('nama_rs');
            namaRuangInput.disabled = radio.value !== 'MRS';
            namaRsInput.disabled = radio.value !== 'Dirujuk ke RS';
        }
    });
</script>
<script src="../js/main.js"></script>

<script>
    function printPDF() {
        const form = document.getElementById('asesmenForm');
        form.action = '../action/cetak_pdf_asesmen_awal_medis_ranap.php';
        form.target = '_blank';
        form.submit();
        form.action = '../actions/save_asesmen_awal_medis_ranap.php';
        form.target = '';
    }
</script>