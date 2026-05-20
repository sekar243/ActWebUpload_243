<?php
header('Content-Type: application/json; charset=utf-8');

$target_dir = "uploads/";
$response = ['success' => false, 'message' => ''];

// Ambil data dari request
$data = json_decode(file_get_contents('php://input'), true);
$filename = $data['filename'] ?? '';

if (empty($filename)) {
    $response['message'] = 'Nama file tidak ditemukan';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Amankan filename
$filename = basename($filename);
$file_path = $target_dir . $filename;

// Cek apakah file ada
if (file_exists($file_path)) {
    // Hapus file
    if (unlink($file_path)) {
        $response['success'] = true;
        $response['message'] = 'File berhasil dihapus';
    } else {
        $response['message'] = 'Gagal menghapus file. Cek permission folder.';
    }
} else {
    $response['message'] = 'File tidak ditemukan';
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>