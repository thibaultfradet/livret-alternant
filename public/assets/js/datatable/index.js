

/**
 * Add a cell to a table row
 */
function dataTable_addCell(tr, content, colSpan = 1) {
    const td = document.createElement('td');
    td.colSpan = colSpan;
    td.textContent = content;
    tr.appendChild(td);
}


/**
 * Common DataTable configuration shared across all tables
 */
const baseTableConfig = {
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
    },
    pageLength: 50,
    lengthMenu: [10, 25, 50, 100],
    responsive: true,
    stripeClasses: ['table-striped', 'table-hover'],
    columnDefs: [
        { className: 'text-center align-middle', targets: '_all' }
    ],
    dom:
        '<"card-body border-bottom py-3 d-flex justify-content-between align-items-center"' +
            '<"dataTables_length"l>' +
            '<"dataTables_filter"f>' +
        '>' +
        't' +
        '<"card-footer d-flex align-items-center"' +
            '<"m-0 text-secondary"i>' +
            '<"pagination m-0 ms-auto"p>' +
        '>',
    initComplete: function() {
        $(this.api().table().header()).find('th').addClass('text-center align-middle');
    }
};

/**
 * DataTable configuration without search bar
 */
const noSearchTableConfig = {
    language: {
        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
    },
    pageLength: 50,
    lengthMenu: [10, 25, 50, 100],
    responsive: true,
    stripeClasses: ['table-striped', 'table-hover'],
    columnDefs: [
        { className: 'text-center align-middle', targets: '_all' }
    ],
    dom:
        '<"card-body border-bottom py-3 d-flex justify-content-between align-items-center"' +
            '<"dataTables_length"l>' +
        '>' +
        't' +
        '<"card-footer d-flex align-items-center"' +
            '<"m-0 text-secondary"i>' +
            '<"pagination m-0 ms-auto"p>' +
        '>',
    initComplete: function() {
        $(this.api().table().header()).find('th').addClass('text-center align-middle');
    }
};



/**
 * Helper to initialize a DataTable safely
 */
function initDataTable(selector, config) {
    const table = document.querySelector(selector);
    if (!table) return;

    const colNum = $(`${selector} > thead > tr:first > th`).length;
    $(selector).DataTable({
        ...baseTableConfig,
        ...config(colNum)
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const tables = [
        { id: 'behavior-table', config: (colNum) => ({
            order: [[0, 'asc']]
        }) },
        { id: 'users-table', config: (colNum) => ({
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [0, colNum - 1], orderable: false },
                { targets: 1, width: '120px' } // role column
            ]
        }) },
        { id: 'classroomTable', config: (colNum) => ({
            order: [[0, 'asc']]
        }) },
        { id: 'periodsTable', config: (colNum) => ({
            order: [[0, 'asc']],
            columnDefs: [
                { targets: 0, width: '50px' } // numéro de période
            ]
        }) },
        { id: 'diplomaTable', config: (colNum) => ({
            order: [[0, 'asc']]
        }) },
        { id: 'schoolYearTable', config: (colNum) => ({
            order: [[0, 'asc']]
        }) },
        { id: 'teachers-table', config: (colNum) => ({
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0, colNum - 1], orderable: false }
            ],
            dom: '<"card-body border-bottom py-3"<"d-flex"<"text-secondary"><"ms-auto text-secondary"f>>>t<"card-footer d-flex align-items-center"<"m-0 text-secondary"><"pagination m-0 ms-auto">>'
        }) },
        { id: 'listModesTable', config: (colNum) => ({
            order: [[0, 'asc']],
            columnDefs: [
                { targets: colNum - 1, orderable: false }
            ]
        }), noSearch: true },
        { id: 'studentTable', config: () => ({
            order: [[1, 'asc']],
            columnDefs: [
                { targets: 0, orderable: false }
            ]
        }), noSearch: true },
        { id: 'evaluationTable', config: () => ({
            order: [[1, 'asc']],
            columnDefs: [
                { targets: [0, 2], orderable: false }
            ]
        }), noSearch: true }
    ];

    tables.forEach(tableInfo => {
        const tableEl = document.getElementById(tableInfo.id);
        if (!tableEl) return;

        const colNum = $(`#${tableInfo.id} > thead > tr:first > th`).length;
        const config = tableInfo.noSearch ? noSearchTableConfig : baseTableConfig;

        $(`#${tableInfo.id}`).DataTable({
            ...config,
            ...tableInfo.config(colNum)
        });
    });
});