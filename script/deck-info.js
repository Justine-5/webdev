document.querySelector('.import-button').addEventListener('click', function () {
    document.getElementById('csvFile').click();
});

function confirmDelete() {
    Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('delete-deck-form').submit();
        }
    });
}


function editDeck(deckId, deckName, deckDescription) {
    Swal.fire({
        title: "Edit Deck",
        html: `
            <form id="edit-deck-form" action="deck-info.php?id=${deckId}" method="post">
                <input type="hidden" name="action" value="edit_deck">
                <label for="swal-deck-name">Name</label>
                <input id="swal-deck-name" name="deck-name" type="text" value="${deckName}" required>
                <label for="swal-deck-description">Description</label>
                <input id="swal-deck-description" name="deck-description" value="${deckDescription}">
            </form>
            `,
        showCancelButton: true,
        confirmButtonText: "Save",
        cancelButtonText: "Cancel",
        preConfirm: () => {
            const deckNameInput = Swal.getPopup().querySelector("#swal-deck-name").value.trim();
            if (!deckNameInput) {
                Swal.showValidationMessage("Deck name is required.");
            }
            return { deckName: deckNameInput };
        },
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('edit-deck-form').submit();
        }
    });
}

$(document).ready(function() {
    $('#cardsTable').DataTable({
        "language": {
            "emptyTable": "No cards available. Add a new card!",
            "info": "_START_ - _END_ of _TOTAL_ cards",
            "infoEmpty": "No entries to show",
            "search": "",
            "searchPlaceholder": "Search a card" 
        },
        "paging": true,
        "ordering": true,
        "info": true,
        "searching": true,
        "lengthChange": false,
        "responsive": true,
        "layout": {
            bottomEnd: {
                paging: {
                    buttons: 5
                }
            }
        }
    });

    $('.editable').on('blur', function() {
        var field = $(this).data('field');
        var id = $(this).data('id');
        var newValue = $(this).text();
        var element = $(this);

        if (newValue !== $(this).data('original-value')) {
            $.ajax({
                url: 'update-card.php',
                method: 'POST',
                data: {
                    action: 'update_card',
                    field: field,
                    value: newValue,
                    card_id: id
                },
                success: function(response) {
                    var res = JSON.parse(response);
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            text: res.message // Use message from PHP response
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: res.message || 'Failed to update card.'
                        });
                        // this
                        $(element).text($(element).data('original-value'));
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Something went wrong.'
                    });
                }
            });
        }
    });

    $('.editable').on('focus', function() {
        $(this).data('original-value', $(this).text());
    });

    // delete
    $('.delete-card-btn').on('click', function() {
        var cardId = $(this).data('id');
        
        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete-card.php',
                    method: 'POST',
                    data: {
                        action: 'delete_card',
                        card_id: cardId
                    },
                    success: function(response) {
                        var res = JSON.parse(response);
                        if (res.success) {
                            $('button[data-id="' + cardId + '"]').closest('tr').remove();
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: res.message
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: res.message || 'Failed to delete card.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Something went wrong.'
                        });
                    }
                });
            }
        });
    });
});