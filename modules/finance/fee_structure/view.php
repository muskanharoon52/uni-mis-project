<?php
$pageTitle = 'Fee Structure Details';
include_once __DIR__ . '/../includes/header.php';

// =============================================
// MODULE DEPRECATION NOTICE
// =============================================
?>
<div style="margin-bottom:16px;">
    <a href="index.php" class="btn btn-ghost" style="font-size:.82rem;">&#8592; Back to Fee Structures</a>
</div>

<div class="card" style="max-width:600px; margin: 40px auto; text-align: center; padding: 40px;">
    <div style="font-size: 4rem; color: #f59e0b; margin-bottom: 15px;">
        <i class="fas fa-construction"></i>
    </div>
    <h3 style="color: #1f2937; margin-bottom: 10px;">Module Discontinued</h3>
    <p style="color: #6b7280; margin-bottom: 20px;">
        The <strong>Fee Structures</strong> module has been replaced. 
        <br>Please use the new <strong>Fee Heads</strong> module to manage fees and pricing.
    </p>
    
    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
        <a href="../fee_heads/index.php" class="btn btn-primary" style="background:#2563eb;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;">
            <i class="fas fa-arrow-right"></i> Go to Fee Heads
        </a>
        <a href="index.php" class="btn btn-outline" style="padding:10px 20px;border-radius:6px;text-decoration:none;border:1px solid #d1d5db;color:#374151;">
            Stay on Fee Structures
        </a>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>