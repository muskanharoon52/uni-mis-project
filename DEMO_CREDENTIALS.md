# Demo Credentials - University MIS

All modules share the `university_mis` database `users` table.

## Main Users (shared across SSO, Examination, Finance, Admission)

| Role | Username | Password | Module Login |
|------|----------|----------|--------------|
| Admin | admin | admin123 | SSO / Admission |
| SSO Admin | sso_admin | password123 | `modules/sso/login.php` |
| Examiner | exam_admin | password123 | `modules/examination/login.php` |
| Examiner | examiner | examiner123 | `modules/examination/login.php` |
| Finance Officer | finance | password123 | `modules/finance/login.php` |
| Teacher | teacher | password123 | SSO / Examination |
| Student | student | password123 | SSO / Examination |

## LMS Users (login via User ID, not username)

| Role | User ID | Password | Login |
|------|---------|----------|-------|
| Teacher | 5001 | teacher123 | `modules/lms/public/login.php` |
| Teacher | 5002 | teacher123 | `modules/lms/public/login.php` |
| Student | 9001 | student123 | `modules/lms/public/login.php` |
| Student | 9002 | student123 | `modules/lms/public/login.php` |

## SBE Users (login via User ID)

| Role | User ID | Password | Login |
|------|---------|----------|-------|
| Teacher | 5001 | teacher123 | `modules/sbe/login.php` |
| Teacher | 5002 | teacher123 | `modules/sbe/login.php` |
| Student | 9001 | student123 | `modules/sbe/login.php` |
| Student | 9002 | student123 | `modules/sbe/login.php` |

## Login URLs

- **Home**: `http://localhost/uni-mis-project/`
- **LMS**: `http://localhost/uni-mis-project/modules/lms/public/login.php`
- **SBE**: `http://localhost/uni-mis-project/modules/sbe/login.php`
- **Admission**: `http://localhost/uni-mis-project/modules/admission/auth/login.php`
- **Examination**: `http://localhost/uni-mis-project/modules/examination/login.php`
- **SSO**: `http://localhost/uni-mis-project/modules/sso/login.php`
- **Finance**: `http://localhost/uni-mis-project/modules/finance/login.php`
