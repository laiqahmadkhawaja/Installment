        </main>
        <!-- Footer -->
        <footer class="mt-auto py-3 px-4 bg-body border-top text-center text-muted small">
            <div class="container-fluid d-flex flex-column flex-sm-row justify-content-between align-items-center">
                <span>&copy; <?= date('Y') ?> <strong><?= get_setting('company_name', 'Installment Management System') ?></strong>. All rights reserved.</span>
                <span class="mt-1 mt-sm-0">Enterprise Installment & Credit Management System (PHP / MySQL)</span>
            </div>
        </footer>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Application Scripts -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
