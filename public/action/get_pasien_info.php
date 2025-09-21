<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config/db.php'; // Pastikan db.php ada di config/

// Helper function esc (untuk keamanan output)
if (!function_exists('esc')) {
    function esc($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Ambil data pasien berdasarkan no_rawat atau no_rkm_medis
 * @param PDO $pdo Koneksi database
 * @param string $no_rawat Nomor rawat dari GET/POST
 * @param string $no_rkm_medis Nomor rekam medis dari GET/POST
 * @return array Data pasien dan umur
 */
function getPatientData($pdo, $no_rawat = '', $no_rkm_medis = '')
{
    date_default_timezone_set('Asia/Jakarta');

    // Inisialisasi data default
    $pasien = $_SESSION['pasien_data'] ?? [
        'no_rawat' => '',
        'no_rkm_medis' => '',
        'nm_pasien' => '',
        'tgl_lahir' => '',
        'jk' => '',
        'alamat' => '',
        'agama' => '',
        'stts_nikah' => '',
        'tgl_masuk' => '',
        'jam_masuk' => ''
    ];
    $umur = '';

    // Jika no_rawat kosong tapi ada no_rkm_medis, ambil rawat terakhir
    if (empty($no_rawat) && !empty($no_rkm_medis)) {
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
        }
    }

    // Ambil data pasien jika ada no_rawat atau no_rkm_medis
    if (!empty($no_rawat)) {
        $sql = "SELECT rp.no_rawat, rp.no_rkm_medis, p.nm_pasien, p.tgl_lahir, p.jk, p.alamat, p.agama, p.stts_nikah,
                       rp.tgl_registrasi AS tgl_masuk, rp.jam_reg AS jam_masuk
                FROM reg_periksa rp
                JOIN pasien p ON p.no_rkm_medis = rp.no_rkm_medis
                WHERE rp.no_rawat = ? OR rp.no_rkm_medis = ?
                LIMIT 1";
        try {
            $st = $pdo->prepare($sql);
            $st->execute([$no_rawat, $no_rkm_medis]);
            $pasien = $st->fetch(PDO::FETCH_ASSOC) ?: $pasien;
            $_SESSION['pasien_data'] = $pasien;
        } catch (PDOException $e) {
            error_log("Error in main query: " . $e->getMessage());
        }
    } elseif (!empty($no_rkm_medis)) {
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
    if (!empty($pasien['tgl_lahir'])) {
        $birthDate = new DateTime($pasien['tgl_lahir']);
        $today = new DateTime(date('Y-m-d'));
        $diff = $today->diff($birthDate);
        $umur = $diff->y . ' thn ' . $diff->m . ' bln ' . $diff->d . ' hr';
    }

    return [
        'pasien' => $pasien,
        'umur' => $umur
    ];
}
