<?php
$target_dir = "uploads/";

// Ambil nama file dari parameter
$filename = basename($_GET['file'] ?? '');
$file_path = $target_dir . $filename;

// Cek apakah file ada
if (file_exists($file_path)) {
    // Cek ekstensi file (hanya gambar)
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($extension, $allowed)) {
        // Set header untuk download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Baca file dan kirim ke browser
        readfile($file_path);
        exit;
    } else {
        echo "Format file tidak didukung untuk download.";
    }
} else {
    echo "File tidak ditemukan.";
}
?>