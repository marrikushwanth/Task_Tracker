<?php
/**
 * Reusable Task Deletion Confirmation Modal
 * Task Tracker - RTIH Technical Assignment
 */
?>
<div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center px-4 pt-0 pb-4">
                <div class="delete-modal-icon mx-auto mb-3 text-danger bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px;">
                    <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                </div>
                <h3 class="h5 fw-bold text-slate-800 mb-2" id="deleteTaskModalLabel">Delete Task</h3>
                <p class="text-muted small mb-3">
                    Are you sure you want to permanently delete <strong id="deleteModalTaskTitle" class="text-slate-900">this task</strong>? This action cannot be undone.
                </p>

                <form action="<?= BASE_URL ?>/tasks/delete.php" method="POST" id="modalDeleteForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" id="deleteModalTaskId" value="">

                    <div class="d-flex justify-content-center gap-2 pt-2">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 fw-semibold shadow-sm" id="confirmDeleteSubmitBtn">
                            <i class="bi bi-trash-fill me-1"></i> Yes, Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

