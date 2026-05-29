<?php
session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT * FROM surat WHERE id = $id");

if ($result->num_rows === 0) {
    flash("❌ Surat tidak ditemukan!", "danger");
    header("Location: index.php");
    exit;
}

$surat = $result->fetch_assoc();

$kop_default = [
    'pemda' => 'PEMERINTAH KABUPATEN/KOTA',
    'dinas' => 'DINAS PEMBERDAYAAN PEREMPUAN DAN PERLINDUNGAN ANAK',
    'alamat' => 'Jl. Contoh Alamat No. 123, Kota/Kabupaten',
    'telp' => '(021) 1234567',
    'email' => 'dp3a@pemerintah.go.id'
];

if(file_exists('config_kop.json')) {
    $kop_data = json_decode(file_get_contents('config_kop.json'), true);
    $kop = array_merge($kop_default, $kop_data);
} else {
    $kop = $kop_default;
}

$bulan_id = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl_kirim = date('d', strtotime($surat['tanggal_kirim'])) . ' ' . $bulan_id[date('m', strtotime($surat['tanggal_kirim'])) - 1] . ' ' . date('Y', strtotime($surat['tanggal_kirim']));
$tgl_terima = date('d', strtotime($surat['tanggal_terima'])) . ' ' . $bulan_id[date('m', strtotime($surat['tanggal_terima'])) - 1] . ' ' . date('Y', strtotime($surat['tanggal_terima']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Surat - <?= htmlspecialchars($surat['no_surat']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius: 12px;
            --radius-lg: 16px;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', 'Segoe UI', 'Times New Roman', serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* SIDEBAR (untuk konsistensi - opsional jika ingin ditambah sidebar) */
        .sidebar {
            position: fixed; top: 0; left: 0; width: 280px; height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white; z-index: 1000; box-shadow: var(--shadow-lg);
            display: flex; flex-direction: column;
        }
        .sidebar-header {
            padding: 32px 24px 24px; text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.15);
        }
        .sidebar-logo {
            width: 72px; height: 72px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            padding: 8px; flex-shrink: 0; overflow: hidden;
            border: 2px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo img {
            width: 100%; height: 100%; object-fit: contain; border-radius: 50%;
        }
        .sidebar-header h2 { font-size: 18px; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 4px; }
        .sidebar-header p { font-size: 12px; opacity: 0.85; line-height: 1.4; }
        .sidebar-nav { flex: 1; overflow-y: auto; padding: 20px 0; }
        .nav-label { padding: 16px 24px 8px; font-size: 10px; text-transform: uppercase; color: rgba(255,255,255,0.4); font-weight: 700; letter-spacing: 1.5px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.25s ease; border-left: 3px solid transparent; margin: 2px 0; }
        .nav-item:hover { background: rgba(255,255,255,0.08); color: white; border-left-color: var(--accent); padding-left: 28px; }
        .nav-item.active { background: linear-gradient(90deg, rgba(245,158,11,0.15) 0%, transparent 100%); color: white; border-left-color: var(--accent); font-weight: 600; }
        .nav-item .icon { width: 20px; text-align: center; font-size: 15px; }

        /* MAIN CONTENT */
        .main-content { margin-left: 0; padding: 32px; min-height: 100vh; }
        @media (min-width: 900px) { .main-content { margin-left: 280px; } }

        /* PAGE HEADER */
        .page-header {
            background: var(--surface); padding: 20px 28px; border-radius: var(--radius-lg);
            margin-bottom: 24px; box-shadow: var(--shadow-md); border: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
        }
        .page-title { display: flex; align-items: center; gap: 12px; }
        .page-title i {
            font-size: 28px; color: var(--secondary);
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }
        .page-title h1 { font-size: 22px; font-weight: 700; color: var(--primary); margin-bottom: 2px; }
        .page-title p { color: var(--text-muted); font-size: 13px; }
        
        /* BUTTONS */
        .btn {
            padding: 10px 18px; border: none; border-radius: var(--radius);
            font-size: 13px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s ease; color: white;
        }
        .btn-primary { background: var(--primary); }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-success { background: var(--success); }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-warning { background: var(--accent); color: white; }
        .btn-warning:hover { background: #d97706; transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-info { background: var(--secondary); }
        .btn-info:hover { background: #2563eb; transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .btn-outline { background: white; border: 1.5px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: var(--surface); border-color: var(--primary); color: var(--primary); }

        /* DOKUMEN RESMI */
        .document-wrapper {
            background: var(--surface);
            max-width: 210mm;
            margin: 0 auto;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border-radius: var(--radius-lg);
            min-height: 297mm;
            border: 1px solid var(--border);
        }
        
        .kop-surat {
            text-align: center;
            border-bottom: 3px double var(--primary);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .kop-surat h2 {
            font-size: 16pt;
            text-transform: uppercase;
            margin: 0;
            letter-spacing: 1px;
            color: var(--primary);
            font-weight: 700;
        }
        .kop-surat h1 {
            font-size: 18pt;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
            color: var(--primary);
        }
        .kop-surat p {
            font-size: 11pt;
            margin: 4px 0;
            color: var(--text-muted);
        }
        
        .judul-dokumen {
            text-align: center;
            margin: 35px 0 30px;
            padding: 18px 24px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border: 2px solid var(--primary);
            border-radius: var(--radius);
        }
        .judul-dokumen h3 {
            font-size: 14pt;
            text-transform: uppercase;
            text-decoration: underline;
            color: var(--primary);
            margin: 0;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .info-header {
            background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
            padding: 18px 24px;
            border-left: 4px solid var(--primary);
            border-radius: 0 var(--radius) var(--radius) 0;
            margin-bottom: 28px;
        }
        .info-header h2 {
            color: var(--primary);
            margin: 0 0 8px 0;
            font-size: 16pt;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .info-header .meta {
            font-size: 11pt;
            color: var(--text-muted);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .info-header .meta i { color: var(--secondary); }
        
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 10pt;
            font-weight: 600;
        }
        .badge-masuk { background: rgba(16,185,129,0.15); color: #059669; }
        .badge-keluar { background: rgba(245,158,11,0.15); color: #b45309; }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            font-size: 11pt;
        }
        .detail-table td {
            padding: 12px 16px;
            border: 1px solid var(--border);
            vertical-align: top;
        }
        .detail-table td.label {
            width: 200px;
            font-weight: 600;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .detail-table td.label i { color: var(--secondary); width: 16px; }
        .detail-table td.value {
            background: white;
            color: var(--text);
        }
        .detail-table td.value a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.2s;
        }
        .detail-table td.value a:hover { color: var(--primary); text-decoration: underline; }
        .detail-table td.value a i { color: var(--danger); }
        
        .footer {
            margin-top: 50px;
            padding-top: 25px;
            border-top: 2px solid var(--border);
            text-align: right;
            font-size: 11pt;
            color: var(--text-muted);
        }
        .footer p { margin: 5px 0; }
        
        /* PRINT STYLES */
        @media print {
            .button-bar, .page-header { display: none !important; }
            body { background: white; }
            .main-content { margin: 0 !important; padding: 0 !important; }
            .document-wrapper {
                box-shadow: none;
                margin: 0;
                padding: 30px;
                border: none;
                border-radius: 0;
                max-width: 100%;
            }
        }
        
        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0 !important; padding: 20px; }
            .document-wrapper { padding: 25px; }
            .detail-table td.label { width: 160px; }
            .page-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <!-- Sidebar (opsional - uncomment jika ingin sidebar di halaman view) -->
    <!--
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <img src="assets/img/logo-dp3a.png" alt="Logo DP3A">
            </div>
            <h2>DP3A</h2>
            <p>Dinas Pemberdayaan Perempuan<br>& Perlindungan Anak</p>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">MENU UTAMA</div>
            <a href="index.php" class="nav-item"><span class="icon"><i class="fas fa-home"></i></span> Dashboard</a>
            <a href="tambah.php" class="nav-item"><span class="icon"><i class="fas fa-plus-circle"></i></span> Tambah Surat</a>
            <div class="nav-label">BIDANG</div>
            <a href="index.php?bidang=Perlindungan%20Khusus%20Anak" class="nav-item"><span class="icon"><i class="fas fa-shield-alt"></i></span> Perlindungan Khusus Anak</a>
            <a href="index.php?bidang=Perlindungan%20Perempuan" class="nav-item"><span class="icon"><i class="fas fa-female"></i></span> Perlindungan Perempuan</a>
            <a href="index.php?bidang=Pemenuhan%20Hak%20Anak" class="nav-item"><span class="icon"><i class="fas fa-child"></i></span> Pemenuhan Hak Anak</a>
            <a href="index.php?bidang=Kualitas%20Hidup%20Perempuan" class="nav-item"><span class="icon"><i class="fas fa-venus"></i></span> Kualitas Hidup Perempuan</a>
            <a href="index.php?bidang=Sekretariat" class="nav-item"><span class="icon"><i class="fas fa-building"></i></span> Sekretariat</a>
            <div class="nav-label">LAPORAN</div>
            <a href="statistik.php" class="nav-item"><span class="icon"><i class="fas fa-chart-bar"></i></span> Statistik</a>
            <a href="arsip.php" class="nav-item"><span class="icon"><i class="fas fa-archive"></i></span> Arsip Surat</a>
        </nav>
    </aside>
    -->

    <div class="main-content">
        <!-- Tombol Aksi -->
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-file-alt"></i>
                <div>
                    <h1>Detail Surat</h1>
                    <p><?= htmlspecialchars($surat['no_surat']) ?></p>
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="index.php" class="btn btn-warning"><i class="fas fa-arrow-left"></i> Kembali</a>
                <a href="edit.php?id=<?= $surat['id'] ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
                <?php if($surat['file_surat']): ?>
                <a href="<?= $surat['file_surat'] ?>" download class="btn btn-success"><i class="fas fa-download"></i> Download</a>
                <?php endif; ?>
                <a href="pdf.php?id=<?= $surat['id'] ?>" class="btn btn-info"><i class="fas fa-file-pdf"></i> Cetak PDF</a>
            </div>
        </div>

        <!-- Dokumen Resmi -->
        <div class="document-wrapper">
            <div class="kop-surat">
                <h2><?= htmlspecialchars($kop['pemda']) ?></h2>
                <h1><?= htmlspecialchars($kop['dinas']) ?></h1>
                <p><?= htmlspecialchars($kop['alamat']) ?></p>
                <p>Telp: <?= htmlspecialchars($kop['telp']) ?> | Email: <?= htmlspecialchars($kop['email']) ?></p>
            </div>

            <div class="judul-dokumen">
                <h3>LEMBAR DATA SURAT</h3>
            </div>

            <div class="info-header">
                <h2>
                    <?= htmlspecialchars($surat['no_surat']) ?>
                    <span class="badge badge-<?= $surat['jenis_surat'] ?>">
                        <i class="fas fa-<?= $surat['jenis_surat']=='masuk'?'arrow-down':'arrow-up' ?>"></i>
                        <?= ucfirst($surat['jenis_surat']) ?>
                    </span>
                </h2>
                <div class="meta">
                    <span><i class="fas fa-folder"></i> <?= htmlspecialchars($surat['bidang']) ?></span>
                    <span><i class="fas fa-building"></i> <?= htmlspecialchars($surat['pengirim']) ?></span>
                </div>
            </div>

            <table class="detail-table">
                <tr><td class="label"><i class="fas fa-hashtag"></i> Nomor Surat</td><td class="value"><strong><?= htmlspecialchars($surat['no_surat']) ?></strong></td></tr>
                <tr><td class="label"><i class="fas fa-tag"></i> Jenis Surat</td><td class="value"><?= ucfirst($surat['jenis_surat']) ?></td></tr>
                <tr><td class="label"><i class="fas fa-calendar"></i> Tanggal Kirim</td><td class="value"><?= $tgl_kirim ?></td></tr>
                <tr><td class="label"><i class="fas fa-calendar-check"></i> Tanggal Terima</td><td class="value"><?= $tgl_terima ?></td></tr>
                <tr><td class="label"><i class="fas fa-user"></i> Pengirim / Tujuan</td><td class="value"><?= htmlspecialchars($surat['pengirim']) ?></td></tr>
                <tr><td class="label"><i class="fas fa-folder-open"></i> Bidang</td><td class="value"><?= htmlspecialchars($surat['bidang']) ?></td></tr>
                <tr><td class="label"><i class="fas fa-file-alt"></i> Perihal</td><td class="value"><strong><?= htmlspecialchars($surat['perihal']) ?></strong></td></tr>
                <tr><td class="label"><i class="fas fa-share"></i> Disposisi</td><td class="value"><?= htmlspecialchars($surat['disposisi']) ?: '-' ?></td></tr>
                <tr><td class="label"><i class="fas fa-tasks"></i> Status</td><td class="value"><strong><?= strtoupper($surat['status']) ?></strong></td></tr>
                <tr>
                    <td class="label"><i class="fas fa-paperclip"></i> File Terlampir</td>
                    <td class="value">
                        <?php if($surat['file_surat']): ?>
                            <a href="<?= $surat['file_surat'] ?>" target="_blank">
                                <i class="fas fa-file-pdf"></i> <?= basename($surat['file_surat']) ?>
                            </a>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">Tidak ada file</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr><td class="label"><i class="fas fa-comment"></i> Keterangan</td><td class="value"><?= htmlspecialchars($surat['keterangan']) ?: '-' ?></td></tr>
                <tr><td class="label"><i class="fas fa-clock"></i> Terakhir Diperbarui</td><td class="value"><?= date('d F Y H:i', strtotime($surat['updated_at'])) ?></td></tr>
            </table>

            <div class="footer">
                <p><strong>Dicetak pada:</strong> <?= date('d F Y') ?></p>
                <p style="margin-top: 70px; font-size: 10pt; color: var(--text-muted); font-style: italic;">
                    <i class="fas fa-info-circle"></i> Dokumen ini dibuat secara elektronik dan sah tanpa tanda tangan basah
                </p>
            </div>
        </div>
    </div>
</body>
</html>