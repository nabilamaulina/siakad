</div> <footer class="footer-custom text-center py-3 mt-auto">
        <div class="container-fluid">
            <span>&copy; 2026 <span class="fw-bold">SOBAT IK</span> - Sistem Operasional & Basis Akademik Terpadu Ilmu Komputer. All Rights Reserved.</span>
        </div>
    </footer>
</div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Inisialisasi default jika menggunakan komponen datatables reguler di halaman lain
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('.datatable-init').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json"
                }
            });
        }
    });
</script>
</body>
</html>