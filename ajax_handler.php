<?php
require_once 'function.php';

/* ===============================
   DOWNLOAD LOGS (GET, NON-JSON)
   =============================== */
if (isset($_GET['action']) && $_GET['action'] === 'download_logs') {

    if (!$system->isAdmin()) {
        http_response_code(403);
        exit('Akses ditolak');
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="logs.txt"');

    $logs = $system->getLogs(); 

    if (empty($logs)) {
        echo "Belum ada log.\n";
        exit;
    }

    foreach ($logs as $log) {
        // Format Log dalam teks: [Waktu] [User] AKSI | Tabel...
        echo "[" . $log['formatted_time'] . "] ";
        echo "[" . ($log['username'] ?? 'System') . "] ";
        echo strtoupper($log['aksi']) . " | ";
        echo $log['tabel'] . " | ";
        echo $log['kolom'] . PHP_EOL;
        echo $log['keterangan'] . PHP_EOL;
        echo str_repeat('-', 50) . PHP_EOL;
    }
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $response = ['status' => 'error', 'message' => 'Aksi tidak valid'];
    
    switch ($action) {
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($system->login($username, $password)) {
                $response = ['status' => 'success'];
            } else {
                $response = ['status' => 'error', 'message' => 'Username atau Password salah'];
            }
            break;
            
        case 'logout':
            if ($system->logout()) {
                $response = ['status' => 'success'];
            }
            break;
            
        case 'search':
            $keyword = $_POST['keyword'] ?? '';
            $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
            $results = $system->searchAll($keyword, $page);
            $response = ['status' => 'success', 'data' => $results];
            break;
            
        case 'add_kategori':
            if (!$system->isAdmin()) { $response = ['status' => 'error', 'message' => 'Akses ditolak']; break; }
            $nama = $_POST['nama'] ?? '';
            $result = $system->addKategori($nama);
            $response = ($result === 'success') ? ['status' => 'success'] : ['status' => 'error', 'message' => $result];
            break;
            
        case 'edit_kategori':
            if (!$system->isAdmin()) { $response = ['status' => 'error', 'message' => 'Akses ditolak']; break; }
            $id = $_POST['id'] ?? 0;
            $nama = $_POST['nama'] ?? '';
            $result = $system->updateKategori($id, $nama);
            $response = ($result === 'success') ? ['status' => 'success'] : ['status' => 'error', 'message' => $result];
            break;
            
        case 'delete_kategori':
            if (!$system->isAdmin()) { $response = ['status' => 'error', 'message' => 'Akses ditolak']; break; }
            $id = $_POST['id'] ?? 0;
            $result = $system->deleteKategori($id);
            $response = ($result === 'success') ? ['status' => 'success'] : ['status' => 'error', 'message' => $result];
            break;
            
        case 'add_item':
            if (!$system->isAdmin()) { $response = ['status' => 'error', 'message' => 'Akses ditolak']; break; }
            $id_kategori = $_POST['id_kategori'] ?? 0;
            $namabarang = $_POST['namabarang'] ?? '';
            $jumlah = $_POST['jumlah'] ?? '';
            $result = $system->addPerabot($id_kategori, $namabarang, $jumlah);
            $response = ($result === 'success') ? ['status' => 'success'] : ['status' => 'error', 'message' => $result];
            break;
            
        case 'edit_item':
            if (!$system->isAdmin()) { $response = ['status' => 'error', 'message' => 'Akses ditolak']; break; }
            $id = $_POST['id'] ?? 0;
            $namabarang = $_POST['namabarang'] ?? '';
            $jumlah = $_POST['jumlah'] ?? '';
            $result = $system->updatePerabot($id, $namabarang, $jumlah);
            $response = ($result === 'success') ? ['status' => 'success'] : ['status' => 'error', 'message' => $result];
            break;
            
        case 'delete_item':
            if (!$system->isAdmin()) { $response = ['status' => 'error', 'message' => 'Akses ditolak']; break; }
            $id = $_POST['id'] ?? 0;
            $result = $system->deletePerabot($id);
            $response = ($result === 'success') ? ['status' => 'success'] : ['status' => 'error', 'message' => $result];
            break;
            
        case 'get_item':
            $id = $_POST['id'] ?? 0;
            $item = $system->getPerabotById($id);
            $response = $item ? ['status' => 'success', 'data' => $item] : ['status' => 'error', 'message' => 'Item tidak ditemukan'];
            break;
            
        case 'get_kategori':
            $id = $_POST['id'] ?? 0;
            $kategori = $system->getKategoriById($id);
            $response = $kategori ? ['status' => 'success', 'data' => $kategori] : ['status' => 'error', 'message' => 'Kategori tidak ditemukan'];
            break;
            
        case 'get_logs':
            if (!$system->isAdmin()) { 
                $response = ['status' => 'error', 'message' => 'Akses ditolak']; 
                break; 
            }
            
            try {
                $logs = $system->getLogs();
                $response = ['status' => 'success', 'data' => $logs];
            } catch (Exception $e) {
                $response = ['status' => 'error', 'message' => 'Gagal mengambil logs: ' . $e->getMessage()];
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>
