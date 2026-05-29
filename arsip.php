<?php
session_start();
require_once 'config.php';

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter_jenis = isset($_GET['jenis']) ? sanitize($_GET['jenis']) : '';
$filter_bidang = isset($_GET['bidang']) ? sanitize($_GET['bidang']) : '';
$filter_tahun = isset($_GET['tahun']) ? sanitize($_GET['tahun']) : '';

// Build query
$where = [];
if ($search) $where[] = "(no_surat LIKE '%$search%' OR perihal LIKE '%$search%' OR pengirim LIKE '%$search%')";
if ($filter_jenis) $where[] = "jenis_surat = '$filter_jenis'";
if ($filter_bidang) $where[] = "bidang = '$filter_bidang'";
if ($filter_tahun) $where[] = "YEAR(tanggal_kirim) = '$filter_tahun'";

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Get all letters
$query = "SELECT * FROM surat $whereClause ORDER BY tanggal_kirim DESC";
$result = $conn->query($query);

// Get statistics
$total_arsip = $result->num_rows;
$total_masuk = $conn->query("SELECT COUNT(*) as c FROM surat WHERE jenis_surat='masuk' $whereClause")->fetch_assoc()['c'];
$total_keluar = $conn->query("SELECT COUNT(*) as c FROM surat WHERE jenis_surat='keluar' $whereClause")->fetch_assoc()['c'];

// Get unique years for filter
$years_query = "SELECT DISTINCT YEAR(tanggal_kirim) as tahun FROM surat ORDER BY tahun DESC";
$years_result = $conn->query($years_query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arsip Digital Surat - DP3A</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --bg: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
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
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.5;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 280px; height: 100vh;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            display: flex; flex-direction: column;
        }
        .sidebar-header {
            padding: 32px 24px 24px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.15);
        }
        
        /* LOGO BULAT SEMPURNA */
        .sidebar-logo {
            width: 72px; 
            height: 72px;
            background: white;
            border-radius: 50%;
            display: flex; 
            align-items: center; 
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            padding: 8px;
            flex-shrink: 0;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.1);
        }
        .sidebar-logo img {
            width: 100%; 
            height: 100%; 
            object-fit: contain;
            border-radius: 50%;
        }
        
        .sidebar-header h2 { font-size: 18px; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 4px; }
        .sidebar-header p { font-size: 12px; opacity: 0.85; line-height: 1.4; }
        
        .sidebar-nav { flex: 1; overflow-y: auto; padding: 20px 0; }
        .nav-label {
            padding: 16px 24px 8px;
            font-size: 10px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            font-weight: 700;
            letter-spacing: 1.5px;
        }
        .nav-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 24px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.25s ease;
            border-left: 3px solid transparent;
            margin: 2px 0;
        }
        .nav-item:hover {
            background: rgba(255,255,255,0.08);
            color: white;
            border-left-color: var(--accent);
            padding-left: 28px;
        }
        .nav-item.active {
            background: linear-gradient(90deg, rgba(245,158,11,0.15) 0%, transparent 100%);
            color: white;
            border-left-color: var(--accent);
            font-weight: 600;
        }
        .nav-item .icon { width: 20px; text-align: center; font-size: 15px; }

        /* MAIN CONTENT */
        .main-content {
            margin-left: 280px;
            padding: 32px;
            min-height: 100vh;
        }

        /* PAGE HEADER */
        .page-header {
            background: var(--surface);
            padding: 28px 32px;
            border-radius: var(--radius-lg);
            margin-bottom: 28px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            animation: slideDown 0.4s ease;
        }
        .page-header h1 {
            font-size: 26px; font-weight: 700; color: var(--primary);
            display: flex; align-items: center; gap: 12px;
            margin-bottom: 6px;
        }
        .page-header p { color: var(--text-muted); font-size: 14px; }
        
        .back-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 18px; background: var(--surface);
            color: var(--primary); text-decoration: none;
            border-radius: var(--radius); font-size: 13px;
            font-weight: 600; margin-bottom: 20px;
            border: 1.5px solid var(--border);
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: var(--primary); color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* STATS CARDS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .stat-badge {
            background: var(--surface);
            padding: 18px 22px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px;
            transition: all 0.3s ease;
        }
        .stat-badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .stat-badge .icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .stat-badge.primary .icon { background: rgba(59,130,246,0.1); color: var(--secondary); }
        .stat-badge.success .icon { background: rgba(16,185,129,0.1); color: var(--success); }
        .stat-badge.warning .icon { background: rgba(245,158,11,0.1); color: var(--accent); }
        
        .stat-badge .info h3 {
            font-size: 12px; color: var(--text-muted);
            font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 4px;
        }
        .stat-badge .info .value {
            font-size: 24px; font-weight: 700; color: var(--text);
        }

        /* FILTER BAR */
        .filter-container {
            background: var(--surface);
            padding: 20px 28px;
            border-radius: var(--radius-lg);
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }
        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 12px;
            align-items: end;
        }
        .filter-group {
            display: flex; flex-direction: column; gap: 6px;
        }
        .filter-group label {
            font-size: 12px; font-weight: 600; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .filter-group input, .filter-group select {
            padding: 10px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            font-size: 13px;
            transition: all 0.2s;
        }
        .filter-group input:focus, .filter-group select:focus {
            outline: none; border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn {
            padding: 10px 18px; border: none; border-radius: var(--radius);
            font-size: 13px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s ease; height: fit-content;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%);
            color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59,130,246,0.4);
        }
        .btn-outline {
            background: white; border: 1.5px solid var(--border); color: var(--text);
        }
        .btn-outline:hover {
            background: #f8fafc; border-color: var(--primary); color: var(--primary);
        }

        /* ARCHIVE LIST */
        .archive-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .archive-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 18px 28px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .archive-header h2 {
            font-size: 16px; font-weight: 700;
            display: flex; align-items: center; gap: 10px;
        }
        .archive-count {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        
        .archive-list {
            padding: 0;
        }
        .archive-item {
            padding: 22px 28px;
            border-bottom: 1px solid var(--border);
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 20px;
            transition: all 0.3s ease;
            animation: fadeIn 0.4s ease;
        }
        .archive-item:hover {
            background: linear-gradient(135deg, #fafafa 0%, #f8fafc 100%);
            transform: translateX(4px);
        }
        .archive-item:last-child { border-bottom: none; }
        
        .archive-main h3 {
            font-size: 15px; font-weight: 700; color: var(--primary);
            margin-bottom: 6px;
        }
        .archive-main p {
            font-size: 13px; color: var(--text-muted);
            margin-bottom: 8px;
        }
        .archive-meta {
            display: flex; gap: 16px; flex-wrap: wrap;
            font-size: 12px; color: var(--text-muted);
        }
        .archive-meta span {
            display: flex; align-items: center; gap: 6px;
        }
        .archive-meta i { color: var(--secondary); }
        
        .archive-actions {
            display: flex; flex-direction: column; gap: 10px; align-items: flex-end;
        }
        .badge {
            padding: 5px 12px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .badge-masuk { background: rgba(16,185,129,0.1); color: #059669; }
        .badge-keluar { background: rgba(245,158,11,0.1); color: #b45309; }
        
        .btn-pdf {
            padding: 8px 16px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white; text-decoration: none;
            border-radius: var(--radius);
            font-size: 12px; font-weight: 600;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(220,38,38,0.3);
        }
        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220,38,38,0.4);
        }
        
        .empty-state {
            padding: 60px 28px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 64px; opacity: 0.3; margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 16px; color: var(--text); margin-bottom: 6px;
        }

        /* ANIMATIONS */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .filter-form { grid-template-columns: 1fr; }
            .archive-item { grid-template-columns: 1fr; }
            .archive-actions { align-items: flex-start; }
            .stats-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<aside class="sidebar">
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
        <a href="arsip.php" class="nav-item active"><span class="icon"><i class="fas fa-archive"></i></span> Arsip Surat</a>
    </nav>
</aside>

<div class="main-content">
    <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    
    <div class="page-header">
        <h1><i class="fas fa-archive"></i> Arsip Digital Surat</h1>
        <p>Daftar seluruh surat masuk dan keluar DP3A</p>
    </div>

    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-badge primary">
            <div class="icon"><i class="fas fa-folder-open"></i></div>
            <div class="info">
                <h3>Total Arsip</h3>
                <div class="value"><?= $total_arsip ?></div>
            </div>
        </div>
        <div class="stat-badge success">
            <div class="icon"><i class="fas fa-arrow-down"></i></div>
            <div class="info">
                <h3>Surat Masuk</h3>
                <div class="value"><?= $total_masuk ?></div>
            </div>
        </div>
        <div class="stat-badge warning">
            <div class="icon"><i class="fas fa-arrow-up"></i></div>
            <div class="info">
                <h3>Surat Keluar</h3>
                <div class="value"><?= $total_keluar ?></div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-container">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Pencarian</label>
                <input type="text" name="search" placeholder="Cari nomor surat, perihal, atau pengirim..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-tag"></i> Jenis</label>
                <select name="jenis">
                    <option value="">Semua Jenis</option>
                    <option value="masuk" <?= $filter_jenis==='masuk'?'selected':'' ?>>Surat Masuk</option>
                    <option value="keluar" <?= $filter_jenis==='keluar'?'selected':'' ?>>Surat Keluar</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-building"></i> Bidang</label>
                <select name="bidang">
                    <option value="">Semua Bidang</option>
                    <option value="Perlindungan Khusus Anak" <?= $filter_bidang==='Perlindungan Khusus Anak'?'selected':'' ?>>Perlindungan Khusus Anak</option>
                    <option value="Perlindungan Perempuan" <?= $filter_bidang==='Perlindungan Perempuan'?'selected':'' ?>>Perlindungan Perempuan</option>
                    <option value="Pemenuhan Hak Anak" <?= $filter_bidang==='Pemenuhan Hak Anak'?'selected':'' ?>>Pemenuhan Hak Anak</option>
                    <option value="Kualitas Hidup Perempuan" <?= $filter_bidang==='Kualitas Hidup Perempuan'?'selected':'' ?>>Kualitas Hidup Perempuan</option>
                    <option value="Sekretariat" <?= $filter_bidang==='Sekretariat'?'selected':'' ?>>Sekretariat</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar"></i> Tahun</label>
                <select name="tahun">
                    <option value="">Semua Tahun</option>
                    <?php 
                    $years_result->data_seek(0);
                    while($year = $years_result->fetch_assoc()): 
                    ?>
                    <option value="<?= $year['tahun'] ?>" <?= $filter_tahun==$year['tahun']?'selected':'' ?>><?= $year['tahun'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                <a href="arsip.php" class="btn btn-outline"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>

    <!-- Archive List -->
    <div class="archive-container">
        <div class="archive-header">
            <h2><i class="fas fa-list"></i> Daftar Arsip</h2>
            <span class="archive-count"><?= $total_arsip ?> dokumen</span>
        </div>
        
        <div class="archive-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                <div class="archive-item">
                    <div class="archive-main">
                        <h3><?= htmlspecialchars($row['no_surat']) ?></h3>
                        <p><?= htmlspecialchars($row['perihal']) ?></p>
                        <div class="archive-meta">
                            <span><i class="fas fa-building"></i> <?= htmlspecialchars($row['bidang']) ?></span>
                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($row['pengirim']) ?></span>
                            <span><i class="fas fa-calendar"></i> <?= date('d F Y', strtotime($row['tanggal_kirim'])) ?></span>
                            <?php if($row['file_surat']): ?>
                            <span><i class="fas fa-paperclip"></i> File tersedia</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="archive-actions">
                        <span class="badge badge-<?= $row['jenis_surat'] ?>">
                            <i class="fas fa-<?= $row['jenis_surat']=='masuk'?'arrow-down':'arrow-up' ?>"></i>
                            <?= ucfirst($row['jenis_surat']) ?>
                        </span>
                        <a href="pdf.php?id=<?= $row['id'] ?>" class="btn-pdf" target="_blank">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-archive"></i>
                    <h3>Tidak Ada Arsip</h3>
                    <p>Belum ada surat yang tersimpan dalam arsip</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-submit filter on change (optional)
document.querySelectorAll('.filter-container select').forEach(select => {
    select.addEventListener('change', function() {
        // Uncomment to auto-submit on change
        // this.closest('form').submit();
    });
});

// Animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

document.querySelectorAll('.archive-item').forEach(item => {
    item.style.opacity = '0';
    item.style.transform = 'translateY(20px)';
    item.style.transition = 'all 0.4s ease';
    observer.observe(item);
});
</script>
</body>
</html>