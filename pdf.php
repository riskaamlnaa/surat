<?php
session_start();
require_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$res = $conn->query("SELECT * FROM surat WHERE id = $id");
if ($res->num_rows === 0) { 
    $_SESSION['flash'] = ['msg' => 'Surat tidak ditemukan', 'type' => 'danger'];
    header("Location: index.php"); 
    exit; 
}
$s = $res->fetch_assoc();

// Load kop surat
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
$tgl_kirim = date('d', strtotime($s['tanggal_kirim'])) . ' ' . $bulan_id[date('m', strtotime($s['tanggal_kirim'])) - 1] . ' ' . date('Y', strtotime($s['tanggal_kirim']));

$has_file = $s['file_surat'] && file_exists($s['file_surat']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Surat <?= htmlspecialchars($s['no_surat']) ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 20px; }
            .page { box-shadow: none; margin: 0; }
        }
        body { 
            font-family: "Times New Roman", Times, serif; 
            font-size: 12pt; 
            line-height: 1.5; 
            background: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        .page {
            background: white;
            padding: 40px;
            max-width: 210mm;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .kop-surat { 
            text-align: center; 
            border-bottom: 3px double #000; 
            padding-bottom: 15px; 
            margin-bottom: 30px; 
        }
        .kop-surat h2 { margin: 0; font-size: 16pt; text-transform: uppercase; letter-spacing: 1px; }
        .kop-surat h1 { margin: 8px 0 5px; font-size: 18pt; font-weight: bold; }
        .kop-surat p { margin: 3px 0; font-size: 11pt; }
        
        .judul { 
            text-align: center; 
            margin: 30px 0 20px; 
            font-weight: bold;
            font-size: 14pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0;
        }
        table td { 
            padding: 10px; 
            vertical-align: top;
            border: 1px solid #000;
        }
        table td.label { 
            width: 180px; 
            font-weight: bold;
            background: #f9f9f9;
        }
        
        .button-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 5px;
            background: #0f2b4c;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        .btn:hover { background: #1a365d; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-warning:hover { background: #e0a800; }
    </style>
</head>
<body>
    <div class="button-container no-print">
        <a href="index.php" class="btn btn-warning">← Kembali</a>
        <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Cetak / Save PDF</button>
        <?php if($has_file): ?>
        <a href="<?= $s['file_surat'] ?>" download class="btn btn-success"><i class="fas fa-download"></i> Download File</a>
        <?php endif; ?>
    </div>
    
    <div class="page">
        <div class="kop-surat">
            <h2><?= htmlspecialchars($kop['pemda']) ?></h2>
            <h1><?= htmlspecialchars($kop['dinas']) ?></h1>
            <p><?= htmlspecialchars($kop['alamat']) ?></p>
            <p>Telp: <?= htmlspecialchars($kop['telp']) ?> | Email: <?= htmlspecialchars($kop['email']) ?></p>
        </div>

        <div class="judul">LEMBAR DATA SURAT</div>

        <table>
            <tr>
                <td class="label">Nomor Surat</td>
                <td><strong><?= htmlspecialchars($s['no_surat']) ?></strong></td>
            </tr>
            <tr>
                <td class="label">Jenis Surat</td>
                <td><?= ucfirst($s['jenis_surat']) ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Kirim</td>
                <td><?= $tgl_kirim ?></td>
            </tr>
            <tr>
                <td class="label">Pengirim / Tujuan</td>
                <td><?= htmlspecialchars($s['pengirim']) ?></td>
            </tr>
            <tr>
                <td class="label">Bidang</td>
                <td><?= htmlspecialchars($s['bidang']) ?></td>
            </tr>
            <tr>
                <td class="label">Perihal</td>
                <td><strong><?= htmlspecialchars($s['perihal']) ?></strong></td>
            </tr>
            <tr>
                <td class="label">Disposisi</td>
                <td><?= htmlspecialchars($s['disposisi']) ?: '-' ?></td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td><strong><?= strtoupper($s['status']) ?></strong></td>
            </tr>
            <tr>
                <td class="label">File Terlampir</td>
                <td>
                    <?php if($has_file): ?>
                        <a href="<?= $s['file_surat'] ?>" target="_blank">
                            <i class="fas fa-file"></i> <?= basename($s['file_surat']) ?>
                        </a>
                    <?php else: ?>
                        Tidak ada file
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="label">Keterangan</td>
                <td><?= htmlspecialchars($s['keterangan']) ?: '-' ?></td>
            </tr>
        </table>
    </div>
</body>
</html>