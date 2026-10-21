import { DataTable } from 'simple-datatables';

const table = document.querySelector('#category-table');
if (table) {
    new DataTable(table, {
        perPage: 10,
        perPageSelect: [5, 10, 25, 50],
        searchable: true,
        searchAnd: true,
        labels: {
            placeholder: 'Cari kategori...',
            searchTitle: 'Cari kategori',
            perPage: 'data per halaman',
            noRows: 'Belum ada kategori.',
            noResults: 'Tidak ada kategori yang cocok.',
            info: 'Menampilkan {start}–{end} dari {rows} kategori',
        },
        columns: [
            { select: [1, 3], type: 'number' },
            { select: 4, sortable: false, searchable: false },
        ],
    });
    const search = document.querySelector('.category-table .datatable-input');
    search?.setAttribute('aria-label', 'Cari kategori');
    document.querySelector('.category-table .datatable-selector')?.setAttribute('aria-label', 'Jumlah kategori per halaman');
    table.addEventListener('change', (event) => {
        const input = event.target;
        if (input instanceof HTMLInputElement && input.closest('.category-status-form')) {
            input.form.requestSubmit();
        }
    });
}

const dialog = document.querySelector('#category-dialog');
const form = document.querySelector('#category-form');
const add = document.querySelector('#category-add');

if (dialog && form && add) {
    add.addEventListener('click', (event) => {
        event.preventDefault();
        form.reset();
        form.action = add.href.replace('&new=1', '');
        for (const [key, value] of Object.entries({
            name: '', slug: '', description: '', icon: 'document', sort_order: '0', is_active: '1', version: '0',
        })) {
            form.elements.namedItem(key).value = value;
        }
        form.elements.namedItem('is_active').checked = true;
        dialog.querySelector('[data-category-status-label]').textContent = 'Aktif';
        dialog.querySelectorAll('.admin-error').forEach((error) => error.remove());
        document.querySelector('#category-dialog-title').textContent = 'Tambah kategori layanan';
        dialog.showModal();
    });
    dialog.querySelectorAll('[data-category-close]').forEach((close) => {
        close.addEventListener('click', (event) => {
            event.preventDefault();
            dialog.close();
        });
    });
    dialog.addEventListener('close', () => add.focus());
    form.elements.namedItem('is_active').addEventListener('change', (event) => {
        dialog.querySelector('[data-category-status-label]').textContent = event.target.checked ? 'Aktif' : 'Nonaktif';
    });
    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            dialog.close();
        }
    });
    if (dialog.hasAttribute('data-auto-open')) {
        dialog.removeAttribute('open');
        dialog.showModal();
    }
}
