<?php
// Start session for messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get application ID from session if it exists
$application_id = $_SESSION['application_id'] ?? null;
$student_name = $_SESSION['full_name'] ?? null;
$student_email = $_SESSION['email'] ?? null;

// Clear the session data after retrieving (so it doesn't show again on refresh)
// But keep the success message for display
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>University Admission - Online Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== MAIN STYLES ===== */
        * { box-sizing: border-box; }
        
        body { 
            background: linear-gradient(135deg, #e0e7ff 0%, #f0f4ff 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-wrapper {
            max-width: 850px;
            margin: 0 auto;
            background: white;
            padding: 35px 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
            position: relative;
            overflow: hidden;
        }
        
        .registration-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2563eb, #7c3aed, #2563eb);
            background-size: 200% 100%;
            animation: gradientMove 3s ease infinite;
        }
        
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* ===== HEADER ===== */
        .header {
            text-align: center;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 2px solid #f0f4ff;
        }
        
        .header .icon {
            font-size: 48px;
            color: #2563eb;
            margin-bottom: 10px;
        }
        
        .header h2 {
            color: #1a1a2e;
            font-weight: 700;
            font-size: 28px;
            margin: 0;
        }
        
        .header p {
            color: #6b7280;
            font-size: 15px;
            margin: 5px 0 0 0;
        }
        
        /* ===== FORM STYLES ===== */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
        
        .form-group label .required {
            color: #ef4444;
            margin-left: 2px;
        }
        
        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
            width: 100%;
            background: #fafbfc;
        }
        
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: white;
        }
        
        .form-control.is-valid {
            border-color: #10b981;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2310b981' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 16px;
        }
        
        .form-control.is-invalid {
            border-color: #ef4444;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23ef4444'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23ef4444' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px 16px;
        }
        
        .form-control.is-invalid:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
        
        .form-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        .form-text i {
            margin-right: 4px;
        }
        
        /* ===== SUBMIT BUTTON ===== */
        .btn-submit {
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: white;
            padding: 14px 30px;
            width: 100%;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-submit i {
            margin-right: 10px;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            border: none;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        
        .alert i {
            font-size: 20px;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 576px) {
            .registration-wrapper {
                padding: 20px;
                border-radius: 12px;
            }
            .header h2 { font-size: 22px; }
            .header .icon { font-size: 36px; }
            .btn-submit { font-size: 16px; padding: 12px 20px; }
            .form-control { font-size: 16px; padding: 10px 12px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="registration-wrapper">
        <!-- Header -->
        <div class="header">
            <div class="icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>University Admission Registration</h2>
            <p>Fill in the form below to apply for admission</p>
        </div>

        <!-- Display Error Messages -->
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= $_SESSION['error'] ?></div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Display Success Message (will be hidden by popup) -->
        <?php if(isset($_SESSION['success']) && !empty($application_id)): ?>
            <div class="alert alert-success" id="successMessage" style="display:none;">
                <i class="fas fa-check-circle"></i>
                <div><?= $_SESSION['success'] ?></div>
            </div>
            <?php 
            // Don't unset yet - popup needs it
            ?>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="process_registration.php" method="POST" id="registrationForm" novalidate>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="full_name" 
                               placeholder="Enter your full name" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Father's Name <span class="required">*</span></label>
                        <input type="text" class="form-control" name="father_name" 
                               placeholder="Enter father's name" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>CNIC (13 digits) <span class="required">*</span></label>
                        <input type="text" class="form-control" name="cnic" 
                               pattern="[0-9]{13}" maxlength="13" 
                               placeholder="4210112345678" 
                               inputmode="numeric" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Enter 13 digits without dashes
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Date of Birth <span class="required">*</span></label>
                        <input type="date" class="form-control" name="dob" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Gender <span class="required">*</span></label>
                        <select class="form-control" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="tel" class="form-control" name="phone" 
                               pattern="[0-9]{11}" maxlength="11" 
                               placeholder="03001234567" 
                               inputmode="numeric" required>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> 11 digits starting with 03
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" class="form-control" name="email" 
                       placeholder="student@email.com" 
                       inputmode="email" required>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Previous Degree <span class="required">*</span></label>
                        <select class="form-control" name="previous_degree" required>
                            <option value="">Select Degree</option>
                            <option value="F.Sc (Pre-Engineering)">F.Sc (Pre-Engineering)</option>
                            <option value="F.Sc (Pre-Medical)">F.Sc (Pre-Medical)</option>
                            <option value="ICS">ICS</option>
                            <option value="I.Com">I.Com</option>
                            <option value="A-Levels">A-Levels</option>
                            <option value="D.A.E">D.A.E</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Program Applying For <span class="required">*</span></label>
                        <select class="form-control" name="program" required>
                            <option value="">Select Program</option>
                            <option value="BS Computer Science">BS Computer Science</option>
                            <option value="BS Software Engineering">BS Software Engineering</option>
                            <option value="BS Artificial Intelligence">BS Artificial Intelligence</option>
                            <option value="BBA (Hons)">BBA (Hons)</option>
                            <option value="BS Mathematics">BS Mathematics</option>
                            <option value="BS English">BS English</option>
                            <option value="BS Economics">BS Economics</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Obtained Marks <span class="required">*</span></label>
                        <input type="number" class="form-control" name="obtained_marks" 
                               step="0.01" min="0" 
                               placeholder="e.g., 850" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Total Marks <span class="required">*</span></label>
                        <input type="number" class="form-control" name="total_marks" 
                               step="0.01" min="1" 
                               placeholder="e.g., 1100" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <input type="text" class="form-control" name="address" 
                       placeholder="Enter your residential address">
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Password <span class="required">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control" name="password" 
                                   id="password" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" 
                                    onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="passwordToggle"></i>
                            </button>
                        </div>
                        <div class="form-text">
                            <i class="fas fa-info-circle"></i> Minimum 6 characters
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Confirm Password <span class="required">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" 
                               id="confirm_password" minlength="6" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Submit Application
            </button>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- SUCCESS POPUP / MODAL                        -->
<!-- ============================================ -->
<?php if(isset($_SESSION['success']) && !empty($application_id)): ?>
<div id="successModal" style="display:flex;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;justify-content:center;align-items:center;backdrop-filter:blur(4px);animation:fadeIn 0.3s ease;">
    <div style="background:white;border-radius:20px;padding:40px;max-width:500px;width:90%;text-align:center;position:relative;animation:slideUp 0.4s ease;box-shadow:0 30px 60px rgba(0,0,0,0.3);max-height:90vh;overflow-y:auto;">
        <!-- Close button -->
        <button onclick="closeSuccessPopup()" style="position:absolute;top:15px;right:20px;background:none;border:none;font-size:24px;color:#9ca3af;cursor:pointer;transition:color 0.2s;">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Success Icon with Animation -->
        <div style="width:100px;height:100px;background:linear-gradient(135deg,#10b981,#059669);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;animation:scaleIn 0.5s ease 0.2s both;">
            <i class="fas fa-check" style="font-size:50px;color:white;"></i>
        </div>
        
        <h2 style="color:#1a1a2e;font-weight:700;margin-bottom:8px;">Registration Successful! 🎉</h2>
        <p style="color:#6b7280;font-size:16px;margin-bottom:20px;">
            Your application has been submitted successfully.
        </p>
        
        <!-- Student Name -->
        <?php if($student_name): ?>
        <div style="background:#f8fafc;border-radius:12px;padding:12px;margin-bottom:15px;">
            <div style="font-size:13px;color:#6b7280;">Student Name</div>
            <div style="font-size:18px;font-weight:600;color:#1a1a2e;"><?= htmlspecialchars($student_name) ?></div>
        </div>
        <?php endif; ?>
        
        <!-- Application ID -->
        <div style="background:#f0f4ff;border-radius:12px;padding:15px;margin-bottom:20px;">
            <div style="font-size:13px;color:#6b7280;">Application ID</div>
            <div style="font-size:24px;font-weight:700;color:#2563eb;letter-spacing:1px;">
                <?= htmlspecialchars($application_id) ?>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-top:4px;">
                <i class="fas fa-info-circle"></i> Save this ID for future reference
            </div>
        </div>
        
        <!-- Email -->
        <?php if($student_email): ?>
        <div style="background:#f8fafc;border-radius:12px;padding:12px;margin-bottom:20px;">
            <div style="font-size:13px;color:#6b7280;">Confirmation Email Sent To</div>
            <div style="font-size:15px;font-weight:500;color:#1a1a2e;"><?= htmlspecialchars($student_email) ?></div>
        </div>
        <?php endif; ?>
        
        <!-- Next Steps -->
        <div style="text-align:left;background:#f8fafc;border-radius:12px;padding:15px;margin-bottom:25px;">
            <div style="font-weight:600;color:#1a1a2e;margin-bottom:8px;">📌 Next Steps:</div>
            <ul style="margin:0;padding-left:20px;color:#4b5563;font-size:14px;">
                <li style="margin-bottom:4px;">Keep your <strong>Application ID</strong> for future reference</li>
                <li style="margin-bottom:4px;">Wait for admin approval (you'll receive an email)</li>
                <li>Check your email for updates on your application status</li>
            </ul>
        </div>
        
        <!-- Action Buttons -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
            <button onclick="closeSuccessPopup()" style="padding:12px 30px;background:#2563eb;color:white;border:none;border-radius:10px;font-weight:600;cursor:pointer;transition:all 0.3s;flex:1;min-width:140px;">
                <i class="fas fa-check"></i> OK, Got it!
            </button>
            <button onclick="window.print()" style="padding:12px 30px;background:#f3f4f6;color:#374151;border:2px solid #e5e7eb;border-radius:10px;font-weight:600;cursor:pointer;transition:all 0.3s;flex:1;min-width:140px;">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>
</div>

<?php 
// Clear session data after displaying popup
unset($_SESSION['success']);
unset($_SESSION['application_id']);
unset($_SESSION['full_name']);
unset($_SESSION['email']);
endif; 
?>

<!-- ============================================ -->
<!-- JAVASCRIPT                                   -->
<!-- ============================================ -->
<script>
// ===== Toggle Password Visibility =====
function togglePassword(id) {
    const input = document.getElementById(id);
    const toggle = document.getElementById('passwordToggle');
    
    if (input.type === 'password') {
        input.type = 'text';
        toggle.classList.remove('fa-eye');
        toggle.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        toggle.classList.remove('fa-eye-slash');
        toggle.classList.add('fa-eye');
    }
}

// ===== Close Success Popup =====
function closeSuccessPopup() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// ===== Close on outside click =====
document.addEventListener('click', function(e) {
    const modal = document.getElementById('successModal');
    if (modal && e.target === modal) {
        closeSuccessPopup();
    }
});

// ===== Close on Escape key =====
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSuccessPopup();
    }
});

// ===== Form Validation =====
document.getElementById('registrationForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        document.getElementById('confirm_password').focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
});

// ===== Real-time Validation =====
document.querySelectorAll('.form-control').forEach(input => {
    input.addEventListener('blur', function() {
        if (this.value.length > 0) {
            if (this.checkValidity()) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        }
    });
});

// ===== Auto-format CNIC and Phone =====
document.querySelectorAll('input[pattern]').forEach(input => {
    input.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
});

// ===== Password Match Validation =====
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;
    
    if (password !== confirm && confirm.length > 0) {
        this.classList.add('is-invalid');
        this.classList.remove('is-valid');
    } else if (confirm.length > 0) {
        this.classList.remove('is-invalid');
        this.classList.add('is-valid');
    }
});

// ===== Animations =====
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateY(30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes scaleIn {
        from { transform: scale(0); }
        to { transform: scale(1); }
    }
`;
document.head.appendChild(style);
</script>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</body>
</html>