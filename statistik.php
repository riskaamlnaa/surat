<?php
session_start();
require_once 'config.php';

// Statistik per Bidang
$stats_bidang = $conn->query("
    SELECT bidang, COUNT(*) as total 
    FROM surat 
    GROUP BY bidang
    ORDER BY total DESC
")->fetch_all(MYSQLI_ASSOC);

// Statistik per Bulan
$stats_bulan = $conn->query("
    SELECT 
        DATE_FORMAT(tanggal_kirim, '%Y-%m') as bulan_code,
        DATE_FORMAT(tanggal_kirim, '%M %Y') as bulan_nama,
        COUNT(*) as total 
    FROM surat 
    GROUP BY DATE_FORMAT(tanggal_kirim, '%Y-%m'), DATE_FORMAT(tanggal_kirim, '%M %Y')
    ORDER BY bulan_code DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Statistik Status
$stats_status = $conn->query("
    SELECT status, COUNT(*) as total 
    FROM surat 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

// Statistik Jenis Surat
$stats_jenis = $conn->query("
    SELECT jenis_surat, COUNT(*) as total 
    FROM surat 
    GROUP BY jenis_surat
")->fetch_all(MYSQLI_ASSOC);

// Total Keseluruhan
$total_surat = $conn->query("SELECT COUNT(*) as c FROM surat")->fetch_assoc()['c'];
$total_masuk = $conn->query("SELECT COUNT(*) as c FROM surat WHERE jenis_surat='masuk'")->fetch_assoc()['c'];
$total_keluar = $conn->query("SELECT COUNT(*) as c FROM surat WHERE jenis_surat='keluar'")->fetch_assoc()['c'];
$total_selesai = $conn->query("SELECT COUNT(*) as c FROM surat WHERE status='selesai'")->fetch_assoc()['c'];

// Surat bulan ini
$surat_bulan_ini = $conn->query("
    SELECT COUNT(*) as c FROM surat 
    WHERE MONTH(tanggal_kirim) = MONTH(CURRENT_DATE()) 
    AND YEAR(tanggal_kirim) = YEAR(CURRENT_DATE())
")->fetch_assoc()['c'];

// Rata-rata per bulan
$rata_rata = count($stats_bulan) > 0 ? round(array_sum(array_column($stats_bulan, 'total')) / count($stats_bulan)) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistik & Laporan - DP3A</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { 
            --primary: #0f172a; 
            --primary-light: #1e293b;
            --secondary: #3b82f6;
            --accent: #f59e0b;
            --success: #10b981;
            --warning: #f59e0b;
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
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 280px; 
            height: 100vh; 
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
        .sidebar-nav { padding: 20px 0; max-height: calc(100vh - 220px); overflow-y: auto; }
        .nav-label { padding: 16px 24px 8px; font-size: 10px; text-transform: uppercase; color: rgba(255,255,255,0.4); font-weight: 700; letter-spacing: 1.5px; }
        .nav-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 12px 24px; 
            color: rgba(255,255,255,0.8); 
            text-decoration: none; 
            font-size: 14px; 
            font-weight: 500;
            transition: all 0.25s ease; 
            border-left: 3px solid transparent; 
            margin: 2px 0;
        }
        .nav-item:hover { background: rgba(255,255,255,0.08); color: white; border-left-color: var(--accent); padding-left: 28px; }
        .nav-item.active { background: linear-gradient(90deg, rgba(245,158,11,0.15) 0%, transparent 100%); color: white; border-left-color: var(--accent); font-weight: 600; }
        .nav-item .icon { width: 20px; text-align: center; font-size: 15px; }

        .main-content { margin-left: 280px; padding: 32px; min-height: 100vh; }
        
        .page-header { 
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); 
            color: white;
            padding: 30px; 
            border-radius: var(--radius-lg); 
            margin-bottom: 30px; 
            box-shadow: var(--shadow-lg);
        }
        .page-header h1 { 
            font-size: 28px; 
            font-weight: 700; 
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header p { color: rgba(255,255,255,0.9); font-size: 15px; }
        
        .back-btn { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            padding: 10px 20px; 
            background: var(--surface); 
            color: var(--primary); 
            text-decoration: none; 
            border-radius: var(--radius); 
            font-size: 14px; 
            font-weight: 600; 
            margin-bottom: 25px; 
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
            border: 1.5px solid var(--border);
        }
        .back-btn:hover { 
            transform: translateY(-2px); 
            box-shadow: var(--shadow-md);
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: var(--surface);
            padding: 25px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            border-top: 4px solid var(--primary);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(15,23,42,0.05) 0%, transparent 100%);
            border-radius: 0 0 0 100%;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        .summary-card.success { border-top-color: var(--success); }
        .summary-card.warning { border-top-color: var(--warning); }
        .summary-card.info { border-top-color: var(--secondary); }
        
        .summary-card .icon-wrapper {
            width: 60px;
            height: 60px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 28px;
        }
        .summary-card.primary .icon-wrapper { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: var(--primary); }
        .summary-card.success .icon-wrapper { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: var(--success); }
        .summary-card.warning .icon-wrapper { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: var(--warning); }
        .summary-card.info .icon-wrapper { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: var(--secondary); }
        
        .summary-card h3 {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .summary-card .value {
            font-size: 36px;
            font-weight: 700;
            color: var(--text);
            line-height: 1;
            margin-bottom: 8px;
        }
        .summary-card .trend {
            font-size: 12px;
            color: var(--success);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .stat-card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .stat-card-header.success { background: linear-gradient(135deg, var(--success) 0%, #059669 100%); }
        .stat-card-header.warning { background: linear-gradient(135deg, var(--warning) 0%, #d97706 100%); }
        .stat-card-header.info { background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%); }
        
        .stat-card-header h3 {
            font-size: 16px;
            font-weight: 700;
            flex: 1;
        }
        .stat-card-header i {
            font-size: 20px;
            opacity: 0.9;
        }
        .stat-card-body {
            padding: 25px;
        }
        
        /* Progress Bars */
        .progress-item {
            margin-bottom: 20px;
        }
        .progress-item .label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
            color: var(--text);
            font-weight: 500;
        }
        .progress-bar {
            height: 10px;
            background: var(--border);
            border-radius: 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 10px;
            transition: width 0.8s ease;
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 280px;
            margin-top: 15px;
        }

        /* Status List */
        .status-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            background: var(--bg);
            border-radius: var(--radius);
            border-left: 4px solid var(--primary);
            transition: transform 0.2s;
        }
        .status-item:hover { transform: translateX(4px); }
        .status-item.success { border-left-color: var(--success); }
        .status-item.warning { border-left-color: var(--warning); }
        .status-item.info { border-left-color: var(--secondary); }
        
        .status-item .status-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .status-item .status-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .status-item.success .status-icon { background: #d1fae5; color: var(--success); }
        .status-item.warning .status-icon { background: #fef3c7; color: var(--warning); }
        .status-item.info .status-icon { background: #dbeafe; color: var(--secondary); }
        
        .status-item .status-label {
            font-weight: 600;
            color: var(--text);
        }
        .status-item .status-count {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .summary-grid { grid-template-columns: 1fr; }
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
        <a href="statistik.php" class="nav-item active"><span class="icon"><i class="fas fa-chart-bar"></i></span> Statistik</a>
        <a href="arsip.php" class="nav-item"><span class="icon"><i class="fas fa-archive"></i></span> Arsip Surat</a>
    </nav>
</aside>

<div class="main-content">
    <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    
    <div class="page-header">
        <h1><i class="fas fa-chart-line"></i> Statistik & Laporan Surat</h1>
        <p>Laporan rekapitulasi surat masuk dan keluar DP3A</p>
    </div>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card primary">
            <div class="icon-wrapper"><i class="fas fa-folder-open"></i></div>
            <h3>Total Surat</h3>
            <div class="value"><?= $total_surat ?></div>
            <div class="trend"><i class="fas fa-database"></i> Seluruh data</div>
        </div>
        <div class="summary-card success">
            <div class="icon-wrapper"><i class="fas fa-arrow-down"></i></div>
            <h3>Surat Masuk</h3>
            <div class="value"><?= $total_masuk ?></div>
            <div class="trend"><i class="fas fa-check-circle"></i> Diterima</div>
        </div>
        <div class="summary-card warning">
            <div class="icon-wrapper"><i class="fas fa-arrow-up"></i></div>
            <h3>Surat Keluar</h3>
            <div class="value"><?= $total_keluar ?></div>
            <div class="trend"><i class="fas fa-paper-plane"></i> Dikirim</div>
        </div>
        <div class="summary-card info">
            <div class="icon-wrapper"><i class="fas fa-calendar-check"></i></div>
            <h3>Bulan Ini</h3>
            <div class="value"><?= $surat_bulan_ini ?></div>
            <div class="trend"><i class="fas fa-clock"></i> <?= date('F Y') ?></div>
        </div>
    </div>

    <div class="stats-grid">
        <!-- Surat per Bidang -->
        <div class="stat-card">
            <div class="stat-card-header">
                <i class="fas fa-building"></i>
                <h3>Surat per Bidang</h3>
            </div>
            <div class="stat-card-body">
                <?php if (count($stats_bidang) > 0): ?>
                    <?php foreach ($stats_bidang as $bidang): 
                        $percentage = $total_surat > 0 ? ($bidang['total'] / $total_surat * 100) : 0;
                    ?>
                    <div class="progress-item">
                        <div class="label">
                            <span><?= htmlspecialchars($bidang['bidang']) ?></span>
                            <span><?= $bidang['total'] ?> surat</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;"><?= number_format($percentage, 1) ?>%</div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center;color:var(--text-muted);padding:40px;"><i class="fas fa-inbox" style="font-size:48px;margin-bottom:15px;display:block;opacity:0.3;"></i>Belum ada data</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Surat per Bulan -->
        <div class="stat-card">
            <div class="stat-card-header success">
                <i class="fas fa-calendar-alt"></i>
                <h3>Surat per Bulan (6 Bulan Terakhir)</h3>
            </div>
            <div class="stat-card-body">
                <?php if (count($stats_bulan) > 0): ?>
                    <div class="chart-container">
                        <canvas id="bulanChart"></canvas>
                    </div>
                <?php else: ?>
                    <p style="text-align:center;color:var(--text-muted);padding:40px;"><i class="fas fa-calendar-times" style="font-size:48px;margin-bottom:15px;display:block;opacity:0.3;"></i>Belum ada data</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Surat per Status -->
        <div class="stat-card">
            <div class="stat-card-header warning">
                <i class="fas fa-tasks"></i>
                <h3>Surat per Status</h3>
            </div>
            <div class="stat-card-body">
                <?php if (count($stats_status) > 0): ?>
                    <div class="status-list">
                        <?php foreach ($stats_status as $status): 
                            $badgeClass = $status['status'] == 'selesai' ? 'success' : ($status['status'] == 'diproses' ? 'warning' : 'info');
                            $icon = $status['status'] == 'selesai' ? 'fa-check-circle' : ($status['status'] == 'diproses' ? 'fa-clock' : 'fa-inbox');
                        ?>
                        <div class="status-item <?= $badgeClass ?>">
                            <div class="status-info">
                                <div class="status-icon"><i class="fas <?= $icon ?>"></i></div>
                                <div class="status-label"><?= ucfirst($status['status']) ?></div>
                            </div>
                            <div class="status-count"><?= $status['total'] ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align:center;color:var(--text-muted);padding:40px;"><i class="fas fa-tasks" style="font-size:48px;margin-bottom:15px;display:block;opacity:0.3;"></i>Belum ada data</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Distribusi Jenis Surat -->
        <div class="stat-card">
            <div class="stat-card-header info">
                <i class="fas fa-chart-pie"></i>
                <h3>Distribusi Jenis Surat</h3>
            </div>
            <div class="stat-card-body">
                <?php if (count($stats_jenis) > 0): ?>
                    <div class="chart-container">
                        <canvas id="jenisChart"></canvas>
                    </div>
                <?php else: ?>
                    <p style="text-align:center;color:var(--text-muted);padding:40px;"><i class="fas fa-chart-pie" style="font-size:48px;margin-bottom:15px;display:block;opacity:0.3;"></i>Belum ada data</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Chart Bulan
<?php if (count($stats_bulan) > 0): ?>
const bulanCtx = document.getElementById('bulanChart').getContext('2d');
new Chart(bulanCtx, {
    type: 'bar',
    data: {
        labels: [<?php foreach (array_reverse($stats_bulan) as $b) echo "'".htmlspecialchars($b['bulan_nama'])."',"; ?>],
        datasets: [{
            label: 'Jumlah Surat',
            data: [<?php foreach (array_reverse($stats_bulan) as $b) echo $b['total'].","; ?>],
            backgroundColor: 'rgba(15, 23, 42, 0.9)',
            borderColor: 'rgba(15, 23, 42, 1)',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                padding: 12,
                titleFont: { size: 13 },
                bodyFont: { size: 12 },
                borderColor: 'rgba(15, 23, 42, 1)',
                borderWidth: 1
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { 
                    stepSize: 1,
                    font: { size: 11 }
                },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 } }
            }
        }
    }
});
<?php endif; ?>

// Chart Jenis
<?php if (count($stats_jenis) > 0): ?>
const jenisCtx = document.getElementById('jenisChart').getContext('2d');
new Chart(jenisCtx, {
    type: 'doughnut',
    data: {
        labels: [<?php foreach ($stats_jenis as $j) echo "'".ucfirst($j['jenis_surat'])."',"; ?>],
        datasets: [{
            data: [<?php foreach ($stats_jenis as $j) echo $j['total'].","; ?>],
            backgroundColor: [
                'rgba(16, 185, 129, 0.85)',
                'rgba(245, 158, 11, 0.85)'
            ],
            borderColor: [
                'rgba(16, 185, 129, 1)',
                'rgba(245, 158, 11, 1)'
            ],
            borderWidth: 3,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { 
                    padding: 20, 
                    usePointStyle: true,
                    pointStyle: 'circle',
                    font: { size: 12, weight: '600' }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, 0.95)',
                padding: 12,
                titleFont: { size: 13 },
                bodyFont: { size: 12 },
                borderColor: 'rgba(15, 23, 42, 1)',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.parsed;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(1);
                        return label + ': ' + value + ' (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>
</body>
</html>