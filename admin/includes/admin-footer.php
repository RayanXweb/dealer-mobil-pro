            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Custom Admin JS -->
    <script src="<?= ASSETS_URL ?>js/admin.js?v=<?= time() ?>"></script>
    
    <script>
    // Auto-close alerts
    setTimeout(function() {
        document.querySelectorAll('.alert-dismissible').forEach(function(el) {
            var alert = new bootstrap.Alert(el);
            alert.close();
        });
    }, 5000);
    </script>
</body>
</html>
