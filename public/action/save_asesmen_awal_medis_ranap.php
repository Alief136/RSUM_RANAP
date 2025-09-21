<?php
// save_asesmen_awal_medis_ranap.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';

try {
    // Ambil data dari POST
    $no_rawat = $_POST['no_rawat'] ?? '';
    $no_rkm_medis = $_POST['no_rkm_medis'] ?? '';
    $kd_dokter = $_POST['kd_dokter'] ?? '';
    $nip_perawat = $_POST['nip_perawat'] ?? '';
    $dokter_jaga = $_POST['dokter_jaga'] ?? '';
    $keluhan_utama = $_POST['keluhan_utama'] ?? '';


    $jalan_napas = isset($_POST['jalan_napas']) ? json_encode($_POST['jalan_napas'], JSON_UNESCAPED_UNICODE) : '';
    $kesimpulan_jalan_napas = $_POST['kesimpulan_jalan_napas'] ?? '';
    $gcs = $_POST['gcs'] ?? null;
    $tgl_masuk = $_POST['tgl_masuk'] ?? null;
    $jam_masuk = $_POST['jam_masuk'] ?? null;
    $ruang = $_POST['ruang'] ?? '';
    $kelas = $_POST['kelas'] ?? '';
    $dikirim_oleh = $_POST['dikirim_oleh'] ?? '';
    $diantar_oleh = $_POST['diantar_oleh'] ?? '';
    $kendaraan_pengantar = $_POST['kendaraan_pengantar'] ?? '';
    $prioritas = $_POST['prioritas'] ?? '';
    $prioritas_0 = $prioritas === 'Prioritas 0' ? 'Prioritas 0' : '';
    $prioritas_1 = $prioritas === 'Prioritas 1' ? 'Prioritas 1' : '';
    $prioritas_2 = $prioritas === 'Prioritas 2' ? 'Prioritas 2' : '';
    $prioritas_3 = $prioritas === 'Prioritas 3' ? 'Prioritas 3' : '';
    $preventif = isset($_POST['kebutuhan']) && in_array('Preventif', $_POST['kebutuhan']) ? 'Ya' : '';
    $kuratif = isset($_POST['kebutuhan']) && in_array('Kuratif', $_POST['kebutuhan']) ? 'Ya' : '';
    $rehabilitatif = isset($_POST['kebutuhan']) && in_array('Rehabilitatif', $_POST['kebutuhan']) ? 'Ya' : '';
    $paliatif = isset($_POST['kebutuhan']) && in_array('Paliatif', $_POST['kebutuhan']) ? 'Ya' : '';


    $pernapasan = isset($_POST['pernapasan']) ? json_encode($_POST['pernapasan'], JSON_UNESCAPED_UNICODE) : '';
    $tipe_pernapasan = isset($_POST['tipe_pernapasan']) ? json_encode($_POST['tipe_pernapasan'], JSON_UNESCAPED_UNICODE) : '';
    $auskultasi_pernapasan = isset($_POST['auskultasi_pernapasan']) ? json_encode($_POST['auskultasi_pernapasan'], JSON_UNESCAPED_UNICODE) : '';
    $kesimpulan_pernapasan = $_POST['kesimpulan_pernapasan'] ?? '';

    $sirkulasi = isset($_POST['sirkulasi']) ? json_encode($_POST['sirkulasi'], JSON_UNESCAPED_UNICODE) : '';
    $kulit_mukosa = isset($_POST['kulit_mukosa']) ? json_encode($_POST['kulit_mukosa'], JSON_UNESCAPED_UNICODE) : '';
    $akral = isset($_POST['akral']) ? json_encode($_POST['akral'], JSON_UNESCAPED_UNICODE) : '';
    $crt = $_POST['crt'] ?? '';
    $kesimpulan_sirkulasi = $_POST['kesimpulan_sirkulasi'] ?? '';

    $td = $_POST['td'] ?? null;
    $nadi = $_POST['nadi'] ?? null;
    $rr = $_POST['rr'] ?? null;
    $suhu = $_POST['suhu'] ?? null;
    $spo2 = $_POST['spo2'] ?? null;
    $bb = $_POST['bb'] ?? null;

    $riwayat_penyakit_sekarang = $_POST['riwayat_penyakit_sekarang'] ?? '';
    $riwayat_penyakit_dahulu = $_POST['riwayat_penyakit_dahulu'] ?? '';
    $riwayat_penyakit_keluarga = $_POST['riwayat_penyakit_keluarga'] ?? '';
    $obat_obatan = $_POST['obat_obatan'] ?? '';
    $alergi = $_POST['alergi'] ?? '';

    $keadaan_umum = $_POST['keadaan_umum'] ?? '';
    $kepala_wajah = $_POST['kepala_wajah'] ?? '';
    $konjungtiva = $_POST['konjungtiva'] ?? '';
    $sklera = $_POST['sklera'] ?? '';
    $bibir_lidah = $_POST['bibir_lidah'] ?? '';
    $mukosa = $_POST['mukosa'] ?? '';
    $leher = $_POST['leher'] ?? '';
    $deviasi_trakea = $_POST['deviasi_trakea'] ?? '';
    $jvp = $_POST['jvp'] ?? '';
    $lnn = $_POST['lnn'] ?? '';
    $tiroid = $_POST['tiroid'] ?? '';
    $thorax = $_POST['thorax'] ?? '';
    $jantung = $_POST['jantung'] ?? '';
    $paru = $_POST['paru'] ?? '';
    $abdomen_pelvis = $_POST['abdomen_pelvis'] ?? '';
    $punggung_pinggang = $_POST['punggung_pinggang'] ?? '';
    $genitalia_ekstremitas = $_POST['genitalia_ekstremitas'] ?? '';
    $ekstremitas = $_POST['ekstremitas'] ?? '';
    $pemeriksaan_lain = $_POST['pemeriksaan_lain'] ?? '';

    $laboratorium = $_POST['laboratorium'] ?? '';
    $ct_scan = $_POST['ct_scan'] ?? '';
    $x_ray = $_POST['x_ray'] ?? '';
    $usg = $_POST['usg'] ?? '';
    $ecg = $_POST['ecg'] ?? '';
    $lain_lain_penunjang = $_POST['lain_lain_penunjang'] ?? '';

    $diagnosis_utama = $_POST['diagnosis_utama'] ?? '';
    $diagnosis_sekunder = $_POST['diagnosis_sekunder'] ?? '';
    $tindakan_terapi = $_POST['tindakan_terapi'] ?? '';
    $keputusan_akhir = $_POST['keputusan_akhir'] ?? '';
    $nama_ruang = $_POST['nama_ruang'] ?? '';
    $nama_rs = $_POST['nama_rs'] ?? '';

    // Validasi no_rawat
    if (empty($no_rawat)) {
        throw new Exception("Nomor rawat tidak valid.");
    }

    // Mulai transaksi
    $pdo->beginTransaction();

    // Nonaktifkan semua asesmen lama untuk no_rawat ini
    $update_old = "UPDATE asesmen_awal_medis_ranap SET status = 'Non Aktif' WHERE no_rawat = ? AND status = 'Aktif'";
    $stmt_old = $pdo->prepare($update_old);
    $stmt_old->execute([$no_rawat]);

    // Set status ke 'Aktif' secara otomatis
    $status = 'Aktif';

    // Query INSERT (tgl_input otomatis dari DEFAULT current_timestamp())
    $sql = "INSERT INTO asesmen_awal_medis_ranap (
        no_rawat, no_rkm_medis, kd_dokter, nip_perawat, dokter_jaga,
        keluhan_utama, jalan_napas, kesimpulan_jalan_napas, gcs, tgl_masuk, jam_masuk,
        ruang, kelas, dikirim_oleh, diantar_oleh, kendaraan_pengantar,
        prioritas_0, prioritas_1, prioritas_2, prioritas_3,
        preventif, kuratif, rehabilitatif, paliatif,
        pernapasan, tipe_pernapasan, auskultasi_pernapasan, kesimpulan_pernapasan,
        sirkulasi, kulit_mukosa, akral, crt, kesimpulan_sirkulasi,
        td, nadi, rr, suhu, spo2, bb,
        riwayat_penyakit_sekarang, riwayat_penyakit_dahulu, riwayat_penyakit_keluarga,
        obat_obatan, alergi, keadaan_umum, kepala_wajah, konjungtiva, sklera,
        bibir_lidah, mukosa, leher, deviasi_trakea, jvp, lnn, tiroid,
        thorax, jantung, paru, abdomen_pelvis, punggung_pinggang,
        genitalia_ekstremitas, ekstremitas, pemeriksaan_lain,
        laboratorium, ct_scan, x_ray, usg, ecg, lain_lain_penunjang,
        diagnosis_utama, diagnosis_sekunder, tindakan_terapi,
        keputusan_akhir, nama_ruang, nama_rs, status
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, 
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?, ?, ?,
        ?, ?, ?,
        ?, ?, ?, ?
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $no_rawat,
        $no_rkm_medis,
        $kd_dokter,
        $nip_perawat,
        $dokter_jaga,
        $keluhan_utama,
        $jalan_napas,
        $kesimpulan_jalan_napas,
        $gcs,
        $tgl_masuk,
        $jam_masuk,
        $ruang,
        $kelas,
        $dikirim_oleh,
        $diantar_oleh,
        $kendaraan_pengantar,
        $prioritas_0,
        $prioritas_1,
        $prioritas_2,
        $prioritas_3,
        $preventif,
        $kuratif,
        $rehabilitatif,
        $paliatif,
        $pernapasan,
        $tipe_pernapasan,
        $auskultasi_pernapasan,
        $kesimpulan_pernapasan,
        $sirkulasi,
        $kulit_mukosa,
        $akral,
        $crt,
        $kesimpulan_sirkulasi,
        $td,
        $nadi,
        $rr,
        $suhu,
        $spo2,
        $bb,
        $riwayat_penyakit_sekarang,
        $riwayat_penyakit_dahulu,
        $riwayat_penyakit_keluarga,
        $obat_obatan,
        $alergi,
        $keadaan_umum,
        $kepala_wajah,
        $konjungtiva,
        $sklera,
        $bibir_lidah,
        $mukosa,
        $leher,
        $deviasi_trakea,
        $jvp,
        $lnn,
        $tiroid,
        $thorax,
        $jantung,
        $paru,
        $abdomen_pelvis,
        $punggung_pinggang,
        $genitalia_ekstremitas,
        $ekstremitas,
        $pemeriksaan_lain,
        $laboratorium,
        $ct_scan,
        $x_ray,
        $usg,
        $ecg,
        $lain_lain_penunjang,
        $diagnosis_utama,
        $diagnosis_sekunder,
        $tindakan_terapi,
        $keputusan_akhir,
        $nama_ruang,
        $nama_rs,
        $status
    ]);

    // Commit transaksi
    $pdo->commit();

    // Redirect kembali ke form dengan success message
    $message = urlencode('Asesmen berhasil disimpan dengan status Aktif.');
    header("Location: ../pages/asesmen_awal.php?no_rkm_medis=" . urlencode($no_rkm_medis) .
        "&no_rawat=" . urlencode($no_rawat) .
        "&status=success&message=" . $message);

    exit;
} catch (Exception $e) {
    // Rollback jika ada error
    $pdo->rollBack();
    // Handle error
    error_log("Error saving asesmen: " . $e->getMessage());
    $message = urlencode('Gagal menyimpan asesmen: ' . $e->getMessage());
    header("Location: ../pages/asesmen_awal.php?no_rkm_medis=" . urlencode($no_rkm_medis) .
        "&no_rawat=" . urlencode($no_rawat) .
        "&status=error&message=" . $message);

    exit;
}
