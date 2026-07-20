$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Auto-dismiss alerts
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
    
    // Sidebar toggle for mobile
    $('#sidebarToggle').on('click', function() {
        $('.sidebar').toggleClass('active');
    });
    
    // Confirm delete
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        if (confirm('Are you sure you want to delete this item?')) {
            window.location.href = $(this).attr('href');
        }
    });
    
    // Search filter for tables
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $(this).closest('.table-responsive').find('table tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});

// Toast notification function
function showToast(message, type = 'success') {
    var toast = `
        <div class="toast align-items-center text-white bg-${type} border-0 show" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    var container = $('#toastContainer');
    if (container.length === 0) {
        container = $('<div id="toastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>');
        $('body').append(container);
    }
    
    container.append(toast);
    setTimeout(function() {
        container.find('.toast:last').toast('hide');
    }, 5000);
}

// Loading overlay
function showLoading() {
    $('.spinner-overlay').addClass('active');
}

function hideLoading() {
    $('.spinner-overlay').removeClass('active');
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    var rows = $('#' + tableId + ' tr');
    var csv = [];
    
    rows.each(function(rowIndex) {
        var row = [];
        $(this).find('th, td').each(function() {
            var text = $(this).text().trim();
            // Remove commas and quotes
            text = text.replace(/"/g, '""');
            row.push('"' + text + '"');
        });
        csv.push(row.join(','));
    });
    
    var csvContent = csv.join('\n');
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    var url = URL.createObjectURL(blob);
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Print function
function printContent(elementId) {
    var content = document.getElementById(elementId).innerHTML;
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write(content);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Date formatting
function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    var date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Status badge class
function getStatusBadgeClass(status) {
    var classes = {
        'active': 'success',
        'inactive': 'secondary',
        'pending': 'warning',
        'approved': 'success',
        'rejected': 'danger',
        'present': 'success',
        'absent': 'danger',
        'late': 'warning',
        'excused': 'info',
        'enrolled': 'primary',
        'completed': 'success',
        'dropped': 'danger',
        'failed': 'danger'
    };
    return classes[status.toLowerCase()] || 'secondary';
}

// Validate form
function validateForm(formId) {
    var valid = true;
    $('#' + formId + ' [required]').each(function() {
        if ($(this).val() === '') {
            $(this).addClass('is-invalid');
            valid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    return valid;
}

// Initialize DataTable with common settings
function initDataTable(tableId, options = {}) {
    var defaultOptions = {
        responsive: true,
        pageLength: 10,
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)"
        }
    };
    
    var settings = $.extend({}, defaultOptions, options);
    return $('#' + tableId).DataTable(settings);
}