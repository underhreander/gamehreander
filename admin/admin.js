/* ============================================
   SOFTMASTER — ADMIN PANEL JS
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {

    /* ------------------------------------------
       Confirm dangerous actions
    ------------------------------------------ */
    document.querySelectorAll('.confirm-action').forEach(btn => {
        btn.addEventListener('click', function (e) {
            const action = this.dataset.action || 'perform this action';
            if (!confirm(`Are you sure you want to ${action}?`)) {
                e.preventDefault();
            }
        });
    });

    /* ------------------------------------------
       Toggle game active status (visual)
    ------------------------------------------ */
    document.querySelectorAll('.toggle-game-active').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const gameId = this.dataset.gameId;
            const isActive = this.checked ? 1 : 0;

            const statusEl = this.closest('.game-card').querySelector('.game-status');
            if (statusEl) {
                statusEl.className = 'game-status ' + (isActive ? 'active' : 'inactive');
                statusEl.innerHTML = `<i class="fas fa-circle"></i> ${isActive ? 'Active' : 'Inactive'}`;
            }

            console.log(`Software ${gameId} set to ${isActive ? 'active' : 'inactive'}`);
        });
    });

    /* ------------------------------------------
       Auto-hide alerts after 5s
    ------------------------------------------ */
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    /* ------------------------------------------
       Sortable tables
    ------------------------------------------ */
    initSortableTables();
    initDynamicFilters();
});

/* ============================================
   FILTER GAMES BY CATEGORY
   ============================================ */
function filterGames(filter, clickedBtn) {
    // Update active tab
    document.querySelectorAll('.software-filter-tabs .filter-tab').forEach(t => t.classList.remove('active'));
    if (clickedBtn) clickedBtn.classList.add('active');

    // Filter cards
    document.querySelectorAll('.game-card').forEach(card => {
        const catId = card.dataset.categoryId || '';
        let show = false;

        if (filter === 'all') {
            show = true;
        } else if (filter === 'uncategorized') {
            show = (!catId || catId === '' || catId === '0');
        } else {
            const id = filter.replace('cat-', '');
            show = (catId === id);
        }

        card.style.display = show ? '' : 'none';
    });
}

/* ============================================
   EDIT CATEGORY MODAL
   ============================================ */
function openEditCategoryModal(id, name, order) {
    const modal = document.getElementById('editCategoryModal');
    if (!modal) return;

    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_category_order').value = order;

    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closeEditCategoryModal() {
    const modal = document.getElementById('editCategoryModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
}

/* ============================================
   EDIT GAME MODAL
   ============================================ */
function openEditModal(id, name, categoryId, isActive, order) {
    const modal = document.getElementById('editModal');
    if (!modal) return;

    document.getElementById('edit_game_id').value = id;
    document.getElementById('edit_game_name').value = name;
    document.getElementById('edit_game_active').checked = (isActive == 1);
    document.getElementById('edit_game_order').value = order;

    // Set category select
    const catSelect = document.getElementById('edit_game_category');
    if (catSelect) {
        catSelect.value = categoryId || '';
    }

    modal.classList.add('show');
    modal.style.display = 'flex';
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if (!modal) return;
    modal.classList.remove('show');
    modal.style.display = 'none';
}

/* ============================================
   IMAGE PREVIEW
   ============================================ */
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="max-width:200px; max-height:150px; border-radius:8px;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewImageUrl(inputEl) {
    const preview = document.getElementById('imagePreview');
    if (!preview) return;
    const url = inputEl.value.trim();
    if (url) {
        preview.innerHTML = `<img src="${url}" alt="Preview" style="max-width:200px; max-height:150px; border-radius:8px;" onerror="this.parentElement.innerHTML='<span style=color:#e74c3c>Failed to load image</span>'">`;
    } else {
        preview.innerHTML = '';
    }
}

/* ============================================
   SECTION TOGGLE (Categories / Software)
   ============================================ */
function showSection(section) {
    const catSection = document.getElementById('categoriesSection');
    const softSection = document.getElementById('softwareSection');
    const btnCat = document.getElementById('btnShowCategories');
    const btnSoft = document.getElementById('btnShowSoftware');

    if (section === 'categories') {
        if (catSection) catSection.style.display = 'block';
        if (softSection) softSection.style.display = 'none';
        if (btnCat) btnCat.classList.add('active');
        if (btnSoft) btnSoft.classList.remove('active');
    } else {
        if (catSection) catSection.style.display = 'none';
        if (softSection) softSection.style.display = 'block';
        if (btnSoft) btnSoft.classList.add('active');
        if (btnCat) btnCat.classList.remove('active');
    }
}

/* ============================================
   SORTABLE TABLES
   ============================================ */
function initSortableTables() {
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.style.cursor = 'pointer';
        th.addEventListener('click', function () {
            const table = this.closest('table');
            const colIndex = Array.from(this.parentNode.children).indexOf(this);
            const dir = this.dataset.dir === 'asc' ? 'desc' : 'asc';

            // Reset all headers
            this.parentNode.querySelectorAll('th').forEach(h => {
                h.dataset.dir = '';
                const icon = h.querySelector('.sort-icon');
                if (icon) icon.remove();
            });

            this.dataset.dir = dir;
            this.insertAdjacentHTML('beforeend',
                `<span class="sort-icon"> <i class="fas fa-sort-${dir === 'asc' ? 'up' : 'down'}"></i></span>`);

            sortTable(table, colIndex, dir);
        });
    });
}

function sortTable(table, colIndex, direction) {
    const tbody = table.querySelector('tbody') || table;
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        const aText = (a.children[colIndex]?.textContent || '').trim().toLowerCase();
        const bText = (b.children[colIndex]?.textContent || '').trim().toLowerCase();
        const aNum = parseFloat(aText);
        const bNum = parseFloat(bText);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        }
        return direction === 'asc' ? aText.localeCompare(bText) : bText.localeCompare(aText);
    });

    rows.forEach(row => tbody.appendChild(row));
}

/* ============================================
   DYNAMIC TABLE FILTERS
   ============================================ */
function initDynamicFilters() {
    document.querySelectorAll('.table-filter').forEach(input => {
        input.addEventListener('input', function () {
            const query = this.value.toLowerCase();
            const table = document.querySelector(this.dataset.table);
            if (!table) return;

            const rows = table.querySelectorAll('tbody tr, tr:not(:first-child)');
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    });
}

/* ============================================
   CLOSE MODALS ON BACKDROP CLICK
   ============================================ */
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('show');
        e.target.style.display = 'none';
    }
});

/* ESC key closes modals */
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.show').forEach(m => {
            m.classList.remove('show');
            m.style.display = 'none';
        });
    }
});