<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Dashboard - University MIS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        /* Reset and Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }
        
        /* Sidebar Styles */
        .sidebar {
            min-height: 100vh;
            background: #2d3748;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #4a5568;
        }
        
        .sidebar-brand h4 {
            margin: 0;
            color: #fff;
        }
        
        .sidebar-brand h4 i {
            color: #667eea;
        }
        
        .sidebar-user {
            padding: 15px 20px;
            border-bottom: 1px solid #4a5568;
            text-align: center;
        }
        
        .sidebar-user i {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .sidebar-user .name {
            font-weight: bold;
            display: block;
        }
        
        .sidebar-user .role {
            color: #a0aec0;
            font-size: 0.8rem;
        }
        
        .sidebar-nav {
            padding: 10px 0;
        }
        
        .sidebar-nav .nav-link {
            color: #cbd5e0;
            padding: 10px 20px;
            border-radius: 0;
            transition: all 0.3s;
        }
        
        .sidebar-nav .nav-link:hover {
            background: #4a5568;
            color: #fff;
        }
        
        .sidebar-nav .nav-link.active {
            background: #667eea;
            color: #fff;
        }
        
        .sidebar-nav .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            width: calc(100% - 250px);
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 15px 20px;
        }
        
        .card-header h5 {
            margin: 0;
            color: #2d3748;
        }
        
        /* Stat Cards */
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2d3748;
        }
        
        .stat-card .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Student Avatar */
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-inactive {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-graduated {
            background: #d4edda;
            color: #155724;
        }
        
        /* Roll Badge */
        .roll-badge {
            font-family: 'Courier New', monospace;
            font-weight: 700;
            font-size: 13px;
            color: #2c3e50;
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        /* Table Actions */
        .table-actions .btn {
            padding: 4px 8px;
            font-size: 12px;
            margin: 0 2px;
        }
        
        /* Empty State */
        .empty-state {
            padding: 60px 0;
            text-align: center;
            color: #95a5a6;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
                margin-left: -200px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-toggle {
                display: block !important;
            }
        }
        
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            background: #2d3748;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<!-- Sidebar Toggle Button (Mobile) -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>