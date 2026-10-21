import { DataTable } from 'simple-datatables';

const serviceTable = document.querySelector('#service-table');
const guideTable = document.querySelector('#guide-table');
const announcementTable = document.querySelector('#announcement-table');
const officeTable = document.querySelector('#office-table');
const table = document.querySelector('#category-table') || serviceTable || guideTable || announcementTable || officeTable;
const entity = serviceTable ? 'layanan' : guideTable ? 'panduan' : announcementTable ? 'pengumuman' : officeTable ? 'data kecamatan' : 'kategori';
if (table) {
    new DataTable(table, {
        perPage: 10,
        perPageSelect: [5, 10, 25, 50],
        searchable: true,
        searchAnd: true,
        labels: {
            placeholder: `Cari ${entity}...`,
            searchTitle: `Cari ${entity}`,
            perPage: 'data per halaman',
            noRows: `Belum ada ${entity}.`,
            noResults: `Tidak ada ${entity} yang cocok.`,
            info: `Menampilkan {start}–{end} dari {rows} ${entity}`,
        },
        columns: [
            ...(officeTable ? [] : [{ select: serviceTable ? [2] : guideTable ? [2, 3] : announcementTable ? [1] : [1, 3], type: 'number' }]),
            { select: officeTable ? 3 : serviceTable || guideTable ? 5 : 4, sortable: false, searchable: false },
        ],
    });
    const search = document.querySelector('.category-table .datatable-input');
    search?.setAttribute('aria-label', `Cari ${entity}`);
    document.querySelector('.category-table .datatable-selector')?.setAttribute('aria-label', `Jumlah ${entity} per halaman`);
    table.addEventListener('change', (event) => {
        const input = event.target;
        if (input instanceof HTMLInputElement && input.closest('.category-status-form')) {
            input.form.requestSubmit();
        }
    });
}

const officeDialog = document.querySelector('#office-dialog');
const officeForm = document.querySelector('#office-form');
const officeAdd = document.querySelector('#office-add');
if (officeDialog && officeForm) {
    officeAdd?.addEventListener('click', (event) => {
        event.preventDefault();
        officeForm.reset();
        officeForm.action = officeAdd.href.replace('&new=1', '');
        const defaults = JSON.parse(document.querySelector('#office-form-defaults').textContent);
        for (const [key, value] of Object.entries(defaults)) {
            const input = officeForm.elements.namedItem(key);
            if (input.type === 'checkbox') input.checked = value === 1;
            else input.value = value;
        }
        officeForm.querySelectorAll('input[type=password]').forEach((input) => input.required = true);
        officeDialog.querySelectorAll('.admin-error').forEach((error) => error.remove());
        document.querySelector('#office-dialog-title').textContent = 'Tambah data';
        officeDialog.showModal();
    });
    officeDialog.querySelectorAll('[data-office-close]').forEach((close) => close.addEventListener('click', (event) => {
        event.preventDefault();
        officeDialog.close();
    }));
    officeDialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            officeDialog.close();
        }
    });
    officeDialog.addEventListener('close', () => (officeAdd || document.querySelector('.office-tabs [aria-current]'))?.focus());
    if (officeDialog.hasAttribute('data-auto-open')) {
        officeDialog.removeAttribute('open');
        officeDialog.showModal();
    }
}

const announcementDialog = document.querySelector('#announcement-dialog');
const announcementForm = document.querySelector('#announcement-form');
const announcementAdd = document.querySelector('#announcement-add');
if (announcementDialog && announcementForm && announcementAdd) {
    announcementAdd.addEventListener('click', (event) => {
        event.preventDefault();
        announcementForm.reset();
        announcementForm.action = announcementAdd.href.replace('&new=1', '');
        announcementForm.querySelectorAll('input:not([type=hidden]),textarea').forEach((input) => {
            if (input.type === 'checkbox') input.checked = false;
            else input.value = '';
        });
        announcementForm.elements.namedItem('version').value = '0';
        announcementForm.elements.namedItem('priority').value = '0';
        announcementDialog.querySelectorAll('.admin-error').forEach((error) => error.remove());
        document.querySelector('#announcement-dialog-title').textContent = 'Tambah pengumuman';
        announcementDialog.showModal();
        announcementForm.elements.namedItem('title').focus();
    });
    announcementDialog.querySelectorAll('[data-announcement-close]').forEach((close) => close.addEventListener('click', (event) => {
        event.preventDefault();
        announcementDialog.close();
    }));
    announcementDialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            announcementDialog.close();
        }
    });
    announcementDialog.addEventListener('close', () => announcementAdd.focus());
    if (announcementDialog.hasAttribute('data-auto-open')) {
        announcementDialog.removeAttribute('open');
        announcementDialog.showModal();
    }
}

const guideDialog = document.querySelector('#guide-dialog');
const guideForm = document.querySelector('#document-guide-form');
const guideAdd = document.querySelector('#guide-add');
if (guideDialog && guideForm && guideAdd) {
    guideAdd.addEventListener('click', (event) => {
        event.preventDefault();
        guideForm.reset();
        guideForm.action = guideAdd.href.replace('&new=1', '');
        guideForm.querySelectorAll('input:not([type=hidden]),textarea').forEach((input) => {
            if (input.type === 'checkbox') input.checked = input.name === 'is_demo';
            else input.value = '';
        });
        guideForm.elements.namedItem('version').value = '0';
        guideForm.elements.namedItem('region').value = 'Kabupaten Serdang Bedagai';
        guideForm.elements.namedItem('sort_order').value = '0';
        guideDialog.querySelectorAll('.admin-error').forEach((error) => error.remove());
        document.querySelector('#guide-dialog-title').textContent = 'Tambah pengurusan dokumen';
        guideDialog.showModal();
        guideForm.elements.namedItem('name').focus();
    });
    guideDialog.querySelectorAll('[data-guide-close]').forEach((close) => close.addEventListener('click', (event) => {
        event.preventDefault();
        guideDialog.close();
    }));
    guideDialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            guideDialog.close();
        }
    });
    guideDialog.addEventListener('close', () => guideAdd.focus());
    if (guideDialog.hasAttribute('data-auto-open')) {
        guideDialog.removeAttribute('open');
        guideDialog.showModal();
    }
}

const serviceDialog = document.querySelector('#service-dialog');
const serviceForm = document.querySelector('#service-form');
const serviceAdd = document.querySelector('#service-add');
if (serviceDialog && serviceForm && serviceAdd) {
    const requirements = document.querySelector('#service-requirements');
    let nextRequirement = requirements.children.length;
    const addRequirement = document.querySelector('#service-requirement-add');
    const updateRequirementLimit = () => { addRequirement.disabled = requirements.children.length >= 50; };
    updateRequirementLimit();
    addRequirement.addEventListener('click', () => {
        const row = document.querySelector('#service-requirement-template').content.cloneNode(true);
        row.querySelectorAll('[data-field]').forEach((input) => {
            input.name = `requirements[${nextRequirement}][${input.dataset.field}]`;
        });
        nextRequirement += 1;
        requirements.append(row);
        updateRequirementLimit();
        requirements.lastElementChild.querySelector('[data-field="name"]').focus();
    });
    requirements.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-remove-requirement]');
        if (!remove) return;
        remove.closest('fieldset').remove();
        updateRequirementLimit();
        addRequirement.focus();
    });
    serviceAdd.addEventListener('click', (event) => {
        event.preventDefault();
        serviceForm.reset();
        serviceForm.action = serviceAdd.href.replace('&new=1', '');
        serviceForm.querySelectorAll('input:not([type=hidden]),textarea,select').forEach((input) => {
            if (input.type === 'checkbox') input.checked = false;
            else input.value = '';
        });
        serviceForm.elements.namedItem('version').value = '0';
        serviceForm.elements.namedItem('sort_order').value = '0';
        serviceForm.elements.namedItem('retention_days').value = '30';
        requirements.replaceChildren();
        updateRequirementLimit();
        serviceDialog.querySelectorAll('.admin-error').forEach((error) => error.remove());
        document.querySelector('#service-dialog-title').textContent = 'Tambah layanan';
        serviceDialog.showModal();
        document.querySelector('#service-category').focus();
    });
    serviceDialog.querySelectorAll('[data-service-close]').forEach((close) => close.addEventListener('click', (event) => {
        event.preventDefault();
        serviceDialog.close();
    }));
    serviceDialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            serviceDialog.close();
        }
    });
    serviceDialog.addEventListener('close', () => serviceAdd.focus());
    if (serviceDialog.hasAttribute('data-auto-open')) {
        serviceDialog.removeAttribute('open');
        serviceDialog.showModal();
    }
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
