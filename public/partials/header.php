<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$title = $title ?? 'E-Ranap';
$rm = $rm ?? ($_GET['no_rkm_medis'] ?? '');
$nr = $nr ?? ($_GET['no_rawat'] ?? '');
$cur = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// List of patient-related pages where navigation links should appear
$patientPages = ['detail.php', 'asesmen.php', 'permintaan_lab.php', 'resep_obat.php', 'asesmen_awal.php', 'persetujuan.php', 'penolakan.php', 'resume_medis.php'];

function active($file, $cur)
{
  return $file === $cur ? 'active' : '';
}

function patient_href($file, $rm, $nr = '')
{
  $query = $rm ? "no_rkm_medis=" . urlencode($rm) : '';
  if ($nr && in_array($file, ['asesmen_awal.php'])) {
    $query .= $query ? '&' : '';
    $query .= "no_rawat=" . urlencode($nr);
  }
  return $query ? "$file?$query" : $file;
}

$namaLogin = $_SESSION['nama_login'] ?? '';
$fotoLogin = $_SESSION['foto_login'] ?? '';
if (!$fotoLogin) $fotoLogin = 'img/default.png';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <?php if (!empty($extra_css)) foreach ((array)$extra_css as $href): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($href) ?>">
  <?php endforeach; ?>

</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-light glass">
    <div class="container-fluid">
      <a class="navbar-brand" href="list_pasien.php">RS UNIPDU MEDIKA</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topnav" aria-controls="topnav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>


      <div class="collapse navbar-collapse" id="topnav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link <?= active('list_pasien.php', $cur) ?>" href="list_pasien.php">Daftar Pasien</a>
          </li>
          <?php if ($rm && $cur !== 'list_pasien.php' && in_array($cur, $patientPages)): ?>
            <li class="nav-item">
              <a class="nav-link <?= active('detail.php', $cur) ?>" href="<?= patient_href('detail.php', $rm, $nr) ?>">Detail Pasien</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= active('asesmen.php', $cur) ?>" href="<?= patient_href('asesmen.php', $rm, $nr) ?>">Asesmen</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= active('permintaan_lab.php', $cur) ?>" href="<?= patient_href('permintaan_lab.php', $rm, $nr) ?>">Permintaan Lab</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= active('resep_obat.php', $cur) ?>" href="<?= patient_href('resep_obat.php', $rm, $nr) ?>">Resep Obat</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= active('asesmen_awal.php', $cur) ?>" href="<?= patient_href('asesmen_awal.php', $rm, $nr) ?>">Isi Asesmen Awal</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= active('persetujuan.php', $cur) ?>" href="<?= patient_href('persetujuan.php', $rm, $nr) ?>">Persetujuan</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= active('penolakan.php', $cur) ?>" href="<?= patient_href('penolakan.php', $rm, $nr) ?>">Penolakan</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= active('resume_medis.php', $cur) ?>" href="<?= patient_href('resume_medis.php', $rm, $nr) ?>">Resume Medis</a>
            </li>
          <?php endif; ?>
        </ul>

        <div class="d-flex ms-auto align-items-center">
          <span class="navbar-text me-3">
            <img src="../img/default.png" alt="foto" class="avatar" onerror="this.onerror=null;this.src='../img/default.png'"><?= htmlspecialchars($namaLogin) ?>
          </span>
          <a class="btn btn-danger btn-sm btn-logout" href="../logout.php" title="Logout" aria-label="Logout">
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true">
              <path d="M15 17l5-5-5-5M20 12H9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M4 4h7a2 2 0 0 1 2 2v3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              <path d="M13 15v3a2 2 0 0 1-2 2H4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </nav>
  <div class="container my-4">
    <script>
      (function() {
        const nav = document.querySelector('.navbar.glass');
        if (!nav) return;
        const onScroll = () => {
          if (window.scrollY > 6) nav.classList.add('nav-compact');
          else nav.classList.remove('nav-compact');
        };
        onScroll();
        window.addEventListener('scroll', onScroll, {
          passive: true
        });
      })();
    </script>