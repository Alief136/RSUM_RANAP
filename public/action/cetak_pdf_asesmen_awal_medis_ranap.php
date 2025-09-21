<?php
// actions/cetak_asesmen.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/db.php';
date_default_timezone_set('Asia/Jakarta');

// Dompdf
require_once __DIR__ . '/../../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$data = $_POST;

// 🔹 Ambil nama dokter & perawat berdasarkan kode yang dikirim
$kd_dokter   = $_POST['kd_dokter'] ?? '';
$nip_perawat = $_POST['nip_perawat'] ?? '';
$dpjp_kode   = $_POST['dokter_jaga'] ?? '';

$nm_dokter   = '';
$nm_perawat  = '';
$dpjp_nama   = '';

// Dokter
if ($kd_dokter) {
  $stmt = $pdo->prepare("SELECT nm_dokter FROM dokter WHERE kd_dokter = ?");
  $stmt->execute([$kd_dokter]);
  $nm_dokter = $stmt->fetchColumn();
}

// Perawat
if ($nip_perawat) {
  $stmt = $pdo->prepare("SELECT nama FROM petugas WHERE nip = ?");
  $stmt->execute([$nip_perawat]);
  $nm_perawat = $stmt->fetchColumn();
}

// DPJP
if ($dpjp_kode) {
  $stmt = $pdo->prepare("SELECT nm_dokter FROM dokter WHERE kd_dokter = ?");
  $stmt->execute([$dpjp_kode]);
  $dpjp_nama = $stmt->fetchColumn();
}

// Sanitasi
function esc($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Helper: tampilkan nilai jika ada, kalau kosong tampil garis
function val_or_line($v, $line = '_________________________')
{
  $v = trim((string)$v ?? '');
  return $v !== '' ? esc($v) : $line;
}

// Helper: cek apakah checkbox tercentang
function is_checked($value, $array, $default = '☐')
{
  if (is_array($array) && in_array($value, $array)) {
    return '☑';
  }
  return $default;
}

// Data pasien (session)
$pasien = $_SESSION['pasien_data'] ?? [];
$umur = '';
if (!empty($pasien['tgl_lahir'])) {
  $birthDate = new DateTime($pasien['tgl_lahir']);
  $today = new DateTime(date('Y-m-d'));
  $diff = $today->diff($birthDate);
  $umur = $diff->m > 0 ? ($diff->y . ' th / ' . $diff->m . ' bln') : ($diff->y . ' th');
}

$jk = isset($pasien['jk']) ? ($pasien['jk'] === 'L' ? 'L' : ($pasien['jk'] === 'P' ? 'P' : '')) : '';
// HTML
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>RM 32a - Asesmen Awal Medis UGD Rawat Inap (Dewasa)</title>
<style>
    @page { margin: 15px 18px 18px 18px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; line-height: 1.15; }
    .header-rs { text-align: center; }
    .header-rs .nama { font-weight: bold; font-size: 14px; }
    .header-rs .alamat { margin-top: 2px; }
    .rm-kode { text-align:right; font-weight:bold; margin-top:2px; }
    .judul-form { text-align:center; font-weight:bold; font-size:12px; margin-top:4px; }
    table { width: 100%; border-collapse: collapse; }
    .tb { border: 1px solid #000; }
    .tb td, .tb th { border: 1px solid #000; padding: 3px 4px; vertical-align: top; }
    .nob { border:none; }
    .label { font-weight: normal; white-space: nowrap; }
    .colon { width: 8px; text-align:center; }
    .center { text-align:center; }
    .bold { font-weight:bold; }
    .subhead { background:#f0f0f0; font-weight:bold; text-align:center; padding:2px 0; }
    .spacer-2 { height: 2px; }
    .spacer-4 { height: 4px; }
    .small { font-size: 9px; }
    .boxcol td { padding: 2px 4px; }
    .section-title { background:#d0d0d0; font-weight:bold; text-align:center; margin: 0; padding: 4px 0; }
    .ttd { margin-top: 16px; text-align: right; }
    .underline { text-decoration: underline; }
    .w-15 { width:15%; } .w-20 { width:20%; } .w-25 { width:25%; } .w-30 { width:30%; } .w-35 { width:35%; } .w-40 { width:40%; } .w-50 { width:50%; } .w-60 { width:60%; } .w-70 { width:70%; }
    .nowrap { white-space:nowrap; }
    .tiny { font-size:8.5px; }
    .survey-table td { vertical-align: top; padding: 4px; }
    .survey-box { margin-bottom: 2px; }
    .survey-subsection { margin-top: 4px; font-weight: bold; }
</style>
</head>
<body>

<div class="header-rs">
  <div class="nama">Rumah Sakit Unipdu Medika Jombang</div>
  <div class="alamat small">Jl. Raya Peterongan – Jogoroto Km. 0,5 (Tambar) Jombang | Telp. (0321) 873699 | Whatsapp: (+62) 857 4853 8844</div>
  <div class="alamat small">e-mail: rs.unipdu.medika@gmail.com</div>
</div>
<div class="rm-kode">RM 32a</div>
<div class="judul-form">ASESMEN AWAL MEDIS RAWAT INAP<br>UNIT GAWAT DARURAT (DEWASA)</div>

<table class="tb" style="margin-top:6px;">
  <!-- Data Pasien -->
  <tr>
    <td class="w-15"><span class="label">Nama</span></td>
    <td class="colon">:</td>
    <td class="w-35">' . val_or_line($pasien["nm_pasien"] ?? ($data["nm_pasien"] ?? "")) . ' &nbsp;&nbsp; Sex (L/P): ' . ($jk !== "" ? esc($jk) : "(__)") . '</td>
    <td class="w-15"><span class="label">No Rekam Medis</span></td>
    <td class="colon">:</td>
    <td class="w-35">' . val_or_line($pasien["no_rkm_medis"] ?? ($data["no_rkm_medis"] ?? "")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Tanggal Lahir</span></td>
    <td class="colon">:</td>
    <td>' . val_or_line($pasien["tgl_lahir"] ?? "") . '</td>
    <td><span class="label">Umur</span></td>
    <td class="colon">:</td>
    <td>' . ($umur !== "" ? esc($umur) : "____ bln/th") . '</td>
  </tr>
  <tr>
    <td><span class="label">Alamat</span></td>
    <td class="colon">:</td>
    <td>' . val_or_line($pasien["alamat"] ?? "") . '</td>
    <td><span class="label">Tgl Masuk</span></td>
    <td class="colon">:</td>
    <td>' . val_or_line($data["tgl_masuk"] ?? "") . '</td>
  </tr>
  <tr>
    <td><span class="label">Agama</span></td>
    <td class="colon">:</td>
    <td>' . val_or_line($pasien["agama"] ?? "") . '</td>
    <td><span class="label">Jam</span></td>
    <td class="colon">:</td>
    <td>' . val_or_line($data["jam_masuk"] ?? "", "__:__") . '</td>
  </tr>
  <tr>
    <td><span class="label">Status Perkawinan</span></td>
    <td class="colon">:</td>
    <td>' . val_or_line($pasien["stts_nikah"] ?? "") . '</td>
    <td><span class="label">Ruang</span></td>
    <td class="colon">:</td>
    <td class="w-35">' . val_or_line($data["ruang"] ?? "") . '</td>
  </tr>
  <tr>
    <td></td><td class="colon"></td><td></td>
    <td><span class="label">Kelas</span></td>
    <td class="colon">:</td>
    <td>' . val_or_line($data["kelas"] ?? "") . '</td>
  </tr>

  <!-- Spacer -->
  <tr class="spacer-4"><td colspan="6"></td></tr>

  <!-- Dikirim dan Diantar -->
  <tr>
    <td class="w-20"><span class="label">Dikirim oleh</span></td><td class="colon">:</td><td class="w-30">' . val_or_line($data["dikirim_oleh"] ?? "") . '</td>
    <td class="w-20"><span class="label">Diantar oleh</span></td><td class="colon">:</td><td class="w-30">' . val_or_line($data["diantar_oleh"] ?? "") . '</td>
  </tr>
  <tr>
    <td><span class="label">Kendaraan Pengantar</span></td><td class="colon">:</td>
    <td colspan="4">
      <table class="nob boxcol" style="width:100%;">
        <tr>
          <td>' . is_checked('Sendiri', $data['kendaraan_pengantar'] ?? []) . ' Sendiri</td>
          <td>' . is_checked('Keluarga', $data['kendaraan_pengantar'] ?? []) . ' Keluarga</td>
          <td>' . is_checked('Ambulance', $data['kendaraan_pengantar'] ?? []) . ' Ambulance</td>
          <td>' . is_checked('RS/PKM/BP', $data['kendaraan_pengantar'] ?? []) . ' RS/PKM/BP</td>
          <td>' . is_checked('Perusahaan', $data['kendaraan_pengantar'] ?? []) . ' Perusahaan</td>
          <td>' . is_checked('Lainnya', $data['kendaraan_pengantar'] ?? []) . ' Lainnya</td>
        </tr>
        <tr>
          <td>' . is_checked('Dokter/Bidan', $data['kendaraan_pengantar'] ?? []) . ' Dokter/Bidan</td>
          <td>' . is_checked('Polisi', $data['kendaraan_pengantar'] ?? []) . ' Polisi</td>
          <td>' . is_checked('Pribadi', $data['kendaraan_pengantar'] ?? []) . ' Pribadi</td>
          <td>' . is_checked('Umum', $data['kendaraan_pengantar'] ?? []) . ' Umum</td>
          <td colspan="2">' . is_checked('Lainnya', $data['kendaraan_pengantar'] ?? []) . ' Lainnya</td>
        </tr>
      </table>
    </td>
  </tr>
<tr>
  <td><span class="label">Dokter</span></td><td class="colon">:</td>
  <td>' . val_or_line(($nm_dokter !== '' ? $nm_dokter . " (" . $kd_dokter . ")" : $kd_dokter)) . '</td>
  <td><span class="label">Perawat</span></td><td class="colon">:</td>
  <td>' . val_or_line(($nm_perawat !== '' ? $nm_perawat . " (" . $nip_perawat . ")" : $nip_perawat), "________________") . '</td>
</tr>


  <!-- Spacer -->
  <tr class="spacer-4"><td colspan="6"></td></tr>

  <!-- Prioritas -->
  <tr>
    <td><span class="label">Prioritas</span></td><td class="colon">:</td>
    <td colspan="4">
      <table class="nob boxcol" style="width:100%;">
        <tr>
          <td>' . is_checked('Prioritas 0', [$data['prioritas'] ?? ''], '☐') . ' Prioritas 0</td>
          <td>' . is_checked('Prioritas 1', [$data['prioritas'] ?? ''], '☐') . ' Prioritas 1</td>
          <td>' . is_checked('Prioritas 2', [$data['prioritas'] ?? ''], '☐') . ' Prioritas 2</td>
          <td>' . is_checked('Prioritas 3', [$data['prioritas'] ?? ''], '☐') . ' Prioritas 3</td>
        </tr>
        <tr>
          <td>' . is_checked('Pasien sudah meninggal', [$data['prioritas'] ?? ''], '☐') . ' Pasien sudah meninggal</td>
          <td>' . is_checked('Tersedak', $data['prioritas'] ?? [], '☐') . ' Tersedak</td>
          <td>' . is_checked('Luka Bakar', $data['prioritas'] ?? [], '☐') . ' Luka Bakar</td>
          <td>' . is_checked('Dislokasi', $data['prioritas'] ?? [], '☐') . ' Dislokasi</td>
        </tr>
        <tr>
          <td>' . is_checked('Cidera Kepala Berat', $data['prioritas'] ?? [], '☐') . ' Cidera Kepala Berat</td>
          <td>' . is_checked('Cidera Kepala Sedang', $data['prioritas'] ?? [], '☐') . ' Cidera Kepala Sedang</td>
          <td>' . is_checked('Patah Tulang tertutup', $data['prioritas'] ?? [], '☐') . ' Patah Tulang tertutup</td>
          <td>' . is_checked('Kejang', $data['prioritas'] ?? [], '☐') . ' Kejang</td>
        </tr>
        <tr>
          <td>' . is_checked('Dehidrasi', $data['prioritas'] ?? [], '☐') . ' Dehidrasi</td>
          <td>' . is_checked('Nyeri minimal', $data['prioritas'] ?? [], '☐') . ' Nyeri minimal</td>
          <td>' . is_checked('Penurunan Kesadaran', $data['prioritas'] ?? [], '☐') . ' Penurunan Kesadaran</td>
          <td>' . is_checked('Muntah Terus menerus', $data['prioritas'] ?? [], '☐') . ' Muntah Terus menerus</td>
        </tr>
        <tr>
          <td>' . is_checked('Luka Minor/Lecet', $data['prioritas'] ?? [], '☐') . ' Luka Minor/Lecet</td>
          <td>' . is_checked('Kelainan Persalinan', $data['prioritas'] ?? [], '☐') . ' Kelainan Persalinan</td>
          <td>' . is_checked('Hipertensi', $data['prioritas'] ?? [], '☐') . ' Hipertensi</td>
          <td>' . is_checked('Muntah tanpa dehidrasi', $data['prioritas'] ?? [], '☐') . ' Muntah tanpa dehidrasi</td>
        </tr>
        <tr>
          <td>' . is_checked('Serangan Jantung', $data['prioritas'] ?? [], '☐') . ' Serangan Jantung</td>
          <td>' . is_checked('Lain-lain', $data['prioritas'] ?? [], '☐') . ' Lain-lain ......</td>
          <td>' . is_checked('Trauma sedang', $data['prioritas'] ?? [], '☐') . ' Trauma sedang</td>
          <td>' . is_checked('Lain-lain', $data['prioritas'] ?? [], '☐') . ' Lain-lain ......</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td><span class="label">Kebutuhan pasien</span></td><td class="colon">:</td>
    <td colspan="4">
      ' . is_checked('Preventif', $data['kebutuhan'] ?? [], '☐') . ' Preventif &nbsp;&nbsp;
      ' . is_checked('Kuratif', $data['kebutuhan'] ?? [], '☐') . ' Kuratif &nbsp;&nbsp;
      ' . is_checked('Rehabilitatif', $data['kebutuhan'] ?? [], '☐') . ' Rehabilitatif &nbsp;&nbsp;
      ' . is_checked('Paliatif', $data['kebutuhan'] ?? [], '☐') . ' Paliatif
    </td>
  </tr>

  <!-- Survey Primer -->
  <tr class="section-title"><td colspan="6">SURVEY PRIMER</td></tr>
  <tr>
    <td class="center bold">JALAN NAPAS</td>
    <td class="center bold" colspan="2">PERNAPASAN</td>
    <td class="center bold" colspan="3">SIRKULASI</td>
  </tr>
  <tr class="survey-table">
    <td>
      <div class="survey-box">' . is_checked('Paten', $data['jalan_napas'] ?? [], '☐') . ' Paten</div>
      <div class="survey-box">' . is_checked('Obstruksi partial', $data['jalan_napas'] ?? [], '☐') . ' Obstruksi partial</div>
      <div class="survey-box">' . is_checked('Stridor', $data['jalan_napas'] ?? [], '☐') . ' Stridor</div>
      <div class="survey-box">' . is_checked('Snoring', $data['jalan_napas'] ?? [], '☐') . ' Snoring</div>
      <div class="survey-box">' . is_checked('Gurgling', $data['jalan_napas'] ?? [], '☐') . ' Gurgling</div>
      <div class="survey-box">' . is_checked('Obstruksi total', $data['jalan_napas'] ?? [], '☐') . ' Obstruksi total</div>
      <div class="survey-box">' . is_checked('Trauma jalan napas', $data['jalan_napas'] ?? [], '☐') . ' Trauma jalan napas</div>
      <div class="survey-box">' . is_checked('Risiko aspirasi', $data['jalan_napas'] ?? [], '☐') . ' Risiko aspirasi</div>
      <div class="survey-box">' . is_checked('Perdarahan / muntahan', $data['jalan_napas'] ?? [], '☐') . ' Perdarahan / muntahan</div>
      <div class="survey-box">' . is_checked('Benda asing', $data['jalan_napas'] ?? [], '☐') . ' Benda asing</div>
      <div class="survey-subsection">Kesimpulan: ' . is_checked('Aman', [$data['kesimpulan_airway'] ?? ''], '☐') . ' Aman &nbsp; ' . is_checked('Mengancam nyawa', [$data['kesimpulan_airway'] ?? ''], '☐') . ' Mengancam nyawa</div>
    </td>
    <td colspan="2">
      <div class="survey-box">' . is_checked('Paten', $data['pernapasan'] ?? [], '☐') . ' Paten</div>
      <div class="survey-box">' . is_checked('Tidak Spontan', $data['pernapasan'] ?? [], '☐') . ' Tidak Spontan</div>
      <div class="survey-box">' . is_checked('Reguler', $data['pernapasan'] ?? [], '☐') . ' Reguler &nbsp; ' . is_checked('Irreguler', $data['pernapasan'] ?? [], '☐') . ' Irreguler</div>
      <div class="survey-box">Gerakan Dada: ' . is_checked('Simetris', $data['pernapasan'] ?? [], '☐') . ' Simetris &nbsp; ' . is_checked('Asimetris', $data['pernapasan'] ?? [], '☐') . ' Asimetris</div>
      <div class="survey-box">' . is_checked('Jejas Dinding Dada', $data['pernapasan'] ?? [], '☐') . ' Jejas Dinding Dada</div>
      <div class="survey-subsection">Tipe Pernapasan:</div>
      <div class="survey-box">
        ' . is_checked('Normal', $data['tipe_pernapasan'] ?? [], '☐') . ' Normal &nbsp; ' . is_checked('Takipneu', $data['tipe_pernapasan'] ?? [], '☐') . ' Takipneu &nbsp; ' . is_checked('Kussmaul', $data['tipe_pernapasan'] ?? [], '☐') . ' Kussmaul &nbsp; ' . is_checked('Hiperventilasi', $data['tipe_pernapasan'] ?? [], '☐') . ' Hiperventilasi<br>
        ' . is_checked('Biot', $data['tipe_pernapasan'] ?? [], '☐') . ' Biot &nbsp; ' . is_checked('Cheyne Stoke', $data['tipe_pernapasan'] ?? [], '☐') . ' Cheyne Stoke &nbsp; ' . is_checked('Apneustic', $data['tipe_pernapasan'] ?? [], '☐') . ' Apneustic
      </div>
      <div class="survey-subsection">Auskultasi:</div>
      <div class="survey-box">' . is_checked('Rhonki', $data['auskultasi_pernapasan'] ?? [], '☐') . ' Rhonki &nbsp; ' . is_checked('Wheezing', $data['auskultasi_pernapasan'] ?? [], '☐') . ' Wheezing</div>
      <div class="survey-subsection">Kesimpulan: ' . is_checked('Aman', [$data['kesimpulan_breathing'] ?? ''], '☐') . ' Aman &nbsp; ' . is_checked('Mengancam nyawa', [$data['kesimpulan_breathing'] ?? ''], '☐') . ' Mengancam nyawa</div>
    </td>
    <td colspan="3">
      <div class="survey-box">Nadi: ' . is_checked('Reguler', $data['sirkulasi'] ?? [], '☐') . ' Reguler &nbsp; ' . is_checked('Irreguler', $data['sirkulasi'] ?? [], '☐') . ' Irreguler &nbsp; ' . is_checked('Kuat', $data['sirkulasi'] ?? [], '☐') . ' Kuat &nbsp; ' . is_checked('Lemah', $data['sirkulasi'] ?? [], '☐') . ' Lemah</div>
      <div class="survey-box">Kulit/Mukosa: ' . is_checked('Normal', $data['kulit_mukosa'] ?? [], '☐') . ' Normal &nbsp; ' . is_checked('Pucat', $data['kulit_mukosa'] ?? [], '☐') . ' Pucat &nbsp; ' . is_checked('Jaundice', $data['kulit_mukosa'] ?? [], '☐') . ' Jaundice &nbsp; ' . is_checked('Sianosis', $data['kulit_mukosa'] ?? [], '☐') . ' Sianosis</div>
      <div class="survey-box">' . is_checked('Berkeringat', $data['kulit_mukosa'] ?? [], '☐') . ' Berkeringat</div>
      <div class="survey-box">Akral: ' . is_checked('Hangat', $data['akral'] ?? [], '☐') . ' Hangat &nbsp; ' . is_checked('Dingin', $data['akral'] ?? [], '☐') . ' Dingin &nbsp; ' . is_checked('Kering', $data['akral'] ?? [], '☐') . ' Kering &nbsp; ' . is_checked('Basah', $data['akral'] ?? [], '☐') . ' Basah</div>
      <div class="survey-box">CRT: ' . is_checked('< 2 Detik', [$data['crt'] ?? ''], '☐') . ' < 2 Detik &nbsp; ' . is_checked('> 2 Detik', [$data['crt'] ?? ''], '☐') . ' > 2 Detik</div>
      <div class="survey-subsection">Kesimpulan: ' . is_checked('Aman', [$data['kesimpulan_circulation'] ?? ''], '☐') . ' Aman &nbsp; ' . is_checked('Mengancam nyawa', [$data['kesimpulan_circulation'] ?? ''], '☐') . ' Mengancam nyawa</div>
    </td>
  </tr>

  <!-- Tanda Vital -->
  <tr class="section-title"><td colspan="6">TANDA VITAL</td></tr>
  <tr>
    <td class="w-15"><span class="label">GCS</span></td><td class="colon">:</td><td class="w-35">' . val_or_line($data["gcs"] ?? "") . '</td>
    <td class="w-15"><span class="label">TD</span></td><td class="colon">:</td><td class="w-35">' . val_or_line($data["td"] ?? "") . ' mmHg</td>
  </tr>
  <tr>
    <td><span class="label">Nadi</span></td><td class="colon">:</td><td>' . val_or_line($data["nadi"] ?? "") . ' x/menit</td>
    <td><span class="label">RR</span></td><td class="colon">:</td><td>' . val_or_line($data["rr"] ?? "") . ' x/menit</td>
  </tr>
  <tr>
    <td><span class="label">Suhu</span></td><td class="colon">:</td><td>' . val_or_line($data["suhu"] ?? "") . ' °C</td>
    <td><span class="label">SpO2</span></td><td class="colon">:</td><td>' . val_or_line($data["spo2"] ?? "") . ' %</td>
  </tr>
  <tr>
    <td><span class="label">BB</span></td><td class="colon">:</td><td>' . val_or_line($data["bb"] ?? "") . ' kg</td>
    <td class="nob" colspan="3"></td>
  </tr>

  <!-- Subjektif -->
  <tr class="section-title"><td colspan="6">SUBJEKTIF</td></tr>
  <tr>
    <td class="w-20"><span class="label">Keluhan Utama</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["keluhan_utama"] ?? "", "…………………………………………………………………………………………………………………………….......................")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Riwayat Penyakit Sekarang</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["riwayat_penyakit_sekarang"] ?? "", "……………………………………………………………………………………………………………………………………………………<br>……………………………………………………………………………………………………………………………………………………<br>……………………………………………………………………………………………………………………………………………………")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Riwayat Penyakit Dahulu</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["riwayat_penyakit_dahulu"] ?? "", "…………………………………………………………………………………………………………………………….......................")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Riwayat Penyakit Keluarga</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["riwayat_penyakit_keluarga"] ?? "", "…………………………………………………………………………………………………………………………….......................")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Obat-obatan</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["obat_obatan"] ?? "", "…………………………………………………………………………………………………………………………….......................")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Alergi</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["alergi"] ?? "", "…………………………………………………………………………………………………………………………….......................")) . '</td>
  </tr>

  <!-- Survey Sekunder -->
  <tr class="section-title"><td colspan="6">SURVEY SEKUNDER<br>OBJECTIVE</td></tr>
  <tr><td class="w-25"><span class="label">Keadaan Umum</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["keadaan_umum"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Kepala</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["kepala_wajah"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Konjungtiva</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["konjungtiva"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Sclera</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["sklera"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Bibir/lidah</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["bibir_lidah"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Mukosa</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["mukosa"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Leher</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["leher"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Deviasi trakea</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["deviasi_trakea"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">JVP</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["jvp"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">LNN</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["lnn"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Tiroid</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["tiroid"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Thorax</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["thorax"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Jantung</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["jantung"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Paru</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["paru"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Abdomen & pelvis</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["abdomen_pelvis"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Punggung & pinggang</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["punggung_pinggang"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Genitalia</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["genitalia_ekstremitas"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Ekstremitas</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["ekstremitas"] ?? "", "……………………………………………………………")) . '</td></tr>
  <tr><td><span class="label">Pemeriksaan lain</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["pemeriksaan_lain"] ?? "", "……………………………………………………………")) . '</td></tr>

  <!-- Pemeriksaan Penunjang -->
  <tr class="section-title"><td colspan="6">PEMERIKSAAN PENUNJANG</td></tr>
  <tr>
    <td class="w-20"><span class="label">Laboratorium</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["laboratorium"] ?? "", " ")) . '</td>
  </tr>
  <tr>
    <td><span class="label">X-ray</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["x_ray"] ?? "", " ")) . '</td>
  </tr>
  <tr>
    <td><span class="label">CT Scan</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["ct_scan"] ?? "", " ")) . '</td>
  </tr>
  <tr>
    <td><span class="label">USG</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["usg"] ?? "", " ")) . '</td>
  </tr>
  <tr>
    <td><span class="label">ECG</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["ecg"] ?? "", " ")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Lain-lain</span></td><td class="colon">:</td><td colspan="4">' . nl2br(val_or_line($data["lain_lain_penunjang"] ?? "", " ")) . '</td>
  </tr>

  <!-- Assesment -->
  <tr class="section-title"><td colspan="6">ASSESMENT</td></tr>
  <tr>
    <td class="w-25"><span class="label">Diagnosis Utama</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["diagnosis_utama"] ?? "", "……………………………………………………………………………………………………………………………………...")) . '</td>
  </tr>
  <tr>
    <td><span class="label">Diagnosis Sekunder</span></td><td class="colon">:</td>
    <td colspan="4">' . nl2br(val_or_line($data["diagnosis_sekunder"] ?? "", "……………………………………………………………………………………………………………………………………...
    ……………………………………………………………………………………………………………………………………...
    ……………………………………………………………………………………………………………………………………...")) . '</td>
  </tr>

  <!-- Planning -->
  <tr class="section-title"><td colspan="6">PLANNING<br><span class="tiny">(TINDAKAN DAN TERAPI)</span></td></tr>
  <tr>
    <td colspan="6">' . nl2br(val_or_line($data["tindakan_terapi"] ?? "", "






")) . '</td>
  </tr>

  <!-- Tindak Lanjut -->
<tr class="section-title"><td colspan="6">TINDAK LANJUT</td></tr>
<tr>
  <td class="w-20"><span class="label">Tindak lanjut</span></td><td class="colon">:</td>
  <td colspan="4">
    ' . is_checked('Pulang', [$data['keputusan_akhir'] ?? ''], '☐') . ' Pulang &nbsp;&nbsp;&nbsp; 
    ' . is_checked('Menolak tindakan', [$data['keputusan_akhir'] ?? ''], '☐') . ' Menolak tindakan &nbsp;&nbsp;&nbsp; 
    ' . is_checked('MRS', [$data['keputusan_akhir'] ?? ''], '☐') . ' MRS &nbsp;&nbsp;&nbsp; 
    ' . is_checked('Meninggal', [$data['keputusan_akhir'] ?? ''], '☐') . ' Meninggal &nbsp;&nbsp;&nbsp; 
    ' . is_checked('DOA', [$data['keputusan_akhir'] ?? ''], '☐') . ' DOA
  </td>
</tr>
<tr>
  <td><span class="label">MRS di ruang</span></td><td class="colon">:</td>
  <td colspan="4">' . (isset($data['keputusan_akhir']) && $data['keputusan_akhir'] === 'MRS' ? val_or_line($data["nama_ruang"] ?? "", "…………………") : '…………………') . '</td>
</tr>
<tr>
  <td><span class="label">Dirujuk ke RS</span></td><td class="colon">:</td>
  <td colspan="4">' . (isset($data['keputusan_akhir']) && $data['keputusan_akhir'] === 'Dirujuk ke RS' ? val_or_line($data["nama_rs"] ?? "", "…………………") : '…………………') . '</td>
</tr>
<tr>
  <td class="nowrap"><span class="label">Dokter yang merawat/DPJP</span></td>
  <td class="colon">:</td>
  <td colspan="4">' . val_or_line(($dpjp_nama !== '' ? $dpjp_nama . " (" . $dpjp_kode . ")" : $dpjp_kode), "……………………………………………………") . '</td>
</tr>

</table>

<div class="ttd">
  <div class="bold">Nama Terang dan Tanda Tangan</div>
  <br><br><br>
  <div>( ' . val_or_line(($dpjp_nama !== '' ? $dpjp_nama : $dpjp_kode), "_________________________") . ' )</div>
  <div class="underline">DOKTER JAGA</div>
</div>

</body>
</html>
';

// Dompdf options
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('fontDir', __DIR__ . '/../vendor/dompdf/dompdf/lib/fonts');
$options->set('fontCache', __DIR__ . '/../vendor/dompdf/dompdf/lib/fonts');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("RM32a_ASESMEN_AWAL_MEDIS_UGD_DEWASA_" . date("Ymd_His") . ".pdf", ["Attachment" => false]);
exit(0);
