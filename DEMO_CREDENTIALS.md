# Demo Credentials - University MIS

| Role | Username | Password | Module Access URL |
|------|----------|----------|-------------------|
| SSO Admin | sso_admin | password123 | `modules/sso/login.php` → `dashboard.php` |
| Exam Officer | exam_admin | password123 | `modules/examination/login.php` → `examination/dashboard.php` |
| Finance Officer | finance_admin | password123 | `modules/finance/login.php` → `dashboard.php` |
| Student | student_demo | password123 | `modules/sso/login.php` (student portal) |
| Teacher | teacher_demo | password123 | `modules/sso/login.php` (teacher portal) |

## How to Use

1. Visit your module's login page:
   - **SSO**: `http://localhost/uni-mis-project/modules/sso/login.php`
   - **Examination**: `http://localhost/uni-mis-project/modules/examination/login.php`
   - **Finance**: `http://localhost/uni-mis-project/modules/finance/login.php`
   - **MIS Portal**: `http://localhost/uni-mis-project/mis.php`

2. Use the credentials above to log in.
3. You will be redirected to the module dashboard on successful authentication.

## Database Setup

Ensure these demo user accounts exist in the `university_mis` database with proper role assignments (role_id). The shared connection file is at `config/db_connect.php`.