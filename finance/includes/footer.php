</div> <!-- End main-content -->

<script>
function toggleSubMenu(event) {
    event.preventDefault();
    var subMenu = document.getElementById('studentFeeSubMenu');
    var arrow = event.currentTarget.querySelector('.arrow');
    
    // Toggle sub-menu
    subMenu.classList.toggle('show');
    
    // Toggle arrow rotation
    if (arrow) {
        arrow.classList.toggle('open');
    }
}

// Close sub-menu when clicking outside
document.addEventListener('click', function(event) {
    var sidebar = document.getElementById('sidebar');
    var target = event.target;
    
    // Check if click is outside sidebar
    if (sidebar && !sidebar.contains(target)) {
        var subMenu = document.getElementById('studentFeeSubMenu');
        var arrow = document.querySelector('.nav-link .arrow');
        
        if (subMenu) {
            subMenu.classList.remove('show');
        }
        if (arrow) {
            arrow.classList.remove('open');
        }
    }
});

// Keep sub-menu open when navigating inside it
document.addEventListener('DOMContentLoaded', function() {
    var currentFolder = '<?php echo basename(dirname($_SERVER['PHP_SELF'])); ?>';
    if (currentFolder === 'student_fee') {
        var subMenu = document.getElementById('studentFeeSubMenu');
        var arrow = document.querySelector('.nav-link .arrow');
        if (subMenu) {
            subMenu.classList.add('show');
        }
        if (arrow) {
            arrow.classList.add('open');
        }
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>