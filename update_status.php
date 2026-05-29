<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $id = (int)$_POST['id'];
    $status = sanitize($_POST['status']);
    
    // Validasi status
    $status_valid = ['diterima', 'diproses', 'selesai', 'ditolak'];
    if (in_array($status, $status_valid)) {
        $stmt = $conn->prepare("UPDATE surat SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        
        if ($stmt->execute()) {
            flash("✅ Status berhasil diubah menjadi " . ucfirst($status), "success");
        } else {
            flash("❌ Gagal mengubah status", "danger");
        }
    } else {
        flash("❌ Status tidak valid", "danger");
    }
}

// Kembali ke halaman sebelumnya
header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
exit;
?>