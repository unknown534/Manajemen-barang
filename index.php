<?php
require_once 'function.php';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if (!isset($system)) {
    // $system = new System(); 
}

$kategori_data = $system->getKategori($page);
$is_admin = $system->isAdmin();
// Ambil username untuk ditampilkan
$current_username = $is_admin ? $_SESSION['username'] : '';

$total_pages = $kategori_data['total_pages'];
$current_page = $page;

$start_page = max(1, $current_page - 2);
$end_page = min($total_pages, $start_page + 4);
if ($end_page - $start_page < 4) {
    $start_page = max(1, $end_page - 4);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Perabot</title>
    
    <link href="bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-icons-1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-box-seam-fill"></i> Manajemen Perabot
            </a>
            <div class="d-flex align-items-center">
                <?php if ($is_admin): ?>
                    <span class="badge bg-light text-primary me-2 px-3 py-2 rounded-pill">
                        <i class="bi bi-person-fill me-1"></i> <?= htmlspecialchars($current_username) ?>
                    </span>
                    <button onclick="logout()" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                <?php else: ?>
                    <button onclick="showLogin()" class="btn btn-light btn-sm fw-bold text-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container pb-5">

        <?php if ($is_admin): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0 text-primary"><i class="bi bi-lightning-charge"></i> Tambah Barang Cepat</h5>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3">
                        <select id="quick-kategori" class="form-select">
                            <option value="">Pilih Kategori</option>
                            <?php 
                            $all_kategori = $system->getAllKategori();
                            foreach ($all_kategori as $kategori): ?>
                                <option value="<?= $kategori['id'] ?>"><?= htmlspecialchars($kategori['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="quick-namabarang" class="form-control" placeholder="Nama Barang">
                    </div>
                    <div class="col-md-2">
                        <input type="number" id="quick-jumlah" class="form-control" placeholder="Jumlah">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button onclick="addQuickItem()" class="btn btn-primary flex-grow-1"><i class="bi bi-plus-lg"></i> Tambah</button>
                        <button onclick="showAddKategori()" class="btn btn-outline-secondary" title="Tambah Kategori Baru"><i class="bi bi-folder-plus"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4 border-0 bg-transparent">
            <div class="input-group input-group-lg shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="search-input" class="form-control border-start-0" placeholder="Cari barang atau kategori..." onkeyup="liveSearch()">
            </div>
            <div id="search-loading-overlay" class="search-loading-container ms-2">
                <div class="small-loader"></div>
                <span class="text-muted fst-italic">Mencari...</span> 
            </div>
        </div>

        <div class="d-flex justify-content-center align-items-center position-relative mb-4">
            
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination mb-0">
                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=1" aria-label="First">&laquo;&laquo;</a>
                    </li>
                    <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $current_page - 1 ?>" aria-label="Previous">&laquo;</a>
                    </li>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $current_page + 1 ?>" aria-label="Next">&raquo;</a>
                    </li>
                    <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $total_pages ?>" aria-label="Last">&raquo;&raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

            <?php if ($is_admin): ?>
            <div class="position-absolute end-0">
                <button onclick="showLogs()" class="btn btn-outline-info rounded-circle" title="Lihat Riwayat Perubahan">
                    <i class="bi bi-info-lg fw-bold"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div id="search-results"></div>
        
        <div id="main-content">
            <?php if (empty($kategori_data['kategori'])): ?>
                <div class="alert alert-info text-center py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p class="mb-0">Tidak ada kategori yang ditemukan.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($kategori_data['kategori'] as $kategori): ?>
                        <div class="col-12 mb-4" id="kategori-<?= $kategori['id'] ?>">
                            <div class="card shadow-sm h-100">
                                <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                                    <h5 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($kategori['nama']) ?></h5>
                                    <?php if ($is_admin): ?>
                                    <div class="position-relative">
                                        <button class="btn btn-sm btn-outline-secondary text-dark rounded-circle btn-menu" onclick="toggleMenu(<?= $kategori['id'] ?>)">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        
                                        <div class="dropdown-menu-custom" id="menu-<?= $kategori['id'] ?>">
                                            <button onclick="showAddItemModal(<?= $kategori['id'] ?>, '<?= htmlspecialchars($kategori['nama']) ?>')" class="text-success">
                                                <i class="bi bi-plus-circle me-2"></i>Tambah Isi
                                            </button>
                                            <button onclick="showEditKategori(<?= $kategori['id'] ?>)" class="text-warning">
                                                <i class="bi bi-pencil me-2"></i>Ubah Nama
                                            </button>
                                            <button onclick="deleteKategori(<?= $kategori['id'] ?>)" class="text-danger">
                                                <i class="bi bi-trash me-2"></i>Hapus Kategori
                                            </button>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-body p-0">
                                    <div class="items-container" id="items-<?= $kategori['id'] ?>">
                                        <?php 
                                        $items = $system->getPerabotByKategori($kategori['id']);
                                        if (empty($items)): ?>
                                            <div class="text-center py-4 text-muted small">
                                                <i class="bi bi-slash-circle mb-2 d-block"></i> Tidak ada barang
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover table-striped mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 50px;" class="text-center">No</th>
                                                            <th>Nama Barang</th>
                                                            <th style="width: 100px;">Jumlah</th>
                                                            <?php if ($is_admin): ?><th style="width: 150px;" class="text-end">Aksi</th><?php endif; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($items as $index => $item): ?>
                                                            <tr id="item-<?= $item['id'] ?>">
                                                                <td class="text-center"><?= $index + 1 ?></td>
                                                                <td class="item-name fw-medium"><?= htmlspecialchars($item['namabarang']) ?></td>
                                                                <td class="item-quantity">
                                                                    <span class="fw-bold fs-5"><?= htmlspecialchars($item['jumlah']) ?></span>
                                                                </td>
                                                                <?php if ($is_admin): ?>
                                                                <td class="item-actions text-end">
                                                                    <button onclick="editItem(<?= $item['id'] ?>)" class="btn btn-sm btn-outline-warning me-1">
                                                                        <i class="bi bi-pencil-square"></i>
                                                                    </button>
                                                                    <button onclick="deleteItem(<?= $item['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                                                        <i class="bi bi-trash"></i>
                                                                    </button>
                                                                </td>
                                                                <?php endif; ?>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=1">&laquo;&laquo;</a>
                </li>
                <li class="page-item <?= ($current_page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page - 1 ?>">&laquo;</a>
                </li>
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $current_page + 1 ?>">&raquo;</a>
                </li>
                <li class="page-item <?= ($current_page >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $total_pages ?>">&raquo;&raquo;</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

    <div id="modal-overlay" onclick="hideModals()"></div>
    
    <div id="login-modal" class="custom-modal">
        <div class="custom-modal-dialog">
            <div class="custom-modal-content border-0 overflow-hidden">
                <div class="custom-modal-header bg-primary text-white">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-shield-lock-fill me-2"></i>Login Admin</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="hideModals()"></button>
                </div>
                <div class="custom-modal-body p-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                            <i class="bi bi-person-lock text-primary" style="font-size: 40px;"></i>
                        </div>
                        <p class="text-muted mt-2 small mb-0">Masukkan kredensial anda</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark">Username</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" id="login-username" class="form-control border-start-0 ps-0" placeholder="Masukan username..." autocomplete="off">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold text-dark">Password</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" id="login-password" class="form-control border-start-0 ps-0" placeholder="Masukan password...">
                        </div>
                    </div>
                </div>
                <div class="custom-modal-footer bg-light border-top-0 p-3">
                    <div class="d-flex w-100 gap-2">
                        <button onclick="hideModals()" class="btn btn-outline-secondary flex-fill py-2">Batal</button>
                        <button onclick="login()" class="btn btn-primary flex-fill py-2 fw-bold shadow-sm">Login</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="kategori-modal" class="custom-modal">
        <div class="custom-modal-dialog">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-info text-white">
                    <h5 class="mb-0" id="kategori-modal-title">Tambah Kategori</h5>
                    <button type="button" class="btn-close" onclick="hideModals()"></button>
                </div>
                <div class="custom-modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" id="kategori-nama" class="form-control" placeholder="Nama Kategori">
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button onclick="hideModals()" class="btn btn-secondary">Batal</button>
                    <button onclick="saveKategori()" class="btn btn-info text-white" id="kategori-save-btn">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <div id="item-modal" class="custom-modal">
        <div class="custom-modal-dialog">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-success text-white">
                    <h5 class="mb-0" id="item-modal-title">Tambah Barang Baru</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="hideModals()"></button>
                </div>
                <div class="custom-modal-body">
                    <div class="mb-3">
                        <label for="item-namabarang" class="form-label">Nama Barang:</label>
                        <input type="text" id="item-namabarang" class="form-control" placeholder="Masukkan nama barang">
                    </div>
                    <div class="mb-3">
                        <label for="item-jumlah" class="form-label">Jumlah:</label>
                        <input type="number" id="item-jumlah" class="form-control" placeholder="Masukkan jumlah">
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button onclick="hideModals()" class="btn btn-secondary">Batal</button>
                    <button onclick="saveNewItem()" class="btn btn-success" id="item-save-btn">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <div id="log-modal" class="custom-modal">
        <div class="custom-modal-dialog modal-lg">
            <div class="custom-modal-content">
                <div class="custom-modal-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Perubahan</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="hideModals()"></button>
                </div>
                <div class="custom-modal-body p-0">
                    <div id="log-container" class="p-4" style="max-height: 500px; overflow-y: auto;">
                        <div class="text-center py-3">
                            <div class="spinner-border text-secondary" role="status"></div>
                        </div>
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button onclick="downloadLogs()" class="btn btn-sm btn-light" title="Download log">
                        <i class="bi bi-download"></i>
                    </button>
                    <button onclick="hideModals()" class="btn btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <script src="bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>
