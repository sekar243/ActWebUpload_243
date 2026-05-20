<?php
// Aktifkan error reporting untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set header encoding ke UTF-8
header('Content-Type: application/json; charset=utf-8');

$target_dir = "uploads/";

// Buat folder uploads jika belum ada
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// Cek apakah folder bisa ditulisi
if (!is_writable($target_dir)) {
    chmod($target_dir, 0777);
}

// Fungsi untuk membersihkan nama file dari karakter aneh
function sanitize_filename($filename) {
    // Ambil nama file dan ekstensi
    $info = pathinfo($filename);
    $name = $info['filename'];
    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
    
    // Hapus karakter aneh (hanya huruf, angka, underscore, strip)
    $name = preg_replace('/[^a-zA-Z0-9_\-]/u', '_', $name);
    // Ganti multiple underscore jadi satu
    $name = preg_replace('/_+/', '_', $name);
    // Hapus underscore di awal/akhir
    $name = trim($name, '_');
    // Jika kosong, beri nama default
    if (empty($name)) {
        $name = 'gambar';
    }
    
    return $name . $ext;
}

// ============================================
// 1. FUNGSI UPLOAD FILE
// ============================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["fileToUpload"])) {
    
    $original_name = basename($_FILES["fileToUpload"]["name"]);
    $clean_name = sanitize_filename($original_name);
    $target_file = $target_dir . $clean_name;
    $uploadOk = 1;
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $message = "";
    $success = false;
    
    // Cek apakah ada file yang diupload
    if ($_FILES["fileToUpload"]["error"] != UPLOAD_ERR_OK) {
        $uploadOk = 0;
        switch ($_FILES["fileToUpload"]["error"]) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "File terlalu besar (maks " . ini_get('upload_max_filesize') . ")";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "File terlalu besar";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "File hanya terupload sebagian";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "Tidak ada file yang dipilih";
                break;
            default:
                $message = "Error code: " . $_FILES["fileToUpload"]["error"];
        }
    }
    
    // PERIKSA APAKAH BERKAS SEBENARNYA ADALAH GAMBAR
    if ($uploadOk == 1) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $message = "Berkas BUKAN gambar.";
            $uploadOk = 0;
        }
    }
    
    // PERIKSA UKURAN BERKAS (maks 5MB)
    if ($uploadOk == 1 && $_FILES["fileToUpload"]["size"] > 5000000) {
        $message = "Maaf, berkas Anda terlalu besar. Maksimal 5MB.";
        $uploadOk = 0;
    }
    
    // HANYA IZINKAN FORMAT GAMBAR
    if ($uploadOk == 1 && !in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        $message = "Maaf, hanya berkas JPG, JPEG, PNG & GIF yang diperbolehkan.";
        $uploadOk = 0;
    }
    
    // PERIKSA APAKAH BERKAS SUDAH ADA
    if ($uploadOk == 1 && file_exists($target_file)) {
        $file_info = pathinfo($clean_name);
        $new_filename = $file_info['filename'] . '_' . time() . '.' . $file_info['extension'];
        $target_file = $target_dir . $new_filename;
        $clean_name = $new_filename;
    }
    
    // PROSES UPLOAD
    if ($uploadOk == 1) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $message = "Berkas " . htmlspecialchars($clean_name) . " telah diunggah.";
            $success = true;
        } else {
            $message = "Maaf, terjadi kesalahan saat mengunggah berkas Anda.";
            if (!is_writable($target_dir)) {
                $message .= " Folder uploads/ tidak dapat ditulisi!";
            }
        }
    }
    
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// 2. FUNGSI AMBIL DAFTAR FILE (GET FILES)
// ============================================
if (isset($_GET['action']) && $_GET['action'] == 'get_files') {
    header('Content-Type: application/json; charset=utf-8');
    $files = [];
    
    if (is_dir($target_dir)) {
        $scan_files = scandir($target_dir);
        
        foreach ($scan_files as $file) {
            if ($file != '.' && $file != '..') {
                $file_path = $target_dir . $file;
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                // Hanya tampilkan file gambar
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $files[] = [
                        'name' => $file,
                        'size' => filesize($file_path),
                        'extension' => $extension
                    ];
                }
            }
        }
        
        // Urutkan dari yang terbaru
        usort($files, function($a, $b) use ($target_dir) {
            return filemtime($target_dir . $b['name']) - filemtime($target_dir . $a['name']);
        });
    }
    
    echo json_encode($files, JSON_UNESCAPED_UNICODE);
    exit;
}

// Jika tidak ada aksi yang sesuai
http_response_code(404);
echo json_encode(['error' => 'Aksi tidak ditemukan'], JSON_UNESCAPED_UNICODE);
?>