<?php
session_start();
require_once 'config.php';

// === FILTER LOGIC ===
$filter_jenis  = isset($_GET['jenis'])  ? sanitize($_GET['jenis'])  : '';
$filter_bidang = isset($_GET['bidang']) ? sanitize($_GET['bidang']) : '';
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search        = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$where = [];
if ($filter_jenis)  $where[] = "jenis_surat = '$filter_jenis'";
if ($filter_bidang) $where[] = "bidang = '$filter_bidang'";
if ($filter_status) $where[] = "status = '$filter_status'";
if ($search)        $where[] = "(no_surat LIKE '%$search%' OR pengirim LIKE '%$search%' OR perihal LIKE '%$search%')";

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// === STATISTIK ===
$total   = $conn->query("SELECT COUNT(*) as c FROM surat")->fetch_assoc()['c'];
$masuk   = $conn->query("SELECT COUNT(*) as c FROM surat WHERE jenis_surat='masuk'")->fetch_assoc()['c'];
$keluar  = $conn->query("SELECT COUNT(*) as c FROM surat WHERE jenis_surat='keluar'")->fetch_assoc()['c'];
$selesai = $conn->query("SELECT COUNT(*) as c FROM surat WHERE status='selesai'")->fetch_assoc()['c'];

// === PAGINATION ===
$per_page   = 15;
$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset     = ($page - 1) * $per_page;
$count_data = $conn->query("SELECT COUNT(*) as c FROM surat $whereClause")->fetch_assoc()['c'];
$total_pages = ceil($count_data / $per_page);

$query  = "SELECT * FROM surat $whereClause ORDER BY created_at DESC LIMIT $offset, $per_page";
$result = $conn->query($query);
$flash  = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DP3A Sistem Manajemen Surat</title>
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
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text);
            line-height: 1.5;
            min-height: 100vh;
        }

        /* SIDEBAR PREMIUM */
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

        /* TOP HEADER */
        .top-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap; gap: 16px;
        }
        .page-title h1 {
            font-size: 26px; font-weight: 700; color: var(--primary);
            margin-bottom: 4px;
        }
        .page-title p { color: var(--text-muted); font-size: 14px; }
        
        .search-box {
            position: relative; width: 320px;
        }
        .search-box input {
            width: 100%; padding: 12px 16px 12px 44px;
            border: 1px solid var(--border); border-radius: var(--radius);
            font-size: 14px; background: var(--surface);
            transition: all 0.2s; box-shadow: var(--shadow-sm);
        }
        .search-box input:focus {
            outline: none; border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
        }
        .search-box i {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 16px;
        }

        /* STATS CARDS PREMIUM */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative; overflow: hidden;
        }
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--secondary) 100%);
            opacity: 0.8;
        }
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(59,130,246,0.3);
        }
        .stat-card.success::before { background: linear-gradient(90deg, #10b981 0%, #059669 100%); }
        .stat-card.warning::before { background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%); }
        .stat-card.info::before { background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); }
        
        .stat-card .icon-wrap {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 16px;
        }
        .stat-card.primary .icon-wrap { background: rgba(15,23,42,0.08); color: var(--primary); }
        .stat-card.success .icon-wrap { background: rgba(16,185,129,0.1); color: var(--success); }
        .stat-card.warning .icon-wrap { background: rgba(245,158,11,0.1); color: var(--accent); }
        .stat-card.info .icon-wrap { background: rgba(59,130,246,0.1); color: var(--secondary); }
        
        .stat-card h3 { font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .stat-card .num { font-size: 32px; font-weight: 700; color: var(--text); line-height: 1; margin-bottom: 8px; }
        .stat-card .sub { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }

        /* TABLE CARD PREMIUM */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
        }
        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
            background: linear-gradient(180deg, #fafafa 0%, #ffffff 100%);
        }
        .card-header h2 {
            font-size: 16px; font-weight: 700; color: var(--primary);
            display: flex; align-items: center; gap: 10px;
        }
        .card-header .badge-count {
            background: var(--primary); color: white;
            padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 16px; border: none; border-radius: var(--radius);
            font-size: 13px; font-weight: 600; cursor: pointer;
            text-decoration: none; transition: all 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 2px 6px rgba(15,23,42,0.2); }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-1px); }
        .btn-outline { background: white; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: #f8fafc; border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: 6px 10px; font-size: 12px; border-radius: 8px; }
        
        /* FILTER BAR */
        .filter-bar {
            padding: 16px 24px;
            background: #fafafa;
            display: flex; gap: 12px; flex-wrap: wrap; align-items: center;
            border-bottom: 1px solid var(--border);
        }
        .filter-bar select, .filter-bar button {
            padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;
            font-size: 13px; background: white; cursor: pointer; transition: all 0.2s;
        }
        .filter-bar select:focus { outline: none; border-color: var(--secondary); box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
        .filter-bar .btn-filter { background: var(--primary); color: white; border: none; }
        .filter-bar .btn-filter:hover { background: var(--primary-light); }
        
        /* TABLE PREMIUM */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
        table th {
            background: #f8fafc; font-weight: 600; color: var(--text-muted);
            text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;
            padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--border);
        }
        table td {
            padding: 14px 20px; border-bottom: 1px solid var(--border);
            color: var(--text); vertical-align: middle;
        }
        table tr:last-child td { border-bottom: none; }
        table tr:hover td { background: #f8fafc; }
        
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
        }
        .badge-masuk { background: rgba(16,185,129,0.1); color: #059669; }
        .badge-keluar { background: rgba(245,158,11,0.1); color: #b45309; }
        
        .action-btns { display: flex; gap: 6px; }
        .action-btn {
            width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border);
            background: white; color: var(--text-muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center; transition: all 0.2s;
        }
        .action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .action-btn.danger:hover { background: var(--danger); border-color: var(--danger); }
        .action-btn.pdf:hover { background: #dc2626; border-color: #dc2626; }
        
        /* PAGINATION */
        .pagination {
            display: flex; justify-content: space-between; align-items: center;
            padding: 16px 24px; border-top: 1px solid var(--border);
            font-size: 13px; color: var(--text-muted);
        }
        .pagination a {
            padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px;
            text-decoration: none; color: var(--text); transition: all 0.2s;
        }
        .pagination a:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .pagination a.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* ALERTS & MODAL */
        .alert {
            padding: 14px 18px; border-radius: var(--radius); margin-bottom: 24px;
            display: flex; align-items: center; gap: 10px; font-size: 13px;
            animation: slideIn 0.3s ease;
        }
        .alert-success { background: rgba(16,185,129,0.1); color: #059669; border: 1px solid rgba(16,185,129,0.2); }
        .alert-danger { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.5);
            display: none; align-items: center; justify-content: center; z-index: 2000;
            backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: white; padding: 28px; border-radius: var(--radius-lg);
            max-width: 420px; width: 90%; text-align: center;
            box-shadow: var(--shadow-lg); animation: scaleIn 0.2s ease;
        }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes scaleIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .top-header { flex-direction: column; align-items: flex-start; }
            .search-box { width: 100%; }
        }
        .menu-toggle { display: none; background: none; border: none; font-size: 22px; color: var(--primary); cursor: pointer; margin-right: 12px; }
    </style>
</head>
<body>

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
        <a href="index.php" class="nav-item active"><span class="icon"><i class="fas fa-home"></i></span> Dashboard</a>
        <a href="tambah.php" class="nav-item"><span class="icon"><i class="fas fa-plus-circle"></i></span> Tambah Surat</a>
        
        <!-- BIDANG DIPBATASI MENJADI SALAH SATU SAJA -->
        <div class="nav-label">BIDANG</div>
        <a href="index.php?bidang=Perlindungan%20Khusus%20Anak" class="nav-item"><span class="icon"><i class="fas fa-shield-alt"></i></span> Perlindungan Khusus Anak</a>
        
        <div class="nav-label">LAPORAN</div>
        <a href="statistik.php" class="nav-item"><span class="icon"><i class="fas fa-chart-bar"></i></span> Statistik</a>
        <a href="arsip.php" class="nav-item"><span class="icon"><i class="fas fa-archive"></i></span> Arsip Surat</a>
    </nav>
</aside>

<div class="main-content">
    <header class="top-header">
        <div style="display:flex;align-items:center;">
            <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
            <div class="page-title">
                <h1>Manajemen Surat</h1>
                <p>Sistem pengelolaan surat masuk & keluar DP3A Bidang Perlindungan Khusus Anak</p>
            </div>
        </div>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Cari nomor surat, perihal, atau pengirim..." value="<?= htmlspecialchars($search) ?>">
        </div>
    </header>

    <div class="content-area">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fas fa-<?= $flash['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= $flash['msg'] ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="icon-wrap"><i class="fas fa-folder-open"></i></div>
                <h3>Total Surat</h3>
                <div class="num"><?= $total ?></div>
                <div class="sub"><i class="fas fa-database"></i> Seluruh data</div>
            </div>
            <div class="stat-card success">
                <div class="icon-wrap"><i class="fas fa-arrow-down"></i></div>
                <h3>Surat Masuk</h3>
                <div class="num"><?= $masuk ?></div>
                <div class="sub"><i class="fas fa-check"></i> Diterima</div>
            </div>
            <div class="stat-card warning">
                <div class="icon-wrap"><i class="fas fa-arrow-up"></i></div>
                <h3>Surat Keluar</h3>
                <div class="num"><?= $keluar ?></div>
                <div class="sub"><i class="fas fa-paper-plane"></i> Dikirim</div>
            </div>
            <div class="stat-card info">
                <div class="icon-wrap"><i class="fas fa-check-double"></i></div>
                <h3>Selesai</h3>
                <div class="num"><?= $selesai ?></div>
                <div class="sub"><i class="fas fa-tasks"></i> Tuntas</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Daftar Surat <span class="badge-count"><?= $count_data ?> data</span></h2>
                <div style="display:flex;gap:10px;">
                    <a href="tambah.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Surat</a>
                    <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Cetak</button>
                </div>
            </div>

            <div class="filter-bar">
                <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <select name="jenis">
                        <option value="">Semua Jenis</option>
                        <option value="masuk" <?= $filter_jenis==='masuk'?'selected':'' ?>>Surat Masuk</option>
                        <option value="keluar" <?= $filter_jenis==='keluar'?'selected':'' ?>>Surat Keluar</option>
                    </select>
                    
                    <!-- PERUBAHAN: Dropdown Bidang hanya 1 opsi -->
                    <select name="bidang">
                        <option value="">Semua Bidang</option>
                        <option value="Perlindungan Khusus Anak" <?= $filter_bidang==='Perlindungan Khusus Anak'?'selected':'' ?>>Perlindungan Khusus Anak</option>
                    </select>
                    
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="diterima" <?= $filter_status==='diterima'?'selected':'' ?>>Diterima</option>
                        <option value="diproses" <?= $filter_status==='diproses'?'selected':'' ?>>Diproses</option>
                        <option value="selesai" <?= $filter_status==='selesai'?'selected':'' ?>>Selesai</option>
                        <option value="ditolak" <?= $filter_status==='ditolak'?'selected':'' ?>>Ditolak</option>
                    </select>
                    <button type="submit" class="btn btn-filter"><i class="fas fa-filter"></i> Filter</button>
                    <a href="index.php" class="btn btn-outline" style="padding:8px 12px;"><i class="fas fa-redo"></i> Reset</a>
                </form>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>No. Surat</th>
                            <th>Jenis</th>
                            <th>Bidang</th>
                            <th>Pengirim</th>
                            <th>Tanggal</th>
                            <th>Perihal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $no = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><code style="background:#f1f5f9;padding:4px 8px;border-radius:6px;font-size:11px;color:var(--primary);font-weight:600;"><?= htmlspecialchars($row['no_surat']) ?></code></td>
                                <td><span class="badge badge-<?= $row['jenis_surat'] ?>"><i class="fas fa-<?= $row['jenis_surat']=='masuk'?'arrow-down':'arrow-up' ?>"></i> <?= ucfirst($row['jenis_surat']) ?></span></td>
                                <td><?= htmlspecialchars($row['bidang']) ?></td>
                                <td><?= htmlspecialchars($row['pengirim']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['tanggal_kirim'])) ?></td>
                                <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($row['perihal']) ?></td>
                                <td>
                                    <form method="POST" action="update_status.php" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding:6px 10px;font-size:11px;border:1px solid var(--border);border-radius:6px;background:white;cursor:pointer;">
                                            <option value="diterima" <?= $row['status']=='diterima'?'selected':'' ?>>Diterima</option>
                                            <option value="diproses" <?= $row['status']=='diproses'?'selected':'' ?>>Diproses</option>
                                            <option value="selesai" <?= $row['status']=='selesai'?'selected':'' ?>>Selesai</option>
                                            <option value="ditolak" <?= $row['status']=='ditolak'?'selected':'' ?>>Ditolak</option>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="view.php?id=<?= $row['id'] ?>" class="action-btn" title="Lihat"><i class="fas fa-eye"></i></a>
                                        <a href="edit.php?id=<?= $row['id'] ?>" class="action-btn" title="Edit"><i class="fas fa-edit"></i></a>
                                        <button onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes($row['no_surat']) ?>')" class="action-btn danger" title="Hapus"><i class="fas fa-trash"></i></button>
                                        <a href="pdf.php?id=<?= $row['id'] ?>" class="action-btn pdf" title="PDF"><i class="fas fa-file-pdf"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                                <i class="fas fa-inbox" style="font-size:40px;margin-bottom:12px;display:block;opacity:0.3;"></i>
                                Tidak ada data surat
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <span>Halaman <?= $page ?> dari <?= $total_pages ?></span>
                <div style="display:flex;gap:6px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?>&jenis=<?= $filter_jenis ?>&bidang=<?= $filter_bidang ?>&status=<?= $filter_status ?>&search=<?= $search ?>">← Prev</a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&jenis=<?= $filter_jenis ?>&bidang=<?= $filter_bidang ?>&status=<?= $filter_status ?>&search=<?= $search ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>&jenis=<?= $filter_jenis ?>&bidang=<?= $filter_bidang ?>&status=<?= $filter_status ?>&search=<?= $search ?>">Next →</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <i class="fas fa-exclamation-triangle" style="font-size:48px;color:var(--accent);margin-bottom:16px;"></i>
        <h3 style="margin:0 0 8px;font-size:18px;">Konfirmasi Hapus</h3>
        <p id="deleteText" style="color:var(--text-muted);margin-bottom:24px;">Apakah Anda yakin ingin menghapus surat ini?</p>
        <div style="display:flex;gap:12px;justify-content:center;">
            <button onclick="closeModal()" class="btn btn-outline" style="padding:10px 20px;">Batal</button>
            <a id="deleteLink" href="#" class="btn" style="background:var(--danger);color:white;padding:10px 20px;">Ya, Hapus</a>
        </div>
    </div>
</div>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }
function confirmDelete(id, noSurat) {
    document.getElementById('deleteText').textContent = `Apakah Anda yakin ingin menghapus surat "${noSurat}"?`;
    document.getElementById('deleteLink').href = `hapus.php?id=${id}`;
    document.getElementById('deleteModal').classList.add('active');
}
function closeModal() { document.getElementById('deleteModal').classList.remove('active'); }
setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); }
}, 3000);
document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') window.location.href = `index.php?search=${encodeURIComponent(this.value)}`;
});
</script>
</body>
</html>