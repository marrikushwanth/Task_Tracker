<?php
/**
 * Shared Application Footer
 * Task Tracker - RTIH Technical Assignment
 */
?>
            </main>

            <!-- Bottom Application Footer -->
            <footer class="app-footer mt-auto py-3 px-3 px-lg-4 bg-white border-top text-muted small">
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                    <div>
                        <strong>TaskFlow</strong> &bull; RTIH (Ratan Tata Innovation Hub) Internship Technical Assignment
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span><i class="bi bi-shield-check text-success me-1"></i>PDO Secure</span>
                        <span><i class="bi bi-lock-fill text-primary me-1"></i>CSRF Protected</span>
                        <span class="text-secondary">&copy; <?= date('Y') ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Toast Notification Container for AJAX Events -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index: 1090;"></div>

    <!-- Bootstrap 5 Bundle JS (Includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Application JS -->
    <script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>

