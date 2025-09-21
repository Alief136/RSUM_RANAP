<link href="../css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../css/navbar,css">

<?php
session_start();
require_once '../../config/db.php';
require_once '../partials/header.php';

// Definisi fungsi esc() jika belum ada
if (!function_exists('esc')) {
    function esc($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Fungsi helper untuk decode JSON aman (untuk kolom array)
if (!function_exists('decodeJsonSafe')) {
    function decodeJsonSafe($jsonString)
    {
        if (empty($jsonString)) return [];
        $decoded = json_decode($jsonString, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        // Fallback ke string jika bukan JSON valid
        return [trim($jsonString)];
    }
}

$no_rawat = isset($_GET['no_rawat']) ? trim($_GET['no_rawat']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';

if (empty($no_rawat)) {
    $_SESSION['message'] = "Nomor rawat tidak ditemukan.";
    header("Location: /"); // Arahkan ke halaman utama yang sesuai
    exit;
}

try {
    $sql = "SELECT 
                a.*, 
                p.nm_pasien, 
                p.tgl_lahir, 
                p.no_rkm_medis,
                r.tgl_registrasi,
                d1.nm_dokter AS nama_dokter_pj,
                d2.nm_dokter AS nama_dokter_jaga,
                pt.nama AS nama_perawat
            FROM 
                asesmen_awal_medis_ranap a 
            LEFT JOIN 
                reg_periksa r ON a.no_rawat = r.no_rawat 
            LEFT JOIN 
                pasien p ON r.no_rkm_medis = p.no_rkm_medis 
            LEFT JOIN
                dokter d1 ON a.kd_dokter = d1.kd_dokter
            LEFT JOIN
                dokter d2 ON a.dokter_jaga = d2.kd_dokter
            LEFT JOIN
                petugas pt ON a.nip_perawat = pt.nip
            WHERE 
                a.no_rawat = :no_rawat";

    if ($status_filter !== 'all') {
        $sql .= " AND a.status = :status";
    }

    $sql .= " ORDER BY a.tgl_input DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':no_rawat', $no_rawat, PDO::PARAM_STR);

    if ($status_filter !== 'all') {
        $stmt->bindValue(':status', $status_filter, PDO::PARAM_STR);
    }

    $stmt->execute();
    $riwayats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['message'] = "Error saat mengambil data riwayat: " . $e->getMessage();
    $riwayats = [];
}

$pasien_info = null;
if (!empty($riwayats)) {
    $pasien_info = [
        'nm_pasien' => $riwayats[0]['nm_pasien'],
        'no_rkm_medis' => $riwayats[0]['no_rkm_medis'] ?? 'N/A'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Riwayat Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/pagesStyle.css">
</head>

<body>
    <div class="container mt-4">
        <h2 class="mb-4">Riwayat Asesmen Pasien -
            <?php if ($pasien_info): ?>
                <?= esc($pasien_info['nm_pasien']) ?> (No. Rawat: <?= esc($no_rawat) ?>)
            <?php else: ?>
                <?= esc($no_rawat) ?>
            <?php endif; ?>
        </h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-info"><?= esc($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <form method="GET" class="mb-4">
            <input type="hidden" name="no_rawat" value="<?= esc($no_rawat) ?>">
            <div class="row">
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="Aktif" <?= $status_filter === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="Non Aktif" <?= $status_filter === 'Non Aktif' ? 'selected' : '' ?>>Non Aktif</option>
                    </select>
                </div>
            </div>
        </form>

        <?php if (!empty($riwayats)): ?>
            <div class="accordion" id="riwayatAccordion">
                <?php foreach ($riwayats as $index => $riwayat):
                    // Dekode JSON dengan aman
                    $jalan_napas = decodeJsonSafe($riwayat['jalan_napas'] ?? '[]');
                    $pernapasan = decodeJsonSafe($riwayat['pernapasan'] ?? '[]');
                    $tipe_pernapasan = decodeJsonSafe($riwayat['tipe_pernapasan'] ?? '[]');
                    $auskultasi_pernapasan = decodeJsonSafe($riwayat['auskultasi_pernapasan'] ?? '[]');
                    $sirkulasi = decodeJsonSafe($riwayat['sirkulasi'] ?? '[]');
                    $kulit_mukosa = decodeJsonSafe($riwayat['kulit_mukosa'] ?? '[]');
                    $akral = decodeJsonSafe($riwayat['akral'] ?? '[]');
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= $index ?>">
                            <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>">
                                <strong>
                                    <?= esc($riwayat['tgl_input']) ?> | Status:
                                    <span class="badge bg-<?= $riwayat['status'] === 'Aktif' ? 'success' : 'secondary' ?>">
                                        <?= esc($riwayat['status']) ?>
                                    </span>
                                </strong>
                            </button>
                        </h2>
                        <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" data-bs-parent="#riwayatAccordion">
                            <div class="accordion-body">

                                <!-- Identitas Pasien -->
                                <?php if ($pasien_info): ?>
                                    <div class="card mb-4 shadow-sm">
                                        <div class="card-body">
                                            <h5 class="fw-bold mb-2"><?= esc($pasien_info['nm_pasien']) ?></h5>
                                            <p class="mb-0">
                                                <strong>No. RM:</strong> <?= esc($pasien_info['no_rkm_medis']) ?> |
                                                <strong>No. Rawat:</strong> <?= esc($no_rawat) ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <hr>

                                <h5 class="fw-bold">Petugas & Detail Penerimaan</h5>
                                <p><strong>Dokter PJ:</strong> <?= esc($riwayat['nama_dokter_pj'] ?? 'N/A') ?> (<?= esc($riwayat['kd_dokter'] ?? 'N/A') ?>)</p>
                                <p><strong>Dokter Jaga:</strong> <?= esc($riwayat['nama_dokter_jaga'] ?? 'N/A') ?> (<?= esc($riwayat['dokter_jaga'] ?? 'N/A') ?>)</p>
                                <p><strong>Perawat:</strong> <?= esc($riwayat['nama_perawat'] ?? 'N/A') ?> (<?= esc($riwayat['nip_perawat'] ?? 'N/A') ?>)</p>
                                <p><strong>Tanggal Masuk:</strong> <?= esc($riwayat['tgl_masuk'] ?? 'N/A') ?></p>
                                <p><strong>Jam Masuk:</strong> <?= esc($riwayat['jam_masuk'] ?? 'N/A') ?></p>
                                <p><strong>Ruang:</strong> <?= esc($riwayat['ruang'] ?? 'N/A') ?></p>
                                <p><strong>Kelas:</strong> <?= esc($riwayat['kelas'] ?? 'N/A') ?></p>
                                <p><strong>Dikirim oleh:</strong> <?= esc($riwayat['dikirim_oleh'] ?? 'N/A') ?></p>
                                <p><strong>Diantar oleh:</strong> <?= esc($riwayat['diantar_oleh'] ?? 'N/A') ?></p>
                                <p><strong>Kendaraan:</strong> <?= esc($riwayat['kendaraan_pengantar'] ?? 'N/A') ?></p>

                                <hr>

                                <h5 class="fw-bold">Prioritas & Kebutuhan</h5>
                                <p><strong>Prioritas:</strong>
                                    <?php
                                    $prioritas_list = [];
                                    if (!empty($riwayat['prioritas_0'])) $prioritas_list[] = $riwayat['prioritas_0'];
                                    if (!empty($riwayat['prioritas_1'])) $prioritas_list[] = $riwayat['prioritas_1'];
                                    if (!empty($riwayat['prioritas_2'])) $prioritas_list[] = $riwayat['prioritas_2'];
                                    if (!empty($riwayat['prioritas_3'])) $prioritas_list[] = $riwayat['prioritas_3'];
                                    echo empty($prioritas_list) ? 'N/A' : implode(', ', array_map('esc', $prioritas_list));
                                    ?>
                                </p>
                                <p><strong>Kebutuhan:</strong>
                                    <?php
                                    $kebutuhan_list = [];
                                    if ($riwayat['preventif'] === 'Ya') $kebutuhan_list[] = 'Preventif';
                                    if ($riwayat['kuratif'] === 'Ya') $kebutuhan_list[] = 'Kuratif';
                                    if ($riwayat['rehabilitatif'] === 'Ya') $kebutuhan_list[] = 'Rehabilitatif';
                                    if ($riwayat['paliatif'] === 'Ya') $kebutuhan_list[] = 'Paliatif';
                                    echo empty($kebutuhan_list) ? 'N/A' : implode(', ', $kebutuhan_list);
                                    ?>
                                </p>

                                <hr>

                                <h5 class="fw-bold">Survey Primer</h5>
                                <p><strong>Jalan Napas:</strong>
                                    <?= !empty($jalan_napas) && is_array($jalan_napas) ? implode(', ', array_map('esc', $jalan_napas)) : esc($riwayat['jalan_napas'] ?? 'N/A') ?>
                                </p>
                                <p><strong>Kesimpulan Airway:</strong> <?= esc($riwayat['kesimpulan_airway'] ?? 'N/A') ?></p>
                                <p><strong>Pernapasan:</strong>
                                    <?= !empty($pernapasan) && is_array($pernapasan) ? implode(', ', array_map('esc', $pernapasan)) : esc($riwayat['pernapasan'] ?? 'N/A') ?>
                                </p>
                                <p><strong>Tipe Pernapasan:</strong>
                                    <?= !empty($tipe_pernapasan) && is_array($tipe_pernapasan) ? implode(', ', array_map('esc', $tipe_pernapasan)) : esc($riwayat['tipe_pernapasan'] ?? 'N/A') ?>
                                </p>
                                <p><strong>Auskultasi:</strong>
                                    <?= !empty($auskultasi_pernapasan) && is_array($auskultasi_pernapasan) ? implode(', ', array_map('esc', $auskultasi_pernapasan)) : esc($riwayat['auskultasi_pernapasan'] ?? 'N/A') ?>
                                </p>
                                <p><strong>Sirkulasi:</strong>
                                    <?= !empty($sirkulasi) && is_array($sirkulasi) ? implode(', ', array_map('esc', $sirkulasi)) : esc($riwayat['sirkulasi'] ?? 'N/A') ?>
                                </p>
                                <p><strong>Kulit/Mukosa:</strong>
                                    <?= !empty($kulit_mukosa) && is_array($kulit_mukosa) ? implode(', ', array_map('esc', $kulit_mukosa)) : esc($riwayat['kulit_mukosa'] ?? 'N/A') ?>
                                </p>
                                <p><strong>Akral:</strong>
                                    <?= !empty($akral) && is_array($akral) ? implode(', ', array_map('esc', $akral)) : esc($riwayat['akral'] ?? 'N/A') ?>
                                </p>

                                <hr>

                                <h5 class="fw-bold">Tanda Vital</h5>
                                <p><strong>GCS:</strong> <?= esc($riwayat['gcs'] ?? 'N/A') ?></p>
                                <p><strong>TD:</strong> <?= esc($riwayat['td'] ?? 'N/A') ?> mmHg</p>
                                <p><strong>Nadi:</strong> <?= esc($riwayat['nadi'] ?? 'N/A') ?> /menit</p>
                                <p><strong>RR:</strong> <?= esc($riwayat['rr'] ?? 'N/A') ?> /menit</p>
                                <p><strong>Suhu:</strong> <?= esc($riwayat['suhu'] ?? 'N/A') ?> °C</p>
                                <p><strong>SpO2:</strong> <?= esc($riwayat['spo2'] ?? 'N/A') ?> %</p>
                                <p><strong>BB:</strong> <?= esc($riwayat['bb'] ?? 'N/A') ?> kg</p>

                                <hr>

                                <h5 class="fw-bold">Subjektif</h5>
                                <p><strong>Keluhan Utama:</strong> <?= nl2br(esc($riwayat['keluhan_utama'] ?? 'N/A')) ?></p>
                                <p><strong>Riwayat Penyakit Sekarang:</strong> <?= nl2br(esc($riwayat['riwayat_penyakit_sekarang'] ?? 'N/A')) ?></p>
                                <p><strong>Riwayat Penyakit Dahulu:</strong> <?= nl2br(esc($riwayat['riwayat_penyakit_dahulu'] ?? 'N/A')) ?></p>
                                <p><strong>Riwayat Penyakit Keluarga:</strong> <?= nl2br(esc($riwayat['riwayat_penyakit_keluarga'] ?? 'N/A')) ?></p>
                                <p><strong>Obat-obatan:</strong> <?= nl2br(esc($riwayat['obat_obatan'] ?? 'N/A')) ?></p>
                                <p><strong>Alergi:</strong> <?= nl2br(esc($riwayat['alergi'] ?? 'N/A')) ?></p>

                                <hr>

                                <h5 class="fw-bold">Pemeriksaan Fisik</h5>
                                <p><strong>Keadaan Umum:</strong> <?= nl2br(esc($riwayat['keadaan_umum'] ?? 'N/A')) ?></p>
                                <p><strong>Kepala & Wajah:</strong> <?= nl2br(esc($riwayat['kepala_wajah'] ?? 'N/A')) ?></p>
                                <p><strong>Konjungtiva:</strong> <?= nl2br(esc($riwayat['konjungtiva'] ?? 'N/A')) ?></p>
                                <p><strong>Sklera:</strong> <?= nl2br(esc($riwayat['sklera'] ?? 'N/A')) ?></p>
                                <p><strong>Bibir / Lidah:</strong> <?= nl2br(esc($riwayat['bibir_lidah'] ?? 'N/A')) ?></p>
                                <p><strong>Mukosa:</strong> <?= nl2br(esc($riwayat['mukosa'] ?? 'N/A')) ?></p>
                                <p><strong>Leher:</strong> <?= nl2br(esc($riwayat['leher'] ?? 'N/A')) ?></p>
                                <p><strong>Deviasi Trakea:</strong> <?= nl2br(esc($riwayat['deviasi_trakea'] ?? 'N/A')) ?></p>
                                <p><strong>JVP:</strong> <?= nl2br(esc($riwayat['jvp'] ?? 'N/A')) ?></p>
                                <p><strong>LNN:</strong> <?= nl2br(esc($riwayat['lnn'] ?? 'N/A')) ?></p>
                                <p><strong>Tiroid:</strong> <?= nl2br(esc($riwayat['tiroid'] ?? 'N/A')) ?></p>
                                <p><strong>Thorax:</strong> <?= nl2br(esc($riwayat['thorax'] ?? 'N/A')) ?></p>
                                <p><strong>Jantung:</strong> <?= nl2br(esc($riwayat['jantung'] ?? 'N/A')) ?></p>
                                <p><strong>Paru:</strong> <?= nl2br(esc($riwayat['paru'] ?? 'N/A')) ?></p>
                                <p><strong>Abdomen & Pelvis:</strong> <?= nl2br(esc($riwayat['abdomen_pelvis'] ?? 'N/A')) ?></p>
                                <p><strong>Punggung & Pinggang:</strong> <?= nl2br(esc($riwayat['punggung_pinggang'] ?? 'N/A')) ?></p>
                                <p><strong>Genitalia & Ekstremitas:</strong> <?= nl2br(esc($riwayat['genitalia_ekstremitas'] ?? 'N/A')) ?></p>
                                <p><strong>Ekstremitas:</strong> <?= nl2br(esc($riwayat['ekstremitas'] ?? 'N/A')) ?></p>
                                <p><strong>Pemeriksaan Lain:</strong> <?= nl2br(esc($riwayat['pemeriksaan_lain'] ?? 'N/A')) ?></p>

                                <hr>

                                <h5 class="fw-bold">Pemeriksaan Penunjang</h5>
                                <p><strong>Laboratorium:</strong> <?= nl2br(esc($riwayat['laboratorium'] ?? 'N/A')) ?></p>
                                <p><strong>CT Scan:</strong> <?= nl2br(esc($riwayat['ct_scan'] ?? 'N/A')) ?></p>
                                <p><strong>X-ray:</strong> <?= nl2br(esc($riwayat['x_ray'] ?? 'N/A')) ?></p>
                                <p><strong>USG:</strong> <?= nl2br(esc($riwayat['usg'] ?? 'N/A')) ?></p>
                                <p><strong>ECG:</strong> <?= nl2br(esc($riwayat['ecg'] ?? 'N/A')) ?></p>
                                <p><strong>Lain-lain:</strong> <?= nl2br(esc($riwayat['lain_lain_penunjang'] ?? 'N/A')) ?></p>

                                <hr>

                                <h5 class="fw-bold">Assesmen & Planning</h5>
                                <p><strong>Diagnosis Utama:</strong> <?= nl2br(esc($riwayat['diagnosis_utama'] ?? 'N/A')) ?></p>
                                <p><strong>Diagnosis Sekunder:</strong> <?= nl2br(esc($riwayat['diagnosis_sekunder'] ?? 'N/A')) ?></p>
                                <p><strong>Tindakan dan Terapi:</strong> <?= nl2br(esc($riwayat['tindakan_terapi'] ?? 'N/A')) ?></p>

                                <hr>

                                <h5 class="fw-bold">Tindak Lanjut</h5>
                                <p><strong>Keputusan Akhir:</strong> <?= esc($riwayat['keputusan_akhir'] ?? 'N/A') ?></p>
                                <p><strong>Nama Ruang:</strong> <?= esc($riwayat['nama_ruang'] ?? 'N/A') ?></p>
                                <p><strong>Nama RS Rujukan:</strong> <?= esc($riwayat['nama_rs'] ?? 'N/A') ?></p>

                                <div class="mt-3">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-muted">Tidak ada riwayat asesmen ditemukan.</p>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="asesmen_awal.php?no_rawat=<?= urlencode($no_rawat) ?>" class="btn btn-primary">Kembali ke Asesmen</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>