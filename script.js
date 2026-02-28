// Global variables
let editingItemId = null;
let editingKategoriId = null;
let currentKategoriId = null;
let searchRequestToken = 0;

// ==========================================
// HELPER FUNCTION: ENTER KEY & SUCCESS
// ==========================================

function setupEnterKey(inputId, callback) {
    const input = document.getElementById(inputId);
    if (input) {
        input.onkeypress = function(event) {
            if (event.key === "Enter") {
                event.preventDefault();
                callback();
            }
        };
    }
}

function handleSuccessAction() {
    hideModals(); 
    const searchInput = document.getElementById('search-input');
    
    if (searchInput && searchInput.value.trim() !== "") {
        const activeBtn = document.querySelector('.ajax-pagination .active');
        const currentPage = activeBtn ? parseInt(activeBtn.innerText) : 1;
        liveSearch(currentPage); 
    } else {
        location.reload(); 
    }
}

// ==========================================
// Modal Functions
// ==========================================
function showLogin() {
    document.getElementById('login-modal').style.display = 'block';
    document.getElementById('modal-overlay').style.display = 'block';

    setTimeout(function() {
        const input = document.getElementById('login-username');
        if(input) {
            input.focus();
            setupEnterKey('login-username', function(){ document.getElementById('login-password').focus() });
            setupEnterKey('login-password', login);
        }
    }, 100);
}

function showAddKategori() {
    document.getElementById('kategori-modal-title').textContent = 'Tambah Kategori';
    document.getElementById('kategori-nama').value = '';
    document.getElementById('kategori-save-btn').setAttribute('onclick', 'saveKategori()');
    editingKategoriId = null;
    document.getElementById('kategori-modal').style.display = 'block';
    document.getElementById('modal-overlay').style.display = 'block';

    setTimeout(function() {
        const input = document.getElementById('kategori-nama');
        if(input) {
            input.focus();
            input.select(); 
            setupEnterKey('kategori-nama', saveKategori);
        }
    }, 100);
}

function showEditKategori(id) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_kategori&id=${id}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('kategori-modal-title').textContent = 'Edit Kategori';
            document.getElementById('kategori-nama').value = data.data.nama;
            document.getElementById('kategori-save-btn').setAttribute('onclick', `updateKategori(${id})`);
            editingKategoriId = id;
            document.getElementById('kategori-modal').style.display = 'block';
            document.getElementById('modal-overlay').style.display = 'block';

            setTimeout(function() {
                const input = document.getElementById('kategori-nama');
                if(input) {
                    input.focus();
                    input.select(); 
                    setupEnterKey('kategori-nama', () => updateKategori(id));
                }
            }, 100);
        } else {
            alert(data.message);
        }
    });
}

function showAddItemModal(kategoriId, kategoriNama) {
    currentKategoriId = kategoriId;
    document.getElementById('item-modal-title').textContent = `Tambah Barang - ${kategoriNama}`;
    document.getElementById('item-namabarang').value = '';
    document.getElementById('item-jumlah').value = '';
    document.getElementById('item-save-btn').setAttribute('onclick', 'saveNewItem()');
    document.getElementById('item-modal').style.display = 'block';
    document.getElementById('modal-overlay').style.display = 'block';

    setTimeout(function() {
        const namaInput = document.getElementById('item-namabarang');
        const jumlahInput = document.getElementById('item-jumlah');
        
        if(namaInput) {
            namaInput.focus();
            namaInput.onkeypress = function(e) {
                if(e.key === "Enter") {
                    e.preventDefault();
                    if(jumlahInput) jumlahInput.focus();
                }
            };
        }

        if(jumlahInput) {
            setupEnterKey('item-jumlah', saveNewItem);
        }
    }, 100);
}

// FUNGSI UNTUK LOGS (UPDATED WITH USERNAME)
function showLogs() {
    document.getElementById('log-modal').style.display = 'block';
    document.getElementById('modal-overlay').style.display = 'block';
    const container = document.getElementById('log-container');
    
    container.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-secondary" role="status"></div></div>';

    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get_logs'
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.data.length === 0) {
                container.innerHTML = '<p class="text-center text-muted">Belum ada riwayat perubahan.</p>';
                return;
            }

            let html = '';
            data.data.forEach(log => {
                let badgeClass = 'bg-secondary';
                if (log.aksi === 'INSERT') badgeClass = 'bg-success';
                if (log.aksi === 'UPDATE') badgeClass = 'bg-warning text-dark';
                if (log.aksi === 'DELETE') badgeClass = 'bg-danger';

                // Menambahkan tampilan Username di log
                const usernameDisplay = log.username ? `<span class="badge bg-info text-dark me-2"><i class="bi bi-person-fill"></i> ${log.username}</span>` : '';

                html += `
                <div class="mb-4 pb-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            ${usernameDisplay}
                            <small class="fw-bold text-primary">${log.formatted_time}</small>
                        </div>
                    </div>
                    <p class="mb-2 text-dark">${log.keterangan}</p>
                    <div class="text-muted small" style="font-family: monospace;">
                        <span class="badge ${badgeClass} me-2">${log.aksi}</span> | ${log.tabel} | ${log.kolom}
                    </div>
                </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = `<p class="text-danger text-center">${data.message}</p>`;
        }
    })
    .catch(err => {
        container.innerHTML = `<p class="text-danger text-center">Terjadi kesalahan koneksi.</p>`;
    });
}

function downloadLogs() {
    window.location.href = 'ajax_handler.php?action=download_logs';
}

function hideModals() {
    document.getElementById('login-modal').style.display = 'none';
    document.getElementById('kategori-modal').style.display = 'none';
    document.getElementById('item-modal').style.display = 'none';
    document.getElementById('log-modal').style.display = 'none';
    document.getElementById('modal-overlay').style.display = 'none';
    currentKategoriId = null;
}

// ==========================================
// Auth Functions (UPDATED)
// ==========================================
function login() {
    const username = document.getElementById('login-username').value.trim();
    const password = document.getElementById('login-password').value;
    
    if(!username || !password) {
        alert("Mohon isi username dan password");
        return;
    }

    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=login&username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') location.reload();
        else alert(data.message);
    });
}

function logout() {
    if (confirm('Yakin ingin logout?')) {
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=logout'
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') location.reload();
        });
    }
}

// ==========================================
// Kategori Functions
// ==========================================
function saveKategori() {
    const nama = document.getElementById('kategori-nama').value.trim();
    if (!nama) { alert('Nama kategori harus diisi'); return; }
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_kategori&nama=${encodeURIComponent(nama)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') handleSuccessAction();
        else alert(data.message);
    });
}

function updateKategori(id) {
    const nama = document.getElementById('kategori-nama').value.trim();
    if (!nama) { alert('Nama kategori harus diisi'); return; }
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=edit_kategori&id=${id}&nama=${encodeURIComponent(nama)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') handleSuccessAction();
        else alert(data.message);
    });
}

function deleteKategori(id) {
    if (confirm('Hapus kategori ini beserta semua isinya?')) {
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_kategori&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') handleSuccessAction();
            else alert(data.message);
        });
    }
}

// ==========================================
// Item Functions
// ==========================================
function addQuickItem() {
    const id_kategori = document.getElementById('quick-kategori').value;
    const namabarang = document.getElementById('quick-namabarang').value.trim();
    const jumlah = document.getElementById('quick-jumlah').value.trim();
    if (!id_kategori || !namabarang || !jumlah) { alert('Semua field harus diisi'); return; }
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_item&id_kategori=${id_kategori}&namabarang=${encodeURIComponent(namabarang)}&jumlah=${encodeURIComponent(jumlah)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') handleSuccessAction();
        else alert(data.message);
    });
}

function saveNewItem() {
    const namabarang = document.getElementById('item-namabarang').value.trim();
    const jumlah = document.getElementById('item-jumlah').value.trim();
    if (!namabarang || !jumlah) { alert('Semua field harus diisi'); return; }
    if (!currentKategoriId) { alert('Kategori tidak valid'); return; }
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add_item&id_kategori=${currentKategoriId}&namabarang=${encodeURIComponent(namabarang)}&jumlah=${encodeURIComponent(jumlah)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') handleSuccessAction();
        else alert(data.message);
    });
}

function refreshItemRow(itemId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_item&id=${itemId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const item = data.data;
            const row = document.getElementById(`item-${itemId}`);
            
            // Cek apakah user adalah admin
            const is_admin = document.body.innerText.includes('Logout');
            const noUrut = row.cells[0].innerText; 

            row.innerHTML = `
                <td class="text-center">${noUrut}</td>
                <td class="item-name fw-medium">${item.namabarang}</td>
                <td class="item-quantity">
                    <span class="fw-bold fs-5">${item.jumlah}</span>
                </td>
                ${is_admin ? `
                <td class="item-actions text-end">
                    <button onclick="editItem(${item.id})" class="btn btn-sm btn-outline-warning me-1">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button onclick="deleteItem(${item.id})" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
                ` : ''}
            `;
            editingItemId = null;
        }
    });
}

function editItem(itemId) {
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get_item&id=${itemId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            const item = data.data;
            const row = document.getElementById(`item-${itemId}`);
            
            row.innerHTML = `
                <td class="text-center">${row.cells[0].innerText}</td>
                <td><input type="text" id="edit-name-${itemId}" value="${item.namabarang}" class="form-control form-control-sm"></td>
                <td><input type="number" id="edit-quantity-${itemId}" value="${item.jumlah}" class="form-control form-control-sm" style="width: 80px;"></td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <button onclick="saveItem(${itemId})" class="btn btn-success" title="Simpan"><i class="bi bi-check-lg"></i></button>
                        <button onclick="cancelEdit(${itemId})" class="btn btn-secondary" title="Batal"><i class="bi bi-x-lg"></i></button>
                    </div>
                </td>
            `;
            editingItemId = itemId;

            setTimeout(function() {
                const qtyInput = document.getElementById(`edit-quantity-${itemId}`);
                const nameInput = document.getElementById(`edit-name-${itemId}`);

                if(qtyInput) {
                    qtyInput.focus();
                    qtyInput.select();
                    qtyInput.onkeypress = function(e) {
                        if(e.key === "Enter") {
                            e.preventDefault();
                            saveItem(itemId);
                        }
                    };
                }

                if(nameInput) {
                    nameInput.onkeypress = function(e) {
                        if(e.key === "Enter") {
                            e.preventDefault();
                            saveItem(itemId);
                        }
                    };
                }

            }, 10);

        } else {
            alert(data.message);
        }
    });
}

function cancelEdit(itemId) {
    refreshItemRow(itemId);
}

function saveItem(itemId) {
    const namabarang = document.getElementById(`edit-name-${itemId}`).value.trim();
    const jumlah = document.getElementById(`edit-quantity-${itemId}`).value.trim();
    if (!namabarang || !jumlah) { alert('Semua field harus diisi'); return; }
    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=edit_item&id=${itemId}&namabarang=${encodeURIComponent(namabarang)}&jumlah=${encodeURIComponent(jumlah)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') refreshItemRow(itemId);
        else alert(data.message);
    });
}

function deleteItem(itemId) {
    if (confirm('Yakin ingin menghapus barang ini?')) {
        fetch('ajax_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_item&id=${itemId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') handleSuccessAction();
            else alert(data.message);
        });
    }
}

// ==========================================
// Loading Functions
// ==========================================
function showLoading() {
    document.getElementById('search-loading-overlay').style.display = 'flex';
    document.getElementById('main-content').classList.add('hide-main-content');
}

function hideLoading() {
    document.getElementById('search-loading-overlay').style.display = 'none';
    document.getElementById('main-content').classList.remove('hide-main-content');
}

// ==========================================
// SEARCH FUNCTIONS
// ==========================================

function liveSearch(page = 1) {
    const keyword = document.getElementById('search-input').value.trim();
    const searchResults = document.getElementById('search-results');
    const mainContent = document.getElementById('main-content');

    const myToken = ++searchRequestToken; 

    if (keyword.length === 0) {
        hideLoading();
        searchResults.innerHTML = '';
        mainContent.style.display = 'block';
        return;
    }

    mainContent.style.display = 'none';
    showLoading();

    fetch('ajax_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=search&keyword=${encodeURIComponent(keyword)}&page=${page}`
    })
    .then(response => response.json())
    .then(data => {
        if (myToken !== searchRequestToken) return;
        if (document.getElementById('search-input').value.trim() === '') return;

        if (data.status === 'success') {
            displaySearchResults(data.data, keyword);
        }
    })
    .finally(() => {
        if (myToken === searchRequestToken) {
            hideLoading();
        }
    });
}
  
function displaySearchResults(results, keyword) {
    const searchResults = document.getElementById('search-results');
    const is_admin = document.body.innerText.includes('Logout');
      
    if (results.items.length === 0 && results.empty_categories.length === 0) {
        searchResults.innerHTML = '<div class="alert alert-warning">Tidak ditemukan hasil untuk: <strong>' + keyword + '</strong></div>';
        return;
    }
      
    let html = '<div class="search-results-container">';
    html += `<h5 class="mb-4">Hasil Pencarian: "${keyword}" <small class="text-muted">(Halaman ${results.current_page} dari ${results.total_pages})</small></h5>`;
      
    const groupedResults = {};
    results.items.forEach(item => {
        if (!groupedResults[item.nama_kategori]) {
            groupedResults[item.nama_kategori] = [];
        }
        groupedResults[item.nama_kategori].push(item);
    });
      
    // Loop Kategori yang memiliki Item
    for (const [kategori, items] of Object.entries(groupedResults)) {
        const catId = items[0].id_kategori || 0;   
        const menuId = `search-menu-cat-${catId}`;
  
        html += `<div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark">${kategori}</h5>
                        ${is_admin ? `
                        <div class="position-relative">
                            <button class="btn btn-sm btn-light rounded-circle border btn-menu" onclick="toggleSearchMenuById('${menuId}')">
                                <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu-custom" id="${menuId}">
                                <button onclick="showAddItemModal(${catId}, '${kategori}')" class="text-success"><i class="bi bi-plus-circle me-2"></i>Tambah Isi</button>
                                <button onclick="showEditKategori(${catId})" class="text-warning"><i class="bi bi-pencil me-2"></i>Ubah Nama</button>
                                <button onclick="deleteKategori(${catId})" class="text-danger"><i class="bi bi-trash me-2"></i>Hapus Kategori</button>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    <div class="card-body p-0">
                    <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">No</th>
                                <th>Nama Barang</th>
                                <th style="width: 100px;">Jumlah</th>
                                ${is_admin ? '<th style="width: 150px;" class="text-end">Aksi</th>' : ''}
                            </tr>
                        </thead>
                        <tbody>`;
          
        items.forEach((item, index) => {
            const number = index + 1; 
            html += `<tr id="item-${item.id}"> 
                        <td class="text-center">${number}</td>
                        <td class="item-name fw-medium">${item.namabarang}</td>
                        <td class="item-quantity">
                             <span class="fw-bold fs-5">${item.jumlah}</span>
                        </td>
                        ${is_admin ? `
                        <td class="item-actions text-end">
                            <button onclick="editItem(${item.id})" class="btn btn-sm btn-outline-warning me-1"><i class="bi bi-pencil-square"></i></button>
                            <button onclick="deleteItem(${item.id})" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </td>
                        ` : ''}
                    </tr>`;
        });
          
        html += '</tbody></table></div></div></div>';
    }
      
    // Loop Kategori Kosong
    if (results.empty_categories.length > 0) {
        results.empty_categories.forEach(kategori => {
            const menuId = `search-menu-cat-${kategori.id}`;
            html += `<div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center bg-white py-3">
                            <h5 class="mb-0 fw-bold text-dark">${kategori.nama}</h5>
                             ${is_admin ? `
                            <div class="position-relative">
                                <button class="btn btn-sm btn-light rounded-circle border btn-menu" onclick="toggleSearchMenuById('${menuId}')">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <div class="dropdown-menu-custom" id="${menuId}">
                                    <button onclick="showAddItemModal(${kategori.id}, '${kategori.nama}')" class="text-success"><i class="bi bi-plus-circle me-2"></i>Tambah Isi</button>
                                    <button onclick="showEditKategori(${kategori.id})" class="text-warning"><i class="bi bi-pencil me-2"></i>Ubah Nama</button>
                                    <button onclick="deleteKategori(${kategori.id})" class="text-danger"><i class="bi bi-trash me-2"></i>Hapus Kategori</button>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                        <div class="card-body"><p class="text-muted fst-italic mb-0">Kategori cocok, tapi barang kosong.</p></div>
                     </div>`;
        });
    }

    if (results.total_pages > 1) {
        html += renderAjaxPagination(results.current_page, results.total_pages);
    }
      
    html += '</div>';
    searchResults.innerHTML = html;
}

function renderAjaxPagination(currentPage, totalPages) {
    let html = '<nav aria-label="Search navigation"><ul class="pagination justify-content-center ajax-pagination">';
    
    // Previous
    html += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="liveSearch(${currentPage - 1})">&laquo;</button>
             </li>`;

    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" onclick="liveSearch(${i})">${i}</button>
                 </li>`;
    }

    // Next
    html += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="liveSearch(${currentPage + 1})">&raquo;</button>
             </li>`;
    
    html += '</ul></nav>';
    return html;
}

function toggleSearchMenuById(elementId) {
    if (window.event) window.event.stopPropagation();

    const menu = document.getElementById(elementId);
    
    document.querySelectorAll('.dropdown-menu-custom').forEach(m => {
        if (m.id !== elementId) m.style.display = 'none';
    });

    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
    }
}

// ==========================================
// Event Listeners
// ==========================================
function toggleMenu(kategoriId) {
    if (window.event) {
        window.event.stopPropagation();
    }

    const menu = document.getElementById(`menu-${kategoriId}`);
    
    document.querySelectorAll('.dropdown-menu-custom').forEach(otherMenu => {
        if (otherMenu.id !== `menu-${kategoriId}`) {
            otherMenu.style.display = 'none';
        }
    });

    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'block';
    }
}

document.addEventListener('click', function(event) {
    const isClickInsideMenu = event.target.closest('.dropdown-menu-custom');
    const isClickOnButton = event.target.closest('.btn-menu');

    if (!isClickInsideMenu && !isClickOnButton) {
        document.querySelectorAll('.dropdown-menu-custom').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});
