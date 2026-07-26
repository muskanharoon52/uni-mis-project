// ============================================
// EXAMINATION MODULE JAVASCRIPT
// ============================================

// ---------- SIDEBAR FUNCTIONALITY ----------
(function() {
    'use strict';
    
    // Wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const contentArea = document.getElementById('contentArea');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        // Only proceed if elements exist
        if (!sidebar || !toggleBtn) return;
        
        // Toggle sidebar function
        function toggleSidebar() {
            const isOpen = sidebar.classList.contains('open');
            
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
            if (contentArea) contentArea.classList.toggle('shifted');
            
            // Save state to localStorage
            localStorage.setItem('sidebarOpen', !isOpen);
            
            // Update button aria-label
            toggleBtn.setAttribute('aria-expanded', !isOpen);
        }
        
        // Open sidebar
        function openSidebar() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            if (contentArea) contentArea.classList.add('shifted');
            localStorage.setItem('sidebarOpen', 'true');
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
        
        // Close sidebar
        function closeSidebar() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            if (contentArea) contentArea.classList.remove('shifted');
            localStorage.setItem('sidebarOpen', 'false');
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
        
        // Toggle on button click
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
        
        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', closeSidebar);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+B to toggle sidebar
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                toggleSidebar();
            }
            // Escape to close
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
        
        // Restore state from localStorage
        const savedState = localStorage.getItem('sidebarOpen');
        if (savedState === 'true' && window.innerWidth > 768) {
            openSidebar();
        }
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth <= 768) {
                    // On mobile, close sidebar if open
                    if (sidebar.classList.contains('open')) {
                        // Keep open on mobile if user wants
                    }
                } else if (window.innerWidth > 768) {
                    // On desktop, restore saved state
                    const saved = localStorage.getItem('sidebarOpen');
                    if (saved === 'true' && !sidebar.classList.contains('open')) {
                        openSidebar();
                    } else if (saved === 'false' && sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                }
            }, 250);
        });
    });
})();

// ---------- SUBMENU TOGGLE ----------
function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    if (submenu) {
        submenu.classList.toggle('open');
        const icon = element.querySelector('.bi-chevron-down, .bi-chevron-up');
        if (icon) {
            icon.classList.toggle('bi-chevron-down');
            icon.classList.toggle('bi-chevron-up');
        }
    }
}

// ---------- CONFIRM DELETE ----------
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// ---------- TOGGLE VISIBILITY ----------
function toggleVisibility(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.display = element.style.display === 'none' ? 'block' : 'none';
    }
}

// ---------- FORMAT DATE ----------
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// ---------- FORMAT TIME ----------
function formatTime(timeString) {
    const time = new Date('2000-01-01T' + timeString);
    return time.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// ---------- CALCULATE GRADE ----------
function calculateGrade(marks, total) {
    const percentage = (marks / total) * 100;
    if (percentage >= 90) return 'A';
    if (percentage >= 80) return 'B';
    if (percentage >= 70) return 'C';
    if (percentage >= 60) return 'D';
    return 'F';
}

// ---------- GET GRADE COLOR ----------
function getGradeColor(grade) {
    const colors = {
        'A': 'success',
        'B': 'primary',
        'C': 'warning',
        'D': 'info',
        'F': 'danger'
    };
    return colors[grade] || 'secondary';
}

// ---------- LOAD STUDENTS VIA AJAX ----------
function loadStudents(programId, targetElement) {
    if (!programId) {
        targetElement.innerHTML = '<option value="">Select Program First</option>';
        return;
    }
    
    fetch(`../ajax/get_students.php?program_id=${programId}`)
        .then(response => response.json())
        .then(data => {
            targetElement.innerHTML = '<option value="">Select Student</option>';
            data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.student_id;
                option.textContent = `${student.full_name} (${student.student_id})`;
                targetElement.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading students:', error);
            targetElement.innerHTML = '<option value="">Error loading students</option>';
        });
}

// ---------- LOAD COURSES VIA AJAX ----------
function loadCourses(programId, targetElement) {
    if (!programId) {
        targetElement.innerHTML = '<option value="">Select Program First</option>';
        return;
    }
    
    fetch(`../ajax/get_courses.php?program_id=${programId}`)
        .then(response => response.json())
        .then(data => {
            targetElement.innerHTML = '<option value="">Select Course</option>';
            data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = `${course.course_code} - ${course.course_name}`;
                targetElement.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading courses:', error);
            targetElement.innerHTML = '<option value="">Error loading courses</option>';
        });
}

// ---------- CONFIRM ACTION ----------
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to perform this action?');
}

// ---------- SHOW LOADING ----------
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading...</p>
            </div>
        `;
    }
}

// ---------- FORMAT CURRENCY ----------
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// ---------- GET STATUS BADGE CLASS ----------
function getStatusBadgeClass(status) {
    const classes = {
        'active': 'bg-success',
        'inactive': 'bg-danger',
        'pending': 'bg-warning',
        'confirmed': 'bg-info',
        'graduated': 'bg-secondary',
        'draft': 'bg-secondary',
        'published': 'bg-success'
    };
    return classes[status] || 'bg-secondary';
}

// ---------- GET EXAM TYPE BADGE CLASS ----------
function getExamTypeBadgeClass(type) {
    const classes = {
        'final': 'bg-danger',
        'mid': 'bg-warning',
        'quiz': 'bg-info',
        'lab': 'bg-purple'
    };
    return classes[type] || 'bg-secondary';
}

// ---------- PRINT PAGE ----------
function printPage() {
    window.print();
}

// ---------- EXPORT TO CSV ----------
function exportToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error('Table not found');
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let data = cols[j].innerText.replace(/,/g, '');
            row.push(data);
        }
        
        csv.push(row.join(','));
    }
    
    const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.download = filename || 'export.csv';
    downloadLink.href = window.URL.createObjectURL(csvFile);
    downloadLink.click();
}

// ---------- DOCUMENT READY ----------
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        }, 5000);
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
// Confirm delete action
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}
/**
 * Sidebar Toggle Functionality
 * Handles slide animation, keyboard shortcuts, and state persistence
 */
(function() {
    'use strict';
    
    // Wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const contentArea = document.getElementById('contentArea');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        // Only proceed if elements exist
        if (!sidebar || !toggleBtn) {
            console.warn('Sidebar elements not found. Make sure sidebar exists in HTML.');
            return;
        }
        
        console.log('✅ Sidebar initialized successfully');
        
        // Toggle sidebar function
        function toggleSidebar() {
            const isOpen = sidebar.classList.contains('open');
            
            // Toggle classes
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
            if (contentArea) contentArea.classList.toggle('shifted');
            
            // Save state to localStorage
            localStorage.setItem('sidebarOpen', !isOpen);
            
            // Update button aria-label
            toggleBtn.setAttribute('aria-expanded', !isOpen);
            
            // Update button icon (optional)
            updateButtonIcon(!isOpen);
        }
        
        // Open sidebar
        function openSidebar() {
            sidebar.classList.add('open');
            if (overlay) overlay.classList.add('active');
            if (contentArea) contentArea.classList.add('shifted');
            localStorage.setItem('sidebarOpen', 'true');
            toggleBtn.setAttribute('aria-expanded', 'true');
            updateButtonIcon(true);
        }
        
        // Close sidebar
        function closeSidebar() {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            if (contentArea) contentArea.classList.remove('shifted');
            localStorage.setItem('sidebarOpen', 'false');
            toggleBtn.setAttribute('aria-expanded', 'false');
            updateButtonIcon(false);
        }
        
        // Update button icon (optional - for visual feedback)
        function updateButtonIcon(isOpen) {
            const bars = toggleBtn.querySelectorAll('.bar');
            if (isOpen) {
                // Transform to X when open
                if (bars.length === 3) {
                    bars[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                    bars[1].style.opacity = '0';
                    bars[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
                }
            } else {
                // Reset to hamburger when closed
                bars.forEach(bar => {
                    bar.style.transform = '';
                    bar.style.opacity = '';
                });
            }
        }
        
        // Event Listeners
        // Toggle on button click
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
        
        // Close sidebar when clicking overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                }
            });
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+B to toggle sidebar
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                toggleSidebar();
            }
            // Escape to close
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });
        
        // Restore state from localStorage
        const savedState = localStorage.getItem('sidebarOpen');
        const isDesktop = window.innerWidth > 768;
        
        if (savedState === 'true' && isDesktop) {
            setTimeout(function() {
                openSidebar();
            }, 100);
        } else if (savedState === 'false' || !isDesktop) {
            closeSidebar();
        }
        
        // Handle window resize
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                const isDesktopNow = window.innerWidth > 768;
                
                if (!isDesktopNow) {
                    if (sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                } else {
                    const saved = localStorage.getItem('sidebarOpen');
                    if (saved === 'true' && !sidebar.classList.contains('open')) {
                        openSidebar();
                    } else if (saved === 'false' && sidebar.classList.contains('open')) {
                        closeSidebar();
                    }
                }
            }, 250);
        });
        
        // Touch swipe support for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        document.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        document.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeDistance = touchStartX - touchEndX;
            const isOpen = sidebar.classList.contains('open');
            
            // Swipe right to open (from left edge)
            if (swipeDistance < -50 && touchStartX < 30 && !isOpen) {
                openSidebar();
            }
            
            // Swipe left to close
            if (swipeDistance > 50 && isOpen) {
                closeSidebar();
            }
        }
    });
})();

// Submenu toggle function
function toggleSubmenu(element) {
    const submenu = element.nextElementSibling;
    if (submenu) {
        submenu.classList.toggle('open');
        const icon = element.querySelector('.bi-chevron-down, .bi-chevron-up');
        if (icon) {
            icon.classList.toggle('bi-chevron-down');
            icon.classList.toggle('bi-chevron-up');
        }
    }
}