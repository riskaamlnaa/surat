<?php
session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = $conn->query("SELECT * FROM surat WHERE id = $id");
if ($res->num_rows === 0) {
    flash("❌ Surat tidak ditemukan!", "danger");
    header("Location: index.php");
    exit;
}
$s = $res->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_surat = sanitize($_POST['no_surat']);
    $jenis_surat = sanitize($_POST['jenis_surat']);
    $bidang = sanitize($_POST['bidang']);
    $pengirim = sanitize($_POST['pengirim']);
    $tanggal_kirim = sanitize($_POST['tanggal_kirim']);
    $tanggal_terima = sanitize($_POST['tanggal_terima']);
    $perihal = sanitize($_POST['perihal']);
    $disposisi = sanitize($_POST['disposisi']);
    $status = sanitize($_POST['status']);
    $keterangan = sanitize($_POST['keterangan']);
    
    $file_surat = $s['file_surat'];
    
    if (isset($_FILES['file_surat']) && $_FILES['file_surat']['error'] === 0) {
        $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];
        $max = 5 * 1024 * 1024;
        
        if (in_array($_FILES['file_surat']['type'], $allowed) && $_FILES['file_surat']['size'] <= $max) {
            if (!is_dir('uploads')) mkdir('uploads', 0777, true);
            $ext = pathinfo($_FILES['file_surat']['name'], PATHINFO_EXTENSION);
            $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $no_surat) . '_' . time() . '.' . $ext;
            $path = 'uploads/' . $name;
            
            if (move_uploaded_file($_FILES['file_surat']['tmp_name'], $path)) {
                if ($s['file_surat'] && file_exists($s['file_surat'])) unlink($s['file_surat']);
                $file_surat = $path;
            } else {
                flash("❌ Gagal upload file", "danger");
            }
        } else {
            flash("❌ File harus PDF/PNG/JPG (maks 5MB)", "danger");
        }
    }
    
    $check = $conn->query("SELECT id FROM surat WHERE no_surat='$no_surat' AND id != $id");
    if ($check && $check->num_rows > 0) {
        flash("❌ Nomor surat sudah terdaftar!", "danger");
    } else {
        $stmt = $conn->prepare("UPDATE surat SET no_surat=?, jenis_surat=?, bidang=?, pengirim=?, tanggal_kirim=?, tanggal_terima=?, perihal=?, disposisi=?, status=?, keterangan=?, file_surat=? WHERE id=?");
        $stmt->bind_param("sssssssssssi", $no_surat, $jenis_surat, $bidang, $pengirim, $tanggal_kirim, $tanggal_terima, $perihal, $disposisi, $status, $keterangan, $file_surat, $id);
        
        if ($stmt->execute()) {
            flash("✅ Surat berhasil diperbarui!", "success");
            header("Location: index.php");
            exit;
        } else {
            flash("❌ Gagal update surat", "danger");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Surat - DP3A</title>
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
            min-height: 100vh;
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
            display: flex; justify-content: space-between; align-items: center;
            border: 1px solid var(--border);
            animation: slideDown 0.4s ease;
        }
        .page-title {
            display: flex; align-items: center; gap: 14px;
        }
        .page-title i {
            font-size: 32px; color: var(--secondary);
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
        }
        .page-title h1 {
            font-size: 26px; font-weight: 700; color: var(--primary);
            margin-bottom: 4px;
        }
        .page-title p { color: var(--text-muted); font-size: 14px; }
        
        /* FORM CARD */
        .form-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }
        .form-header {
            background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%);
            color: white;
            padding: 24px 32px;
            display: flex; align-items: center; gap: 12px;
        }
        .form-header i { font-size: 24px; }
        .form-header h2 { font-size: 20px; font-weight: 700; }
        
        .form-body { padding: 32px; }
        
        /* FORM GRID */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }
        .form-group {
            display: flex; flex-direction: column; gap: 8px;
        }
        .form-group.full-width { grid-column: 1 / -1; }
        
        .form-group label {
            font-size: 13px; font-weight: 600; color: var(--text);
            display: flex; align-items: center; gap: 6px;
        }
        .form-group label .required { color: var(--danger); }
        .form-group label i { color: var(--secondary); font-size: 14px; }
        
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i.input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); font-size: 15px;
            transition: color 0.2s;
        }
        
        .form-control {
            width: 100%; padding: 12px 14px 12px 42px;
            border: 1.5px solid var(--border); border-radius: var(--radius);
            font-size: 14px; background: var(--surface);
            transition: all 0.2s;
            color: var(--text);
        }
        .form-control:focus {
            outline: none; border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(59,130,246,0.1);
        }
        .form-control:focus + i.input-icon,
        .input-wrapper:focus-within i.input-icon {
            color: var(--secondary);
        }
        textarea.form-control {
            resize: vertical; min-height: 100px;
        }
        select.form-control { cursor: pointer; }
        
        /* FILE INFO */
        .file-info {
            padding: 16px 20px;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 1.5px solid #f59e0b;
            border-radius: var(--radius);
            margin-bottom: 20px;
            display: flex; align-items: center; gap: 12px;
            animation: slideIn 0.3s ease;
        }
        .file-info i {
            font-size: 24px; color: #b45309;
            width: 40px; height: 40px;
            border-radius: 10px;
            background: rgba(245,158,11,0.1);
            display: flex; align-items: center; justify-content: center;
        }
        .file-info a {
            color: #92400e;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .file-info a:hover { color: #78350f; text-decoration: underline; }
        .file-info .file-size {
            font-size: 12px; color: #92400e;
            margin-left: 8px;
        }
        
        /* FILE UPLOAD PREMIUM */
        .file-upload-container {
            margin: 24px 0;
        }
        .file-upload-zone {
            border: 2.5px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 40px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #fafafa 0%, #f8fafc 100%);
            position: relative;
            overflow: hidden;
        }
        .file-upload-zone::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(59,130,246,0.05) 0%, rgba(59,130,246,0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .file-upload-zone:hover {
            border-color: var(--secondary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .file-upload-zone:hover::before { opacity: 1; }
        .file-upload-zone.active {
            border-color: var(--success);
            background: linear-gradient(135deg, rgba(16,185,129,0.05) 0%, rgba(16,185,129,0.1) 100%);
        }
        .file-upload-zone i {
            font-size: 48px; color: var(--text-muted);
            margin-bottom: 16px;
            transition: all 0.3s;
        }
        .file-upload-zone:hover i {
            color: var(--secondary);
            transform: scale(1.1);
        }
        .file-upload-zone h3 {
            font-size: 16px; font-weight: 600; color: var(--text);
            margin-bottom: 6px;
        }
        .file-upload-zone p {
            font-size: 13px; color: var(--text-muted);
        }
        
        .file-preview {
            margin-top: 16px;
            padding: 16px;
            background: white;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            display: none;
            animation: slideUp 0.3s ease;
        }
        .file-preview.show { display: block; }
        .file-preview i { color: var(--success); font-size: 24px; margin-right: 10px; }
        .file-preview .file-name {
            font-weight: 600; color: var(--text);
            font-size: 14px;
        }
        .file-preview .file-size {
            font-size: 12px; color: var(--text-muted);
        }
        
        /* FORM ACTIONS */
        .form-actions {
            display: flex; justify-content: flex-end; gap: 12px;
            padding-top: 28px;
            border-top: 2px solid var(--border);
            margin-top: 28px;
        }
        
        .btn {
            padding: 12px 24px; border: none; border-radius: var(--radius);
            font-size: 14px; font-weight: 600; cursor: pointer;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
            transition: all 0.3s ease; position: relative; overflow: hidden;
        }
        .btn::before {
            content: ''; position: absolute; top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        .btn:hover::before { left: 100%; }
        
        .btn-outline {
            background: white; border: 1.5px solid var(--border); color: var(--text);
        }
        .btn-outline:hover {
            background: var(--surface); border-color: var(--primary);
            color: var(--primary); transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, var(--accent) 0%, #d97706 100%);
            color: white; box-shadow: 0 4px 12px rgba(245,158,11,0.3);
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245,158,11,0.4);
        }
        
        /* ALERTS */
        .alert {
            padding: 16px 20px; border-radius: var(--radius);
            margin-bottom: 24px; display: flex; align-items: center; gap: 12px;
            font-size: 14px; font-weight: 500;
            animation: slideIn 0.4s ease;
            border-left: 4px solid;
        }
        .alert-success {
            background: rgba(16,185,129,0.1); color: #059669;
            border-left-color: var(--success);
        }
        .alert-danger {
            background: rgba(239,68,68,0.1); color: #dc2626;
            border-left-color: var(--danger);
        }
        .alert i { font-size: 20px; }
        
        /* ANIMATIONS */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); width: 260px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        }
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
            <a href="index.php" class="nav-item"><span class="icon"><i class="fas fa-home"></i></span> Dashboard</a>
            <!-- BIDANG DIPBATASI MENJADI SALAH SATU SAJA -->
            <div class="nav-label">BIDANG</div>
            <a href="index.php?bidang=Perlindungan%20Khusus%20Anak" class="nav-item"><span class="icon"><i class="fas fa-shield-alt"></i></span> Perlindungan Khusus Anak</a>
            <div class="nav-label">LAPORAN</div>
            <a href="statistik.php" class="nav-item"><span class="icon"><i class="fas fa-chart-bar"></i></span> Statistik</a>
            <a href="arsip.php" class="nav-item"><span class="icon"><i class="fas fa-archive"></i></span> Arsip Surat</a>
        </nav>
    </aside>

    <div class="main-content">
        <div class="page-header">
            <div class="page-title">
                <i class="fas fa-edit"></i>
                <div>
                    <h1>Edit Surat</h1>
                    <p>Perbarui data surat yang sudah tersimpan</p>
                </div>
            </div>
            <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <div class="form-container">
            <div class="form-header">
                <i class="fas fa-file-edit"></i>
                <h2>Formulir Edit Surat</h2>
            </div>
            
            <div class="form-body">
                <?php if(isset($_SESSION['flash'])): ?>
                    <div class="alert alert-<?=$_SESSION['flash']['type']?>">
                        <i class="fas fa-<?= $_SESSION['flash']['type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= $_SESSION['flash']['msg'] ?>
                    </div>
                    <?php unset($_SESSION['flash']); endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="formEdit">
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-hashtag"></i> No. Surat <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="text" name="no_surat" class="form-control" value="<?=htmlspecialchars($s['no_surat'])?>" required>
                                <i class="fas fa-file-alt input-icon"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tag"></i> Jenis Surat <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <select name="jenis_surat" class="form-control" required>
                                    <option value="">Pilih Jenis</option>
                                    <option value="masuk" <?=$s['jenis_surat']=='masuk'?'selected':''?>>Surat Masuk</option>
                                    <option value="keluar" <?=$s['jenis_surat']=='keluar'?'selected':''?>>Surat Keluar</option>
                                </select>
                                <i class="fas fa-chevron-down input-icon" style="left: auto; right: 14px;"></i>
                            </div>
                        </div>
                        
                        <!-- PERUBAHAN: Opsi Bidang Dipisah menjadi Satu saja -->
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Bidang <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <select name="bidang" class="form-control" required>
                                    <option value="">Pilih Bidang</option>
                                    <option value="Perlindungan Khusus Anak" <?=$s['bidang']=='Perlindungan Khusus Anak'?'selected':''?>>Perlindungan Khusus Anak</option>
                                </select>
                                <i class="fas fa-chevron-down input-icon" style="left: auto; right: 14px;"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Pengirim / Tujuan <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="text" name="pengirim" class="form-control" value="<?=htmlspecialchars($s['pengirim'])?>" required>
                                <i class="fas fa-building input-icon"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Tanggal Kirim <span class="required">*</span></label>
                            <div class="input-wrapper">
                                <input type="date" name="tanggal_kirim" class="form-control" value="<?=$s['tanggal_kirim']?>" required>
                                <i class="fas fa-calendar input-icon"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Tanggal Terima</label>
                            <div class="input-wrapper">
                                <input type="date" name="tanggal_terima" class="form-control" value="<?=$s['tanggal_terima']?>">
                                <i class="fas fa-calendar-check input-icon"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-heading"></i> Perihal <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <textarea name="perihal" class="form-control" required><?=htmlspecialchars($s['perihal'])?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-share"></i> Disposisi</label>
                            <div class="input-wrapper">
                                <input type="text" name="disposisi" class="form-control" value="<?=htmlspecialchars($s['disposisi'])?>">
                                <i class="fas fa-user-tie input-icon"></i>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tasks"></i> Status</label>
                            <div class="input-wrapper">
                                <select name="status" class="form-control">
                                    <option value="diterima" <?=$s['status']=='diterima'?'selected':''?>>Diterima</option>
                                    <option value="diproses" <?=$s['status']=='diproses'?'selected':''?>>Diproses</option>
                                    <option value="selesai" <?=$s['status']=='selesai'?'selected':''?>>Selesai</option>
                                    <option value="ditolak" <?=$s['status']=='ditolak'?'selected':''?>>Ditolak</option>
                                </select>
                                <i class="fas fa-chevron-down input-icon" style="left: auto; right: 14px;"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="file-upload-container">
                        <label style="display:block; font-size:13px; font-weight:600; margin-bottom:8px;">
                            <i class="fas fa-paperclip" style="color:var(--secondary);"></i> File Surat Terlampir
                        </label>
                        
                        <?php if($s['file_surat'] && file_exists($s['file_surat'])): ?>
                        <div class="file-info">
                            <i class="fas fa-file-alt"></i>
                            <div>
                                <strong>File saat ini:</strong><br>
                                <a href="<?=$s['file_surat']?>" target="_blank"><?=basename($s['file_surat'])?></a>
                                <span class="file-size">(<?=round(filesize($s['file_surat'])/1024, 2)?> KB)</span>
                            </div>
                        </div>
                        <?php elseif($s['file_surat']): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            File terdaftar tapi tidak ditemukan: <strong><?=$s['file_surat']?></strong>
                        </div>
                        <?php endif; ?>
                        
                        <!-- INPUT DILETAKKAN DI LUAR KOTAK AGAR TIDAK HILANG SAAT DI-OVERWRITE JS -->
                        <input type="file" name="file_surat" id="file_surat" accept=".pdf,.png,.jpg,.jpeg" onchange="handleFileSelect(this)" style="display:none;">
                        
                        <div class="file-upload-zone" id="fileUploadZone" onclick="document.getElementById('file_surat').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h3><?= $s['file_surat'] ? 'Klik untuk ganti file' : 'Klik untuk upload file' ?></h3>
                            <p>PDF/PNG/JPG - Maksimal 5MB</p>
                        </div>
                        
                        <div class="file-preview" id="filePreview">
                            <i class="fas fa-check-circle"></i>
                            <div class="file-name" id="fileName"></div>
                            <div class="file-size" id="fileSize"></div>
                        </div>
                        <div class="help-text" style="margin-top:8px; font-size:12px; color:var(--text-muted);">
                            <i class="fas fa-info-circle"></i> 
                            <?=$s['file_surat'] ? '<strong>Upload file baru untuk mengganti. Kosongkan jika tidak ingin mengganti.</strong>' : ''?>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label><i class="fas fa-comment-alt"></i> Keterangan</label>
                        <div class="input-wrapper">
                            <textarea name="keterangan" class="form-control" style="min-height:80px;"><?=htmlspecialchars($s['keterangan'])?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> Batal</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save"></i> Update Surat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function handleFileSelect(input) {
        const file = input.files[0];
        const zone = document.getElementById('fileUploadZone');
        const preview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        if (file) {
            zone.classList.add('active');
            preview.classList.add('show');
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
            
            // Ubah tampilan text saja tanpa menghapus elemen
            zone.querySelector('h3').textContent = "File dipilih: " + file.name;
            zone.querySelector('i').className = "fas fa-check";
            zone.querySelector('i').style.color = "var(--success)";
            zone.querySelector('p').style.display = "none";
        }
    }
    
    // Form validation
    document.getElementById('formEdit')?.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        submitBtn.disabled = true;
    });
    
    // Auto-dismiss alerts
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-20px)';
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);
    </script>
</body>
</html>