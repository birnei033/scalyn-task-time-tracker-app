<x-modal name="delete-confirmation-modal" maxWidth="md">
    <form id="delete-confirmation-form" method="POST" action="">
        @csrf
        <input type="hidden" name="_method" value="DELETE" id="delete-confirmation-method">
        <input type="hidden" name="return_to" value="" id="delete-confirmation-return-to">

        <div class="modal-header">
            <div>
                <div class="section-kicker mb-1">Confirm deletion</div>
                <h2 class="modal-title fs-5 mb-0" id="delete-confirmation-title">Delete item</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
            <p class="muted-copy mb-0" id="delete-confirmation-message">
                Are you sure you want to delete this item? This action cannot be undone.
            </p>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                Cancel
            </button>
            <button type="submit" class="btn btn-danger" id="delete-confirmation-submit" data-loading-text="Deleting...">
                <i class="bi bi-trash me-1"></i>
                Delete
            </button>
        </div>
    </form>
</x-modal>
