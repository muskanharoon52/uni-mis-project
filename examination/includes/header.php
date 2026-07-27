<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

function getGradeColor($grade) {
    switch ($grade) {
        case 'A': return 'bg-success';
        case 'B': return 'bg-primary';
        case 'C': return 'bg-warning';
        case 'D': return 'bg-info';
        case 'F': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Examination' ?> | University MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; }
        
        .main-container { display: flex; min-height: 100vh; }
        
        .content-area {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 20px; }
        .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 15px 20px; }
        .card-header h5 { margin: 0; color: #2d3748; }
        
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); text-align: center; transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card .stat-number { font-size: 2rem; font-weight: bold; color: #2d3748; }
        .stat-card .stat-label { color: #666; font-size: 14px; }
        .stat-card .stat-icon { font-size: 2rem; margin-bottom: 10px; }
        .stat-card-primary .stat-icon { color: #667eea; }
        .stat-card-success .stat-icon { color: #28a745; }
        .stat-card-warning .stat-icon { color: #ffc107; }
        .stat-card-info .stat-icon { color: #17a2b8; }
        
        .table-actions .btn { padding: 4px 8px; font-size: 12px; margin: 0 2px; }
        
        .badge-exam-mid { background: #ffc107; color: #212529; }
        .badge-exam-final { background: #dc3545; color: #fff; }
        .badge-exam-quiz { background: #17a2b8; color: #fff; }
        .badge-exam-lab { background: #6f42c1; color: #fff; }
        
        .badge-grade-A { background: #28a745; color: #fff; }
        .badge-grade-B { background: #007bff; color: #fff; }
        .badge-grade-C { background: #ffc107; color: #212529; }
        .badge-grade-D { background: #17a2b8; color: #fff; }
        .badge-grade-F { background: #dc3545; color: #fff; }
        
        .timeline-item { padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .timeline-item:last-child { border-bottom: none; }
        .timeline-item .time { font-size: 12px; color: #999; margin-bottom: 5px; }
        .timeline-item .title { font-weight: 600; color: #333; }
        .timeline-item .description { font-size: 13px; color: #666; margin-top: 5px; }
        
        @media (max-width: 768px) {
            .content-area { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="main-container">
