$(document).ready(function () {
    // deal with ajax request
    function sendAjax(url, payload, successCallback, errorCallback) {
        $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: successCallback,
            error: errorCallback || function () { alert('Erreur de communication avec le serveur.'); }
        });
    }

    // add a group
    $('#btn-save-group').on('click', function () {
        const label = $('#group-label').val().trim();
        const diplomaId = $('#btn-create-group').data('diploma-id');
        if (!label) return alert('Le libellé est requis.');

        sendAjax(
            `/skill-manage/group/save/${diplomaId}`,
            { label },
            function (res) {
                if (res.success) {
                    $('#modalAddSkillGroup').modal('hide');

                    const newRow = `
                        <tr data-group-id="${res.group.id}">
                            <td class="text-start">${res.group.label}</td>
                            <td class="text-end">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input js-toggle-group-status" type="checkbox"
                                           data-id="${res.group.id}"
                                           data-label="${res.group.label}" >
                                </div>
                            </td>
                        </tr>`;
                    $('#groups-summary tbody').append(newRow);

                    const cardHtml = `
                        <div class="card mb-4" id="group-card-${res.group.id}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h3 class="text-start fw-bold mb-0">Groupe : ${res.group.label}</h3>
                                    <button class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalAddSkill"
                                            data-group-id="${res.group.id}"
                                            data-group-label="${res.group.label}">
                                        + Ajouter compétence
                                    </button>
                                </div>
                                <table class="table table-bordered mt-2 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">Libellé</th>
                                            <th class="text-end">Désactivé</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">Aucune compétence.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>`;
                    $('hr').eq(1).after(cardHtml);
                } else {
                    alert(res.message || 'Erreur lors de la création du groupe.');
                }
            }
        );
    });

    // add a skill button
    $('#btn-save-skill').on('click', function () {
        const label = $('#skill-label').val().trim();
        const groupId = $('#modalAddSkill').data('group-id');
        if (!label) return alert('Le libellé est requis.');

        sendAjax(
            `/skill-manage/skill/save/${groupId}`,
            { label },
            function (res) {
                if (res.success) {
                    $('#modalAddSkill').modal('hide');

                    const groupLabel = $('#modalAddSkill').find('.modal-title').text().split('"')[1];
                    const $card = $(`.card:has(h3:contains("${groupLabel}"))`);

                    const newSkillRow = `
                        <tr>
                            <td class="text-start">${res.skill.label}</td>
                            <td class="text-end">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input js-toggle-status" type="checkbox" data-id="${res.skill.id}">
                                </div>
                            </td>
                        </tr>`;
                    $card.find('tbody').append(newSkillRow);
                } else {
                    alert(res.message || 'Erreur lors de l’ajout de la compétence.');
                }
            }
        );
    });

    // toggle a skill
    $(document).on('change', '.js-toggle-status', function () {
        const $chk = $(this);
        const id = $chk.data('id');
        const isChecked = $chk.prop('checked');

        if (!confirm(`Êtes-vous sûr de vouloir ${isChecked ? 'activer' : 'désactiver'} cette compétence ?`)) {
            $chk.prop('checked', !isChecked);
            return;
        }

        sendAjax(`/skill-manage/skill/${id}/toggle`, {}, function (res) {
            if (res.success && !isChecked) {
                $chk.closest('tr').remove();
            } else if (!res.success) {
                alert(res.message || 'Erreur.');
                $chk.prop('checked', !isChecked);
            }
        });
    });

    // toggle group
    $(document).on('change', '.js-toggle-group-status', function () {
        const $chk = $(this);
        const id = $chk.data('id');
        const isChecked = $chk.prop('checked');

        if (!confirm(`Êtes-vous sûr de vouloir ${isChecked ? 'activer' : 'désactiver'} ce groupe de compétences ?`)) {
            $chk.prop('checked', !isChecked);
            return;
        }

        sendAjax(`/skill-manage/group/${id}/toggle`, {}, function (res) {
            if (res.success && !isChecked) {
                $(`#groups-summary tbody tr[data-group-id="${id}"]`).remove();
                $(`#group-card-${id}`).remove();
            } else if (!res.success) {
                alert(res.message || 'Erreur.');
                $chk.prop('checked', !isChecked);
            }
        });
    });

    // prepare modal add skill
    $(document).on('click', 'button[data-bs-target="#modalAddSkill"]', function () {
        const groupId = $(this).data('group-id');
        const groupLabel = $(this).data('group-label');

        const $modal = $('#modalAddSkill');
        $modal.data('group-id', groupId);
        $modal.find('.modal-title').text(`Ajouter une compétence au groupe : "${groupLabel}"`);
        $modal.find('#skill-label').val('');
    });
});
