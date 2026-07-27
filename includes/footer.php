    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (toggle) toggle.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('active'); });
    if (overlay) overlay.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('active'); });

    // DataTables
    if ($.fn.dataTable) {
        $('table.dataTable').DataTable({ pageLength: 10, language: { search: "Search:", lengthMenu: "Show _MENU_", info: "Showing _START_-_END_ of _TOTAL_" } });
    }

    // Auto-dismiss alerts
    setTimeout(() => { document.querySelectorAll('.alert').forEach(el => { el.style.transition = 'opacity 0.5s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 600); }); }, 5000);
});
</script>
</body>
</html>
