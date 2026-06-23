<x-modal name="attachment-preview-modal" maxWidth="xl">
    <div class="modal-header">
        <div>
            <div class="section-kicker mb-1">Attachment Preview</div>
            <h2 class="modal-title fs-5 mb-0" data-attachment-preview-title>Attachment preview</h2>
        </div>
        <button type="button" class="btn-close" data-swal-close aria-label="Close"></button>
    </div>

    <div class="modal-body">
        <div class="attachment-preview-meta mb-3">
            <div class="d-flex flex-wrap gap-3 small text-muted">
                <div><strong class="text-body">Size:</strong> <span data-attachment-preview-size>--</span></div>
                <div><strong class="text-body">Type:</strong> <span data-attachment-preview-mime>--</span></div>
                <div><strong class="text-body">Uploaded by:</strong> <span data-attachment-preview-uploader>--</span></div>
            </div>
        </div>

        <div class="attachment-preview-stage">
            <img
                class="attachment-preview-image d-none"
                data-attachment-preview-image
                alt="Attachment preview"
            >

            <iframe
                class="attachment-preview-frame d-none"
                data-attachment-preview-frame
                title="Attachment preview"
                loading="lazy"
            ></iframe>

            <div class="attachment-preview-fallback d-none" data-attachment-preview-fallback>
                <div class="attachment-preview-fallback-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <h3 class="h5 mb-2">This file cannot be previewed inline</h3>
                <p class="muted-copy mb-4">
                    Some document types are best opened or downloaded directly.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="#" target="_blank" rel="noopener" data-attachment-preview-download>
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-modal>
