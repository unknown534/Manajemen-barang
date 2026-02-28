<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

class Database {
    private $conn;

    public function __construct() {
        // Pastikan file .dbconfig.php ada dan benar
        $config = require __DIR__ . '/.dbconfig.php';

        $this->conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}

class PerabotSystem {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    // --- LOGIN SYSTEM (UPDATED) ---
    public function login($username, $password) {
        $conn = $this->db->getConnection();
        
        // Cek username dan password (MENTAH/PLAIN TEXT sesuai request)
        $sql = "SELECT * FROM users WHERE username = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Set Session
            $_SESSION['admin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username']; // Simpan username untuk navbar & logs
            
            return true;
        }
        return false;
    }

    public function logout() {
        session_destroy();
        return true;
    }

    public function isAdmin() {
        return isset($_SESSION['admin']) && $_SESSION['admin'] === true;
    }

    public function getCurrentUsername() {
        return isset($_SESSION['username']) ? $_SESSION['username'] : 'System';
    }

    // --- LOGGING SYSTEM (UPDATED) ---
    private function saveLog($aksi, $tabel, $kolom, $keterangan) {
        $conn = $this->db->getConnection();
        $waktu = date('Y-m-d H:i:s');
        
        // Ambil username yang sedang login
        $username = $this->getCurrentUsername();

        // Query update: insert username juga
        $sql = "INSERT INTO tblog (waktu, username, aksi, tabel, kolom, keterangan)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $waktu, $username, $aksi, $tabel, $kolom, $keterangan);
        $stmt->execute();
    }

    public function getLogs() {
        $conn = $this->db->getConnection();

        // Ambil kolom username juga
        $sql = "SELECT id,
            DATE_FORMAT(waktu, '%H:%i %d/%m/%Y') AS formatted_time,
            username, aksi, tabel, kolom, keterangan
        FROM tblog
        ORDER BY waktu DESC
        LIMIT 50";

        $result = $conn->query($sql);
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        return $logs;
    }
    
    // --- EXISTING FUNCTIONS (TIDAK BERUBAH) ---
    
    public function getKategori($page = 1, $per_page = 3) {
        $offset = ($page - 1) * $per_page;
        $conn = $this->db->getConnection();
        
        $sql = "SELECT * FROM kategori ORDER BY nama LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $offset, $per_page);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $kategori = [];
        while ($row = $result->fetch_assoc()) {
            $kategori[] = $row;
        }
        
        $total_sql = "SELECT COUNT(*) as total FROM kategori";
        $total_result = $conn->query($total_sql);
        $total_row = $total_result->fetch_assoc();
        $total_pages = ceil($total_row['total'] / $per_page);
        
        return [
            'kategori' => $kategori,
            'total_pages' => $total_pages,
            'current_page' => $page
        ];
    }

    public function getAllKategori() {
        $conn = $this->db->getConnection();
        $result = $conn->query("SELECT * FROM kategori ORDER BY nama");
        $kategori = [];
        while ($row = $result->fetch_assoc()) {
            $kategori[] = $row;
        }
        return $kategori;
    }

    public function getPerabotByKategori($id_kategori) {
        $conn = $this->db->getConnection();
        $sql = "SELECT p.*, k.nama as nama_kategori 
                FROM tbperabot p 
                JOIN kategori k ON p.id_kategori = k.id 
                WHERE p.id_kategori = ? 
                ORDER BY p.namabarang";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_kategori);
        $stmt->execute();
        $result = $stmt->get_result();
        $perabot = [];
        while ($row = $result->fetch_assoc()) {
            $perabot[] = $row;
        }
        return $perabot;
    }

    // --- FUNGSI SEARCH ---
    public function searchAll($keyword, $page = 1, $limit = 3) {
        $conn = $this->db->getConnection();
        $offset = ($page - 1) * $limit;
        $search_term = "%$keyword%";

        $sql_cat_ids = "SELECT DISTINCT k.id 
                        FROM kategori k 
                        LEFT JOIN tbperabot p ON k.id = p.id_kategori 
                        WHERE k.nama LIKE ? OR p.namabarang LIKE ? 
                        ORDER BY k.nama ASC 
                        LIMIT ?, ?";
        
        $stmt_ids = $conn->prepare($sql_cat_ids);
        $stmt_ids->bind_param("ssii", $search_term, $search_term, $offset, $limit);
        $stmt_ids->execute();
        $result_ids = $stmt_ids->get_result();
        
        $cat_ids = [];
        while ($row = $result_ids->fetch_assoc()) {
            $cat_ids[] = $row['id'];
        }

        $sql_count = "SELECT COUNT(DISTINCT k.id) as total 
                      FROM kategori k 
                      LEFT JOIN tbperabot p ON k.id = p.id_kategori 
                      WHERE k.nama LIKE ? OR p.namabarang LIKE ?";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->bind_param("ss", $search_term, $search_term);
        $stmt_count->execute();
        $total_categories = $stmt_count->get_result()->fetch_assoc()['total'];
        $total_pages = ceil($total_categories / $limit);

        $items = [];
        $empty_categories = [];

        if (!empty($cat_ids)) {
            $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
            $types = str_repeat('i', count($cat_ids));
            $types .= "ss";

            $sql_items = "SELECT p.*, k.nama as nama_kategori, k.id as cat_id_check
                          FROM tbperabot p 
                          JOIN kategori k ON p.id_kategori = k.id 
                          WHERE k.id IN ($placeholders) 
                          AND (p.namabarang LIKE ? OR k.nama LIKE ?)
                          ORDER BY k.nama, p.namabarang";
            
            $stmt_items = $conn->prepare($sql_items);
            $params = array_merge($cat_ids, [$search_term, $search_term]);
            $stmt_items->bind_param($types, ...$params);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            
            while ($row = $result_items->fetch_assoc()) {
                $items[] = $row;
            }

            $types_cat = str_repeat('i', count($cat_ids));
            $sql_cat_details = "SELECT * FROM kategori WHERE id IN ($placeholders) ORDER BY nama";
            $stmt_cat = $conn->prepare($sql_cat_details);
            $stmt_cat->bind_param($types_cat, ...$cat_ids);
            $stmt_cat->execute();
            $res_cat = $stmt_cat->get_result();

            while($cat = $res_cat->fetch_assoc()) {
                $found = false;
                foreach($items as $item) {
                    if($item['id_kategori'] == $cat['id']) {
                        $found = true;
                        break;
                    }
                }
                if(!$found) {
                    $empty_categories[] = $cat;
                }
            }
        }
        
        return [
            'items' => $items,
            'empty_categories' => $empty_categories,
            'total_pages' => $total_pages,
            'current_page' => (int)$page,
            'total_items' => $total_categories
        ];
    }

    // --- CRUD METHODS WITH LOGGING ---

    public function addKategori($nama) {
        $conn = $this->db->getConnection();
        $check_sql = "SELECT id FROM kategori WHERE nama = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $nama);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) return "Kategori sudah ada";
        
        $sql = "INSERT INTO kategori (nama) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nama);
        
        if ($stmt->execute()) {
            $this->saveLog("INSERT", "kategori", "nama", "Menambah kategori baru: " . $nama);
            return "success";
        }
        return "Error: " . $stmt->error;
    }

    public function updateKategori($id, $nama) {
        $conn = $this->db->getConnection();
        
        $old_data = $this->getKategoriById($id);
        $old_name = $old_data['nama'] ?? 'Unknown';

        $check_sql = "SELECT id FROM kategori WHERE nama = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $nama, $id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) return "Kategori sudah ada";
        
        $sql = "UPDATE kategori SET nama = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $nama, $id);
        
        if ($stmt->execute()) {
            $this->saveLog("UPDATE", "kategori", "nama", "Mengubah nama kategori dari '$old_name' menjadi '$nama'");
            return "success";
        }
        return "Error: " . $stmt->error;
    }

    public function deleteKategori($id) {
        $conn = $this->db->getConnection();
        
        $data = $this->getKategoriById($id);
        $nama_kategori = $data['nama'] ?? 'Unknown';

        $conn->begin_transaction();
        try {
            $stmt1 = $conn->prepare("DELETE FROM tbperabot WHERE id_kategori = ?");
            $stmt1->bind_param("i", $id);
            $stmt1->execute();
            
            $stmt2 = $conn->prepare("DELETE FROM kategori WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            
            $conn->commit();
            
            $this->saveLog("DELETE", "kategori", "nama", "Menghapus kategori '$nama_kategori' beserta isinya");
            return "success";
        } catch (Exception $e) {
            $conn->rollback();
            return "Error: " . $e->getMessage();
        }
    }

    public function addPerabot($id_kategori, $namabarang, $jumlah) {
        $conn = $this->db->getConnection();
        $check_sql = "SELECT id FROM tbperabot WHERE id_kategori = ? AND namabarang = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("is", $id_kategori, $namabarang);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) return "Barang sudah ada";
        
        $sql = "INSERT INTO tbperabot (id_kategori, namabarang, jumlah) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $id_kategori, $namabarang, $jumlah);
        
        if ($stmt->execute()) {
            $this->saveLog("INSERT", "tbperabot", "nama_barang", "Menambah barang baru: $namabarang ($jumlah)");
            return "success";
        }
        return "Error: " . $stmt->error;
    }

    public function updatePerabot($id, $namabarang, $jumlah) {
        $conn = $this->db->getConnection();
        
        $old_data = $this->getPerabotById($id);
        $old_name = $old_data['namabarang'] ?? 'Unknown';
        
        $check_sql = "SELECT id FROM tbperabot WHERE namabarang = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $namabarang, $id);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) return "Barang sudah ada";
        
        $sql = "UPDATE tbperabot SET namabarang = ?, jumlah = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $namabarang, $jumlah, $id);
        
        if ($stmt->execute()) {
            $desc = "Mengupdate barang '$old_name'";
            if ($old_name != $namabarang) $desc .= " nama menjadi '$namabarang'";
            $desc .= " jumlah menjadi '$jumlah'";
            
            $this->saveLog("UPDATE", "tbperabot", "jumlah/nama", $desc);
            return "success";
        }
        return "Error: " . $stmt->error;
    }

    public function deletePerabot($id) {
        $conn = $this->db->getConnection();
        
        $data = $this->getPerabotById($id);
        $nama_barang = $data['namabarang'] ?? 'Unknown';

        $sql = "DELETE FROM tbperabot WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $this->saveLog("DELETE", "tbperabot", "nama_barang", "Menghapus barang '$nama_barang'");
            return "success";
        }
        return "Error: " . $stmt->error;
    }

    public function getPerabotById($id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM tbperabot WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getKategoriById($id) {
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM kategori WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

$system = new PerabotSystem();
?>
