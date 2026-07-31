<script>
document.addEventListener('DOMContentLoaded', function() {
    const selects = ['#select_all', '#select_all_top'];
    selects.forEach(function(sel) {
        const el = document.querySelector(sel);
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
function requireStudentSelection() {
    const checked = document.querySelectorAll('.student-cb:checked').length;
    const hidden = document.querySelectorAll('input[name="student_ids[]"][type="hidden"]').length;
    if (checked === 0 && hidden === 0) {
        alert('Please select at least one student first.');
        return false;
    }
    return true;
}
</script>
