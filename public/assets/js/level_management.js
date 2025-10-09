$(document).ready(function () {
    // --- Fonction centrale pour AJAX ---
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

    // --- Ajout d'un niveau ---
    $('#btn-save-level').on('click', function () {
        const label = $('#level-label').val().trim();
        if (!label) return alert('Le libellé est requis.');

        sendAjax(
            '/skill-manage/level/save',
            { label },
            function (res) {
                if (res.success) {
                    $('#modalAddLevel').modal('hide');

                    // Ajouter le niveau dans la liste existante
                    const newLevelRow = `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            ${res.level.label}
                            <div class="form-check form-switch">
                                <input class="form-check-input js-toggle-level-status" type="checkbox"
                                       role="switch"
                                       data-id="${res.level.id}">
                                <label class="form-check-label">Désactivé</label>
                            </div>
                        </li>`;
                    $('#list-levels ul').append(newLevelRow);
                    $('#level-label').val(''); // reset input
                } else {
                    alert(res.message || 'Erreur lors de l’ajout du niveau.');
                }
            }
        );
    });

    // --- Toggle niveau (désactiver/activer) ---
    $(document).on('change', '.js-toggle-level-status', function () {
        const $chk = $(this);
        const id = $chk.data('id');
        const levelLabel = $chk.closest('li').contents().get(0).nodeValue.trim(); // récupérer le texte du li
        const isChecked = $chk.prop('checked');

        if (!confirm(`Êtes-vous sûr de vouloir ${isChecked ? 'désactiver' : 'activer'} le niveau "${levelLabel}" ?`)) {
            $chk.prop('checked', !isChecked);
            return;
        }

        sendAjax(`/skill-manage/level/${id}/toggle`, {}, function (res) {
            if (!res.success) {
                alert(res.message || 'Erreur.');
                $chk.prop('checked', !isChecked);
            }
        });
    });
});
