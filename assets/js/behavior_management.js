$(document).ready(function () {
    //  Central AJAX function
    function sendAjax(url, payload, successCallback, errorCallback) {
        $.ajax({
            url: url,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify(payload),
            success: successCallback,
            error:
                errorCallback ||
                function () {
                    alert("Server communication error.");
                },
        });
    }

    //  Create new behavior from modal form
    $("#behaviorModal form").on("submit", function (e) {
        e.preventDefault();
        const payload = {
            label_behavior: $("#labelBehavior").val(),
            niveau1: $("#niveau1").val(),
            niveau2: $("#niveau2").val(),
            niveau3: $("#niveau3").val(),
            niveau4: $("#niveau4").val(),
            niveau5: $("#niveau5").val(),
        };

        sendAjax("/behavior/manage/create", payload, function (response) {
            if (response.success) {
                // Close modal
                $("#behaviorModal").modal("hide");
                // Reset form
                $("#behaviorModal form")[0].reset();

                // Dynamically update the behavior list
                const crit = response.criteria;
                let html = `<li data-id="${crit.id}">
                                <strong>${crit.label}</strong>
                                <ul>`;
                crit.levels.forEach((lvl) => {
                    html += `<li data-level-id="${lvl.id}">Level ${lvl.level_number}: ${lvl.label} 
                                <button class="btn btn-sm btn-warning replace-level-btn" data-level-id="${lvl.id}" data-level-label="${lvl.label}" data-bs-toggle="modal" data-bs-target="#editLevelModal">Replace</button>
                             </li>`;
                });
                html += `</ul></li>`;

                $("#behavior-list").append(html);
            } else {
                alert(response.message || "Error while creating the behavior.");
            }
        });
    });

    //  Prefill the level replacement modal
    $("#editLevelModal").on("show.bs.modal", function (event) {
        const trigger = $(event.relatedTarget);
        const oldLabel = trigger.data("level-label") || "-";
        const oldId = trigger.data("level-id") || "";

        const modal = $(this);
        modal.find("#oldLevelLabel").text(oldLabel);
        modal.find("#oldLevelId").val(oldId);
        modal.find("#levelId").val(oldId);
    });

    //  Submit the level replacement form
    $("#editLevelForm").on("submit", function (e) {
        e.preventDefault();

        const levelId = $("#levelId").val();
        const replacementLabel = $("#levelLabel").val().trim();

        if (!replacementLabel) {
            alert("The new level label is required.");
            return;
        }

        const payload = {
            label_level_replacement: replacementLabel,
        };

        sendAjax(
            `/behavior/level/toggle/${levelId}`,
            payload,
            function (response) {
                if (response.success) {
                    // Close modal
                    $("#editLevelModal").modal("hide");
                    // Reset form
                    $("#editLevelForm")[0].reset();

                    // Dynamically update the list
                    const oldLevelElem = $(`[data-level-id='${levelId}']`);
                    oldLevelElem.find("button.replace-level-btn").remove(); // remove button from old level
                    oldLevelElem.append(
                        `<span class="text-muted"> (disabled)</span>`
                    );

                    // Add the new level after the old one
                    const newLevel = response.new_level;
                    oldLevelElem.after(`<li data-level-id="${newLevel.id}">
                                        Level ${newLevel.level_number}: ${newLevel.label} 
                                        <button class="btn btn-sm btn-warning replace-level-btn" data-level-id="${newLevel.id}" data-level-label="${newLevel.label}" data-bs-toggle="modal" data-bs-target="#editLevelModal">Replace</button>
                                    </li>`);
                } else {
                    alert(
                        response.message || "Error while replacing the level."
                    );
                }
            }
        );
    });

    // Handle criteria toggle (checkbox click)
    $(document).on("change", "input[id^='disableBehavior']", function () {
        // Get behavior criteria id from checkbox id
        const criteriaId = $(this).attr("id").replace("disableBehavior", "");

        // Send AJAX request to toggle criteria
        sendAjax(
            `/behavior/criteria/toggle/${criteriaId}`,
            {}, // no payload needed
            function (response) {
                if (response.success) {
                    // Display a quick feedback message
                    console.log(response.message);
                } else {
                    alert(response.message || "Error while toggling criteria.");
                }
            }
        );
    });
});
