// Initialize DataTables across multiple pages with unified style
$(document).ready(function() {

    // --- Common configuration for simple DataTables ---
    const simpleTableConfig = {
        responsive: true,
        paging: false,
        searching: false,
        info: false,
        stripeClasses: ['table-striped', 'table-hover'],
        columnDefs: [
            { className: 'text-center align-middle', targets: '_all' } // Center all text
        ]
    };

    // --- Behavior management table ---
    if ($('#behaviorTable').length) {
        $('#behaviorTable').DataTable({
            ...simpleTableConfig,
            stripeClasses: ['table-striped-columns', 'table-hover']
        });
    }

    // --- Classroom table ---
    if ($('#classroomTable').length) {
        $('#classroomTable').DataTable(simpleTableConfig);
    }

    // --- Diploma management table ---
    if ($('#diplomaTable').length) {
        $('#diplomaTable').DataTable(simpleTableConfig);
    }

    // --- Missing evaluations table ---
    if ($('#missingEvaluationsTable').length) {
        $('#missingEvaluationsTable').DataTable(simpleTableConfig);
    }

    // --- Evaluation validation table ---
    if ($('#evaluationsValidationTable').length) {
        $('#evaluationsValidationTable').DataTable(simpleTableConfig);
    }

    // --- Notification table ---
    if ($('#evaluationTable').length) {
        $('#evaluationTable').DataTable(simpleTableConfig);
    }

    // --- Periods of active year table ---
    if ($('#periodsTable').length) {
        $('#periodsTable').DataTable(simpleTableConfig);
    }

    // --- School year list table ---
    if ($('#schoolYearTable').length) {
        $('#schoolYearTable').DataTable({
            ...simpleTableConfig,
            columnDefs: [
                { className: 'text-center align-middle', targets: '_all' }, // Center all text
                { targets: 5, width: '300px', render: function(data, type, row) {
                    if (type === 'display' && data.length > 100) {
                        return data.substr(0, 100) + '...';
                    }
                    return data;
                }}
            ]
        });
    }

    // --- Skill managment - Group summary ---
    if ($('#groups-summary').length) {
        $('#groups-summary').DataTable(simpleTableConfig);
    }

    // --- Users list table ---
    if ($('#users-table').length) {
        $('#users-table').DataTable(simpleTableConfig);
    }

});