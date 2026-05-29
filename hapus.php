<?php
session_start();
require_once 'config.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM surat WHERE id = $id");
    
    // Simpan pesan sukses ke session
    $_SESSION['flash'] = ['msg' => '✅ Surat berhasil dihapus!', 'type' => 'success'];
}

// Kembali ke halaman sebelumnya (biasanya index.php)
$referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header("Location: $referer");
exit;
?>