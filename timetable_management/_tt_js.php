<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#select_all, #select_all_top').forEach(function(el) {
        if (el) el.addEventListener('change', function() {
            document.querySelectorAll('.student-cb').forEach(function(cb) { cb.checked = el.checked; });
        });
    });
    document.querySelectorAll('.student-cb').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const total = document.querySelectorAll('.student-cb').length;
            const checked = document.querySelectorAll('.student-cb:checked').length;
            document.querySelectorAll('#select_all, #select_all_top').forEach(function(el) {
                if (el) el.checked = total > 0 && checked === total;
            });
        });
    });
});
</script>
