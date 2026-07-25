-- =====================================================================
-- UNIVERSITY MIS - MAIN/CENTRAL DATABASE
-- Merges: Admission, Finance, SSO, Examination, SBE, Login modules
-- Engine : InnoDB | Charset: utf8mb4
-- Author : Prepared for Admission Office lead (main DB integrator)
-- =====================================================================
-- HOW THIS FILE IS ORGANIZED
--  0. Database creation
--  1. SHARED / CORE tables      (used by every module)
--  2. LOGIN / USERS module      (staff accounts - fills the gap in every
--                                 member's design, since all modules
--                                 mentioned "Login module" but nobody
--                                 actually built it)
--  3. ADMISSION module
--  4. SSO module
--  5. FINANCE module
--  6. EXAMINATION module
--  7. SBE (system based examination) module
--  8. CROSS-MODULE TRIGGERS / STORED PROCEDURES (business rules)
--  9. USEFUL VIEWS
-- =====================================================================
-- NAMING FIXES vs the drafts you were sent (so nothing collides in ONE db):
--  * SSO's student-request table       -> renamed sso_applications
--    (was "applications", clashed conceptually with admission form)
--  * Examination module's "exams"      -> renamed examinations
--    (was "exams", clashed with SBE's own "exams" table)
--  * SBE tables all prefixed sbe_      (matches how member 5 already
--    documented them, and keeps her "local, isolated" layer intact)
--  * "program" (admission wording) and "department" (SSO wording) are
--    the SAME real-world thing (BSCS/BSSE/BSIT/BSAI) -> unified into
--    one shared table: departments
-- =====================================================================

CREATE DATABASE IF NOT EXISTS university_mis
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE university_mis;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. SHARED / CORE TABLES
-- =====================================================================

-- Departments = Programs (BSCS, BSSE, BSIT, BSAI ...)
CREATE TABLE departments (
    department_id   INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,          -- e.g. BSCS
    department_code VARCHAR(20)  NOT NULL UNIQUE,    -- e.g. CS
    duration_years  TINYINT      NOT NULL DEFAULT 4,
    total_semesters TINYINT      NOT NULL DEFAULT 8,
    status          ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sessions / Intake batches (Fall 2026, Spring 2027 ...)
CREATE TABLE sessions (
    session_id   INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(50) NOT NULL UNIQUE,        -- e.g. Fall 2026
    start_date   DATE NULL,
    end_date     DATE NULL,
    status       ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Semesters belong to a department, numbered so promotion logic works
CREATE TABLE semesters (
    semester_id     INT AUTO_INCREMENT PRIMARY KEY,
    semester_name   VARCHAR(50) NOT NULL,             -- Semester 1
    semester_number TINYINT     NOT NULL,             -- 1,2,3...8  (needed to detect "final semester")
    department_id   INT NOT NULL,
    CONSTRAINT fk_sem_dept FOREIGN KEY (department_id) REFERENCES departments(department_id),
    UNIQUE KEY uq_dept_sem (department_id, semester_number)
) ENGINE=InnoDB;

-- =====================================================================
-- 2. LOGIN / USERS MODULE (staff side - admission, finance, sso, exam, admin)
-- Every module referenced "Login module" but never defined it centrally.
-- =====================================================================
CREATE TABLE roles (
    role_id   INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE   -- SuperAdmin, AdmissionOfficer, FinanceOfficer, SSOStaff, ExamOfficer, Teacher
) ENGINE=InnoDB;

CREATE TABLE users (
    user_id       INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(150) NOT NULL,
    username      VARCHAR(100) NOT NULL UNIQUE,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id       INT NOT NULL,
    department_id INT NULL,                 -- for teachers / dept-scoped staff
    login_id      VARCHAR(20) NULL UNIQUE,  -- LMS login ID (e.g. 5001, 9001)
    profile_photo VARCHAR(255) NULL,        -- LMS profile photo path
    status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    last_login_at DATETIME NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id),
    CONSTRAINT fk_users_dept FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 3. ADMISSION MODULE
-- Flow: form submitted -> temporary number -> (review) -> first payment
--       received -> student_id issued (first-come-first-served on PAYMENT,
--       not on submission time) -> batch/session/program/starting
--       semester fixed by admission -> courses allocated from SSO's
--       predefined course list -> fee structure (from SSO) applied,
--       admission can add a discount by adding/removing fee heads ->
--       first (admission) scholarship can be granted here.
-- =====================================================================

CREATE TABLE admission_applications (
    application_id      INT AUTO_INCREMENT PRIMARY KEY,
    temp_application_no VARCHAR(30) NOT NULL UNIQUE,   -- e.g. TMP-2026-000123, given instantly on submission
    full_name           VARCHAR(150) NOT NULL,
    father_name         VARCHAR(150) NOT NULL,
    cnic_or_bform       VARCHAR(20)  NULL,
    dob                 DATE NULL,
    gender              ENUM('Male','Female','Other') NULL,
    contact_no          VARCHAR(20)  NULL,
    email               VARCHAR(150) NULL,
    address             VARCHAR(255) NULL,
    program_id          INT NOT NULL,                  -- desired department/program
    session_id          INT NOT NULL,                  -- intake session applied for
    applied_semester_id INT NOT NULL,                  -- normally semester 1, fixed by admission
    application_status  ENUM('Submitted','Under Review','Approved','Rejected','Admitted','Cancelled')
                         NOT NULL DEFAULT 'Submitted',
    submitted_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_by          INT NULL,
    reviewed_at          DATETIME NULL,
    rejection_reason     VARCHAR(255) NULL,
    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_appl_program  FOREIGN KEY (program_id)  REFERENCES departments(department_id),
    CONSTRAINT fk_appl_session  FOREIGN KEY (session_id)  REFERENCES sessions(session_id),
    CONSTRAINT fk_appl_sem      FOREIGN KEY (applied_semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_appl_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- The permanent student record. Created ONLY once the first (admission)
-- fee payment clears -> guarantees "first payer gets the ID first"
-- because student_id is an AUTO_INCREMENT assigned at insert time, and
-- the insert only happens from sp_confirm_admission() below, which is
-- called from the payment flow, never from the application flow.
CREATE TABLE students (
    student_id        INT AUTO_INCREMENT PRIMARY KEY,
    application_id    INT NOT NULL UNIQUE,
    roll_no           VARCHAR(30) NULL UNIQUE,   -- filled in later by SSO's "Roll Number" sub-module
    full_name         VARCHAR(150) NOT NULL,
    father_name       VARCHAR(150) NOT NULL,
    cnic_or_bform     VARCHAR(20)  NULL,
    dob               DATE NULL,
    gender            ENUM('Male','Female','Other') NULL,
    contact_no        VARCHAR(20)  NULL,
    email             VARCHAR(150) NULL,
    address           VARCHAR(255) NULL,
    program_id        INT NOT NULL,              -- = department, fixed at admission
    admission_session_id INT NOT NULL,           -- starting session/year, FIXED, never changes
    current_session_id   INT NOT NULL,           -- session the student is currently studying in
    current_semester_id  INT NOT NULL,           -- set/advanced by Admission at first, later by Examination promotion
    batch_year        SMALLINT NOT NULL,
    admission_date    DATE NOT NULL,
    status            ENUM('Active','Freeze','Graduated','Dropped','Suspended') NOT NULL DEFAULT 'Active',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_stu_application FOREIGN KEY (application_id) REFERENCES admission_applications(application_id),
    CONSTRAINT fk_stu_program     FOREIGN KEY (program_id) REFERENCES departments(department_id),
    CONSTRAINT fk_stu_adm_session FOREIGN KEY (admission_session_id) REFERENCES sessions(session_id),
    CONSTRAINT fk_stu_cur_session FOREIGN KEY (current_session_id) REFERENCES sessions(session_id),
    CONSTRAINT fk_stu_cur_sem     FOREIGN KEY (current_semester_id) REFERENCES semesters(semester_id)
) ENGINE=InnoDB;

-- Fee structure is DEFINED by SSO (per program/session/semester), but it
-- is APPLIED/ASSIGNED to a student by Admission. This is the bridge table
-- that resolves "fee structure already defined by SSO, admission office
-- will apply that to the student".
CREATE TABLE fee_structures (
    fee_structure_id INT AUTO_INCREMENT PRIMARY KEY,
    program_id       INT NOT NULL,
    session_id       INT NOT NULL,
    semester_id      INT NOT NULL,
    total_amount     DECIMAL(12,2) NOT NULL DEFAULT 0,
    status           ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_by       INT NOT NULL,        -- SSO staff user_id
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_fs_program  FOREIGN KEY (program_id)  REFERENCES departments(department_id),
    CONSTRAINT fk_fs_session  FOREIGN KEY (session_id)  REFERENCES sessions(session_id),
    CONSTRAINT fk_fs_semester FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_fs_creator  FOREIGN KEY (created_by)  REFERENCES users(user_id),
    UNIQUE KEY uq_program_session_sem (program_id, session_id, semester_id)
) ENGINE=InnoDB;

-- fee_structure_details is created after fee_heads (section 5) - see below.

-- Admission applies a fee structure to a specific student for a specific semester
CREATE TABLE student_fee_assignment (
    assignment_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id       INT NOT NULL,
    fee_structure_id INT NOT NULL,
    semester_id      INT NOT NULL,
    assigned_by      INT NOT NULL,      -- admission officer user_id
    assigned_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sfa_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_sfa_fs      FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(fee_structure_id),
    CONSTRAINT fk_sfa_sem     FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_sfa_officer FOREIGN KEY (assigned_by) REFERENCES users(user_id),
    UNIQUE KEY uq_student_semester_assignment (student_id, semester_id)
) ENGINE=InnoDB;

-- Scholarships: FIRST one given by Admission at admission time, subsequent
-- semester-wise scholarships given by other departments/committees.
CREATE TABLE scholarships (
    scholarship_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id        INT NOT NULL,
    scholarship_type  ENUM('Admission','Semester-wise','Merit','Need-based','Other') NOT NULL,
    awarding_body      VARCHAR(100) NOT NULL,   -- 'Admission Office', 'Academics Committee', etc.
    semester_id        INT NULL,                -- NULL for the admission-time one
    discount_kind       ENUM('Flat','Percentage') NOT NULL DEFAULT 'Percentage',
    discount_value      DECIMAL(10,2) NOT NULL,
    approved_by         INT NOT NULL,
    approved_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status               ENUM('Active','Revoked') NOT NULL DEFAULT 'Active',
    remarks              VARCHAR(255) NULL,
    CONSTRAINT fk_sch_student   FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_sch_semester  FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_sch_approver  FOREIGN KEY (approved_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- Admission-granted discounts by adding/removing individual fee heads
-- (separate from scholarships, this is the "add/remove fee head" tool).
CREATE TABLE student_fee_discounts (
    discount_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id     INT NOT NULL,
    fee_head_id    INT NOT NULL,
    semester_id    INT NOT NULL,
    action_type    ENUM('Add','Remove','Reduce') NOT NULL,  -- Add a head, Remove a head entirely, or Reduce its amount
    amount         DECIMAL(10,2) NOT NULL DEFAULT 0,        -- amount added/removed/reduced
    reason         VARCHAR(255) NULL,
    applied_by     INT NOT NULL,
    applied_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sfd_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_sfd_semester FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_sfd_officer FOREIGN KEY (applied_by) REFERENCES users(user_id)
    -- fk_head added after fee_heads table is created (section 5)
) ENGINE=InnoDB;

-- =====================================================================
-- 4. SSO MODULE (everything after admission: courses, teachers,
--    timetable, attendance, applications, roll numbers)
-- =====================================================================

CREATE TABLE teachers (
    teacher_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NULL,                -- link to login account, if teacher has portal access
    teacher_name  VARCHAR(150) NOT NULL,
    designation   VARCHAR(100) NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20)  NULL,
    department_id INT NOT NULL,
    status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_teacher_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_teacher_dept FOREIGN KEY (department_id) REFERENCES departments(department_id)
) ENGINE=InnoDB;

CREATE TABLE courses (
    course_id      INT AUTO_INCREMENT PRIMARY KEY,
    course_code    VARCHAR(20) NOT NULL UNIQUE,
    course_title   VARCHAR(150) NOT NULL,
    description    TEXT NULL,                        -- LMS course description
    credit_hours   TINYINT NOT NULL DEFAULT 3,
    department_id  INT NOT NULL,
    semester_id    INT NOT NULL,
    semester_name  VARCHAR(40) NULL,                 -- LMS semester string (e.g. 'Spring 2026')
    teacher_id     INT NULL,                         -- LMS direct teacher link (FK to teachers)
    status         ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_course_dept FOREIGN KEY (department_id) REFERENCES departments(department_id),
    CONSTRAINT fk_course_sem  FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_course_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
) ENGINE=InnoDB;

CREATE TABLE semester_courses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    semester_id  INT NOT NULL,
    course_id    INT NOT NULL,
    CONSTRAINT fk_semc_sem    FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_semc_course FOREIGN KEY (course_id) REFERENCES courses(course_id),
    UNIQUE KEY uq_sem_course (semester_id, course_id)
) ENGINE=InnoDB;

CREATE TABLE teacher_courses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id   INT NOT NULL,
    course_id    INT NOT NULL,
    semester_id  INT NOT NULL,
    session_id   INT NOT NULL,
    section      VARCHAR(5) NOT NULL DEFAULT 'A',
    CONSTRAINT fk_tc_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    CONSTRAINT fk_tc_course  FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_tc_sem     FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_tc_session FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    UNIQUE KEY uq_course_sem_session_section (course_id, semester_id, session_id, section)
) ENGINE=InnoDB;

CREATE TABLE timetable (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    course_id    INT NOT NULL,
    teacher_id   INT NOT NULL,
    day_of_week  ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    start_time   TIME NOT NULL,
    end_time     TIME NOT NULL,
    room_no      VARCHAR(30) NOT NULL,
    semester_id  INT NOT NULL,
    session_id   INT NOT NULL,
    section      VARCHAR(5) NOT NULL DEFAULT 'A',
    CONSTRAINT fk_tt_course  FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_tt_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    CONSTRAINT fk_tt_sem     FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_tt_session FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    CONSTRAINT chk_tt_time CHECK (end_time > start_time),
    -- prevents double-booking the same room, at the same time, same day
    UNIQUE KEY uq_room_slot (room_no, day_of_week, start_time, session_id)
) ENGINE=InnoDB;

CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id    INT NOT NULL,
    course_id     INT NOT NULL,
    teacher_id    INT NULL,                 -- nullable for LMS usage
    class_date    DATE NOT NULL,
    status        ENUM('Present','Absent','Late','Leave') NOT NULL,
    marked_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_att_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_att_course  FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_att_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    UNIQUE KEY uq_student_course_date (student_id, course_id, class_date)
) ENGINE=InnoDB;

-- Renamed from "applications" to sso_applications to avoid clashing
-- with admission_applications in the shared database.
CREATE TABLE sso_applications (
    application_id  INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    application_type ENUM('Leave','Course Withdrawal','Semester Freeze','Transcript',
                           'Bonafide Certificate','ID Card','Other') NOT NULL,
    subject         VARCHAR(150) NOT NULL,
    description     VARCHAR(500) NULL,
    attachment_path VARCHAR(255) NULL,
    status          ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    remarks         VARCHAR(255) NULL,
    resolved_by     INT NULL,
    resolved_at     DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ssoapp_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_ssoapp_resolver FOREIGN KEY (resolved_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 5. FINANCE MODULE
-- =====================================================================

CREATE TABLE fee_heads (
    fee_head_id   INT AUTO_INCREMENT PRIMARY KEY,
    fee_head_name VARCHAR(100) NOT NULL UNIQUE,   -- Admission Fee, Tuition Fee, Library Fee...
    description   VARCHAR(255) NULL,
    status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    deleted_at    TIMESTAMP NULL,                 -- soft delete / restore support
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Now that fee_heads exists, finish fee_structure_details & the discount FK
CREATE TABLE fee_structure_details (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    fee_structure_id INT NOT NULL,
    fee_head_id      INT NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_fsd_structure FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(fee_structure_id) ON DELETE CASCADE,
    CONSTRAINT fk_fsd_head      FOREIGN KEY (fee_head_id) REFERENCES fee_heads(fee_head_id),
    UNIQUE KEY uq_structure_head (fee_structure_id, fee_head_id)
) ENGINE=InnoDB;

ALTER TABLE student_fee_discounts
    ADD CONSTRAINT fk_sfd_head FOREIGN KEY (fee_head_id) REFERENCES fee_heads(fee_head_id);

CREATE TABLE student_fee (
    student_fee_id   INT AUTO_INCREMENT PRIMARY KEY,
    student_id       INT NOT NULL,
    semester_id      INT NOT NULL,
    session_id       INT NOT NULL,
    fee_structure_id INT NOT NULL,
    total_amount     DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount      DECIMAL(12,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - paid_amount) STORED,
    due_date         DATE NULL,
    status           ENUM('Unpaid','Partially Paid','Paid','Overdue') NOT NULL DEFAULT 'Unpaid',
    generated_by     INT NOT NULL,
    generated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stf_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_stf_sem     FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_stf_session FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    CONSTRAINT fk_stf_fs      FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(fee_structure_id),
    CONSTRAINT fk_stf_generator FOREIGN KEY (generated_by) REFERENCES users(user_id),
    UNIQUE KEY uq_student_semester_fee (student_id, semester_id)
) ENGINE=InnoDB;

CREATE TABLE student_fee_details (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    student_fee_id INT NOT NULL,
    fee_head_id    INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    net_amount     DECIMAL(10,2) GENERATED ALWAYS AS (amount - discount_amount) STORED,
    CONSTRAINT fk_sfdet_stf  FOREIGN KEY (student_fee_id) REFERENCES student_fee(student_fee_id) ON DELETE CASCADE,
    CONSTRAINT fk_sfdet_head FOREIGN KEY (fee_head_id) REFERENCES fee_heads(fee_head_id),
    UNIQUE KEY uq_stf_head (student_fee_id, fee_head_id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    payment_id      INT AUTO_INCREMENT PRIMARY KEY,
    student_fee_id  INT NOT NULL,
    student_id      INT NOT NULL,
    amount_paid     DECIMAL(12,2) NOT NULL,
    payment_method  ENUM('Cash','Bank','Card','Online') NOT NULL DEFAULT 'Cash',
    transaction_ref VARCHAR(100) NULL,
    payment_date    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by     INT NOT NULL,
    status          ENUM('Success','Reversed') NOT NULL DEFAULT 'Success',
    CONSTRAINT fk_pay_stf     FOREIGN KEY (student_fee_id) REFERENCES student_fee(student_fee_id),
    CONSTRAINT fk_pay_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_pay_officer FOREIGN KEY (received_by) REFERENCES users(user_id),
    CONSTRAINT chk_pay_positive CHECK (amount_paid > 0)
) ENGINE=InnoDB;

CREATE TABLE receipts (
    receipt_id  INT AUTO_INCREMENT PRIMARY KEY,
    payment_id  INT NOT NULL UNIQUE,
    receipt_no  VARCHAR(30) NOT NULL UNIQUE,
    issued_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    issued_by   INT NOT NULL,
    CONSTRAINT fk_rcpt_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id),
    CONSTRAINT fk_rcpt_issuer  FOREIGN KEY (issued_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE installments (
    installment_id  INT AUTO_INCREMENT PRIMARY KEY,
    student_fee_id  INT NOT NULL,
    installment_no  TINYINT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    due_date        DATE NOT NULL,
    paid_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    status          ENUM('Pending','Paid','Overdue') NOT NULL DEFAULT 'Pending',
    CONSTRAINT fk_inst_stf FOREIGN KEY (student_fee_id) REFERENCES student_fee(student_fee_id) ON DELETE CASCADE,
    UNIQUE KEY uq_stf_installment_no (student_fee_id, installment_no)
) ENGINE=InnoDB;

CREATE TABLE payment_reversals (
    reversal_id     INT AUTO_INCREMENT PRIMARY KEY,
    payment_id      INT NOT NULL,
    reversed_amount DECIMAL(12,2) NOT NULL,
    reason          VARCHAR(255) NOT NULL,
    reversed_by     INT NOT NULL,
    reversed_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id),
    CONSTRAINT fk_rev_officer FOREIGN KEY (reversed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    log_id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    module          VARCHAR(50) NOT NULL,     -- Admission, Finance, SSO, Examination, SBE
    action          VARCHAR(100) NOT NULL,    -- 'Fee Generated', 'Payment Received', 'Soft Delete', etc.
    reference_table VARCHAR(100) NULL,
    reference_id    BIGINT NULL,
    performed_by    INT NULL,
    details         TEXT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (performed_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 6. EXAMINATION MODULE  (Publish Paper + Promote Student)
-- NOTE: original "exams" renamed to "examinations" to avoid a name clash
--       with the SBE module's own "exams" table in this shared database.
-- =====================================================================

CREATE TABLE examinations (
    exam_id     INT AUTO_INCREMENT PRIMARY KEY,
    exam_type   ENUM('Mid','Final') NOT NULL,
    session_id  INT NOT NULL,
    semester_id INT NOT NULL,
    created_by  INT NOT NULL,       -- teacher/admin user_id
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exm_session FOREIGN KEY (session_id) REFERENCES sessions(session_id),
    CONSTRAINT fk_exm_sem     FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_exm_creator FOREIGN KEY (created_by) REFERENCES users(user_id)
) ENGINE=InnoDB;

CREATE TABLE question_papers (
    paper_id     INT AUTO_INCREMENT PRIMARY KEY,
    exam_id      INT NOT NULL,
    course_id    INT NOT NULL,
    teacher_id   INT NOT NULL,
    paper_file   VARCHAR(255) NOT NULL,
    status       ENUM('Pending','Published') NOT NULL DEFAULT 'Pending',
    publish_date DATE NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qp_exam    FOREIGN KEY (exam_id) REFERENCES examinations(exam_id),
    CONSTRAINT fk_qp_course  FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_qp_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    -- reject invalid file formats at the app layer; DB enforces the extension allow-list here as a backstop
    CONSTRAINT chk_qp_file_ext CHECK (paper_file LIKE '%.pdf' OR paper_file LIKE '%.docx')
) ENGINE=InnoDB;

CREATE TABLE student_promotions (
    promotion_id    INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT NOT NULL,
    from_semester_id INT NOT NULL,
    to_semester_id   INT NOT NULL,
    from_session_id  INT NOT NULL,
    to_session_id    INT NOT NULL,
    promoted_by      INT NOT NULL,
    promotion_date   DATE NOT NULL DEFAULT (CURRENT_DATE),
    remarks          VARCHAR(255) NULL,
    CONSTRAINT fk_promo_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_promo_from_sem FOREIGN KEY (from_semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_promo_to_sem   FOREIGN KEY (to_semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT fk_promo_from_ses FOREIGN KEY (from_session_id) REFERENCES sessions(session_id),
    CONSTRAINT fk_promo_to_ses   FOREIGN KEY (to_session_id) REFERENCES sessions(session_id),
    CONSTRAINT fk_promo_officer  FOREIGN KEY (promoted_by) REFERENCES users(user_id),
    -- prevents duplicate promotion of the same student into the same target semester/session
    UNIQUE KEY uq_student_target (student_id, to_semester_id, to_session_id)
) ENGINE=InnoDB;

-- =====================================================================
-- 7. SBE MODULE (System Based Examination)
-- Kept as its own isolated layer as originally designed (auth_users is
-- local), but course/teacher/student IDs are now linked to the shared
-- tables so it plugs into the ONE main database instead of floating
-- disconnected data. sbe_ prefix keeps every object name unique.
-- =====================================================================

CREATE TABLE sbe_auth_users (
    auth_id       INT AUTO_INCREMENT PRIMARY KEY,
    role          ENUM('Teacher','Student') NOT NULL,
    login_id      VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(150) NOT NULL,
    -- links back to the real person in the shared DB (nullable so the
    -- module can still work standalone if ever needed)
    teacher_id    INT NULL,
    student_id    INT NULL,
    status        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sbeauth_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    CONSTRAINT fk_sbeauth_student FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT chk_sbeauth_role CHECK (
        (role = 'Teacher' AND teacher_id IS NOT NULL) OR
        (role = 'Student' AND student_id IS NOT NULL)
    )
) ENGINE=InnoDB;

CREATE TABLE sbe_question_bank (
    question_id      INT AUTO_INCREMENT PRIMARY KEY,
    course_id        INT NOT NULL,
    teacher_id       INT NOT NULL,
    topic            VARCHAR(150) NULL,
    question_text    TEXT NOT NULL,
    option_a         VARCHAR(255) NOT NULL,
    option_b         VARCHAR(255) NOT NULL,
    option_c         VARCHAR(255) NOT NULL,
    option_d         VARCHAR(255) NOT NULL,
    correct_option   ENUM('A','B','C','D') NOT NULL,
    explanation      VARCHAR(500) NULL,
    marks            DECIMAL(5,2) NOT NULL DEFAULT 1,
    difficulty_level ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Medium',
    status           ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_qb_course  FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_qb_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
) ENGINE=InnoDB;

CREATE TABLE sbe_exams (
    exam_id            INT AUTO_INCREMENT PRIMARY KEY,
    exam_code          VARCHAR(30) NOT NULL UNIQUE,
    course_id          INT NOT NULL,
    teacher_id         INT NOT NULL,
    title              VARCHAR(150) NOT NULL,
    exam_type          ENUM('Quiz','Mid','Final','Practice','Assignment Test') NOT NULL,
    instructions       TEXT NULL,
    duration_minutes   SMALLINT NOT NULL,
    total_questions    SMALLINT NOT NULL,
    total_marks        DECIMAL(6,2) NOT NULL,
    passing_marks      DECIMAL(6,2) NOT NULL,
    selection_mode     ENUM('Manual','Random') NOT NULL DEFAULT 'Manual',
    negative_marking   DECIMAL(4,2) NOT NULL DEFAULT 0,
    shuffle_questions  TINYINT(1) NOT NULL DEFAULT 0,
    shuffle_options    TINYINT(1) NOT NULL DEFAULT 0,
    allow_review       TINYINT(1) NOT NULL DEFAULT 1,
    status             ENUM('Draft','Published','Closed','Archived') NOT NULL DEFAULT 'Draft',
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sbeexam_course  FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_sbeexam_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id),
    CONSTRAINT chk_sbeexam_pass CHECK (passing_marks <= total_marks)
) ENGINE=InnoDB;

CREATE TABLE sbe_exam_schedule (
    schedule_id                 INT AUTO_INCREMENT PRIMARY KEY,
    exam_id                     INT NOT NULL,
    section                     VARCHAR(5) NOT NULL,     -- class/section, e.g. BSCS-5A
    semester_id                 INT NOT NULL,
    exam_date                   DATE NOT NULL,
    start_time                  TIME NOT NULL,
    end_time                    TIME NOT NULL,
    late_submission_grace_minutes SMALLINT NOT NULL DEFAULT 0,
    location                    VARCHAR(100) NULL,
    remarks                     VARCHAR(255) NULL,
    status                      ENUM('Scheduled','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
    created_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sbesched_exam FOREIGN KEY (exam_id) REFERENCES sbe_exams(exam_id),
    CONSTRAINT fk_sbesched_sem  FOREIGN KEY (semester_id) REFERENCES semesters(semester_id),
    CONSTRAINT chk_sbesched_time CHECK (end_time > start_time)
) ENGINE=InnoDB;

CREATE TABLE sbe_exam_questions (
    exam_question_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id          INT NOT NULL,
    question_id      INT NOT NULL,
    question_order   SMALLINT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sbeeq_exam FOREIGN KEY (exam_id) REFERENCES sbe_exams(exam_id),
    CONSTRAINT fk_sbeeq_question FOREIGN KEY (question_id) REFERENCES sbe_question_bank(question_id),
    UNIQUE KEY uq_exam_question (exam_id, question_id),        -- no duplicate question in the same exam
    UNIQUE KEY uq_exam_order (exam_id, question_order)         -- no duplicate order position
) ENGINE=InnoDB;

CREATE TABLE sbe_student_exams (
    student_exam_id    INT AUTO_INCREMENT PRIMARY KEY,
    schedule_id        INT NOT NULL,
    exam_id            INT NOT NULL,
    student_id         INT NOT NULL,
    attempt_no         TINYINT NOT NULL DEFAULT 1,
    status             ENUM('In Progress','Submitted','Auto Submitted','Expired','Cancelled') NOT NULL DEFAULT 'In Progress',
    started_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at         DATETIME NOT NULL,
    submitted_at       DATETIME NULL,
    time_taken_seconds INT NULL,
    obtained_marks     DECIMAL(6,2) NULL,
    percentage         DECIMAL(5,2) NULL,
    pass_fail_status   ENUM('Pass','Fail') NULL,
    CONSTRAINT fk_sbese_schedule FOREIGN KEY (schedule_id) REFERENCES sbe_exam_schedule(schedule_id),
    CONSTRAINT fk_sbese_exam     FOREIGN KEY (exam_id) REFERENCES sbe_exams(exam_id),
    CONSTRAINT fk_sbese_student  FOREIGN KEY (student_id) REFERENCES students(student_id),
    UNIQUE KEY uq_schedule_student_attempt (schedule_id, student_id, attempt_no)
) ENGINE=InnoDB;

CREATE TABLE sbe_student_answers (
    student_answer_id  INT AUTO_INCREMENT PRIMARY KEY,
    student_exam_id     INT NOT NULL,
    question_id          INT NOT NULL,
    question_order        SMALLINT NOT NULL,
    question_snapshot     JSON NOT NULL,
    selected_option       ENUM('A','B','C','D') NULL,
    answered_at            DATETIME NULL,
    is_correct             TINYINT(1) NULL,
    marks_awarded          DECIMAL(5,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_sbesa_studentexam FOREIGN KEY (student_exam_id) REFERENCES sbe_student_exams(student_exam_id),
    CONSTRAINT fk_sbesa_question    FOREIGN KEY (question_id) REFERENCES sbe_question_bank(question_id),
    UNIQUE KEY uq_studentexam_question (student_exam_id, question_id)
) ENGINE=InnoDB;

CREATE TABLE sbe_exam_results (
    exam_result_id  INT AUTO_INCREMENT PRIMARY KEY,
    student_exam_id INT NOT NULL,
    exam_id         INT NOT NULL,
    student_id      INT NOT NULL,
    obtained_marks  DECIMAL(6,2) NOT NULL,
    total_marks     DECIMAL(6,2) NOT NULL,
    percentage      DECIMAL(5,2) NOT NULL,
    pass_fail_status ENUM('Pass','Fail') NOT NULL,
    rank_position   INT NULL,
    remarks         VARCHAR(255) NULL,
    status          ENUM('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
    published_at    DATETIME NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sberes_studentexam FOREIGN KEY (student_exam_id) REFERENCES sbe_student_exams(student_exam_id),
    CONSTRAINT fk_sberes_exam        FOREIGN KEY (exam_id) REFERENCES sbe_exams(exam_id),
    CONSTRAINT fk_sberes_student     FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT chk_sberes_marks CHECK (obtained_marks <= total_marks),
    UNIQUE KEY uq_studentexam_result (student_exam_id)
) ENGINE=InnoDB;

-- =====================================================================
-- LMS MODULE TABLES (Learning Management System)
-- =====================================================================

-- LMS: Student-course enrollment (links LMS users to courses)
CREATE TABLE lms_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    student_user_id INT NOT NULL,
    course_id    INT NOT NULL,
    enrolled_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_enr_student FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_lms_enr_course  FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    UNIQUE KEY uq_lms_enrollment (student_user_id, course_id)
) ENGINE=InnoDB;

-- LMS: Teacher-created assignments with file upload and due date
CREATE TABLE lms_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id    INT NOT NULL,
    title        VARCHAR(160) NOT NULL,
    description  TEXT NULL,
    file_path    VARCHAR(255) NULL,
    due_date     DATE NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_asn_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- LMS: Course lecture materials with file upload
CREATE TABLE lms_lectures (
    lecture_id    INT AUTO_INCREMENT PRIMARY KEY,
    course_id     INT NOT NULL,
    title         VARCHAR(160) NOT NULL,
    file_path     VARCHAR(255) NOT NULL,
    lecture_date  DATE NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_lec_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- LMS: Student assignment submissions with grading and feedback
CREATE TABLE lms_submissions (
    submission_id   INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id   INT NOT NULL,
    student_user_id INT NOT NULL,
    content         TEXT NOT NULL,
    submission_file VARCHAR(255) NULL,
    submitted_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade           DECIMAL(5,2) NULL,
    feedback        TEXT NULL,
    CONSTRAINT fk_lms_sub_assignment FOREIGN KEY (assignment_id) REFERENCES lms_assignments(assignment_id) ON DELETE CASCADE,
    CONSTRAINT fk_lms_sub_student    FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_lms_submission (assignment_id, student_user_id)
) ENGINE=InnoDB;

-- LMS: Per-component internal marks (quiz, mid, assignment, etc.)
CREATE TABLE lms_marks (
    mark_id        INT AUTO_INCREMENT PRIMARY KEY,
    course_id      INT NOT NULL,
    student_user_id INT NOT NULL,
    component      VARCHAR(100) NOT NULL,
    marks_obtained DECIMAL(6,2) NOT NULL,
    total_marks    DECIMAL(6,2) NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_mark_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    CONSTRAINT fk_lms_mark_student FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_lms_mark (course_id, student_user_id, component)
) ENGINE=InnoDB;

-- LMS: Locks/finalizes marks for a student-course pair
CREATE TABLE lms_mark_finalizations (
    finalization_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id       INT NOT NULL,
    student_user_id INT NOT NULL,
    is_finalized    TINYINT(1) NOT NULL DEFAULT 0,
    finalized_at    TIMESTAMP NULL,
    CONSTRAINT fk_lms_mf_course FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE CASCADE,
    CONSTRAINT fk_lms_mf_student FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uq_lms_finalization (course_id, student_user_id)
) ENGINE=InnoDB;

-- LMS: Student support/query tickets
CREATE TABLE lms_queries (
    query_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    subject        VARCHAR(160) NOT NULL,
    message        TEXT NOT NULL,
    status         ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
    reply          TEXT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_qry_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- LMS: Student applications (leave, withdrawal, etc.)
CREATE TABLE lms_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    type           VARCHAR(100) NOT NULL,
    details        TEXT NOT NULL,
    status         ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_app_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- LMS: In-app notifications with sender, recipient, category, read-status
CREATE TABLE lms_notifications (
    notification_id   INT AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id INT NOT NULL,
    sender_user_id    INT NULL,
    category          ENUM('notification','message','announcement') NOT NULL DEFAULT 'notification',
    title             VARCHAR(160) NOT NULL,
    body              TEXT NOT NULL,
    link_url          VARCHAR(255) NULL,
    is_read           TINYINT(1) NOT NULL DEFAULT 0,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_notif_recipient FOREIGN KEY (recipient_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_lms_notif_sender    FOREIGN KEY (sender_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- LMS: Simplified fee records for LMS views
CREATE TABLE lms_fee_records (
    fee_record_id INT AUTO_INCREMENT PRIMARY KEY,
    student_user_id INT NOT NULL,
    semester     VARCHAR(40) NOT NULL,
    description  VARCHAR(160) NOT NULL,
    amount       DECIMAL(12,2) NOT NULL,
    paid_amount  DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_date     DATE NOT NULL,
    status       ENUM('paid','partial','unpaid') NOT NULL DEFAULT 'unpaid',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lms_fee_student FOREIGN KEY (student_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 8. CROSS-MODULE BUSINESS-RULE LOGIC
-- =====================================================================

DELIMITER $$

-- 8.1 Confirm admission after the FIRST successful payment.
--     This is what guarantees "whoever pays first gets student_id first",
--     because the students row (and its AUTO_INCREMENT id) is only
--     created here, not at application time.
CREATE PROCEDURE sp_confirm_admission(
    IN p_application_id INT,
    IN p_current_semester_id INT,
    IN p_batch_year SMALLINT,
    OUT p_student_id INT
)
BEGIN
    DECLARE v_program_id INT;
    DECLARE v_session_id INT;

    SELECT program_id, session_id INTO v_program_id, v_session_id
    FROM admission_applications
    WHERE application_id = p_application_id
      AND application_status IN ('Approved','Under Review','Submitted');

    INSERT INTO students (
        application_id, full_name, father_name, cnic_or_bform, dob, gender,
        contact_no, email, address, program_id, admission_session_id,
        current_session_id, current_semester_id, batch_year, admission_date, status
    )
    SELECT
        application_id, full_name, father_name, cnic_or_bform, dob, gender,
        contact_no, email, address, program_id, session_id,
        session_id, p_current_semester_id, p_batch_year, CURDATE(), 'Active'
    FROM admission_applications
    WHERE application_id = p_application_id;

    SET p_student_id = LAST_INSERT_ID();

    UPDATE admission_applications
    SET application_status = 'Admitted'
    WHERE application_id = p_application_id;
END$$

-- 8.2 Keep student_fee.paid_amount / status in sync whenever a payment is recorded
CREATE TRIGGER trg_after_payment_insert
AFTER INSERT ON payments
FOR EACH ROW
BEGIN
    IF NEW.status = 'Success' THEN
        UPDATE student_fee
        SET paid_amount = paid_amount + NEW.amount_paid
        WHERE student_fee_id = NEW.student_fee_id;

        UPDATE student_fee
        SET status = CASE
                        WHEN paid_amount >= total_amount THEN 'Paid'
                        WHEN paid_amount > 0 THEN 'Partially Paid'
                        ELSE 'Unpaid'
                     END
        WHERE student_fee_id = NEW.student_fee_id;

        INSERT INTO activity_logs (module, action, reference_table, reference_id, performed_by, details)
        VALUES ('Finance', 'Payment Received', 'payments', NEW.payment_id, NEW.received_by,
                CONCAT('Amount: ', NEW.amount_paid));
    END IF;
END$$

-- 8.3 Reverse a payment: adjust student_fee back down and log it
CREATE TRIGGER trg_after_reversal_insert
AFTER INSERT ON payment_reversals
FOR EACH ROW
BEGIN
    DECLARE v_student_fee_id INT;

    SELECT student_fee_id INTO v_student_fee_id FROM payments WHERE payment_id = NEW.payment_id;

    UPDATE payments SET status = 'Reversed' WHERE payment_id = NEW.payment_id;

    UPDATE student_fee
    SET paid_amount = GREATEST(paid_amount - NEW.reversed_amount, 0)
    WHERE student_fee_id = v_student_fee_id;

    UPDATE student_fee
    SET status = CASE
                    WHEN paid_amount >= total_amount THEN 'Paid'
                    WHEN paid_amount > 0 THEN 'Partially Paid'
                    ELSE 'Unpaid'
                 END
    WHERE student_fee_id = v_student_fee_id;

    INSERT INTO activity_logs (module, action, reference_table, reference_id, performed_by, details)
    VALUES ('Finance', 'Payment Reversed', 'payment_reversals', NEW.reversal_id, NEW.reversed_by, NEW.reason);
END$$

-- 8.4 Prevent promoting a student who is already in the final semester of their program
CREATE TRIGGER trg_before_promotion_insert
BEFORE INSERT ON student_promotions
FOR EACH ROW
BEGIN
    DECLARE v_from_num TINYINT;
    DECLARE v_total_sem TINYINT;
    DECLARE v_program_id INT;

    SELECT s.semester_number, d.total_semesters, d.department_id
    INTO v_from_num, v_total_sem, v_program_id
    FROM semesters s
    JOIN departments d ON d.department_id = s.department_id
    WHERE s.semester_id = NEW.from_semester_id;

    IF v_from_num >= v_total_sem THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot promote: student is already in the final semester.';
    END IF;
END$$

-- 8.5 Log soft delete / restore of fee heads
CREATE TRIGGER trg_after_feehead_update
AFTER UPDATE ON fee_heads
FOR EACH ROW
BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO activity_logs (module, action, reference_table, reference_id, details)
        VALUES ('Finance', 'Soft Delete Fee Head', 'fee_heads', NEW.fee_head_id, NEW.fee_head_name);
    ELSEIF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO activity_logs (module, action, reference_table, reference_id, details)
        VALUES ('Finance', 'Restore Fee Head', 'fee_heads', NEW.fee_head_id, NEW.fee_head_name);
    END IF;
END$$

DELIMITER ;

-- =====================================================================
-- 9. USEFUL VIEWS
-- =====================================================================

-- One-stop student profile combining Admission + SSO + current fee status
CREATE OR REPLACE VIEW vw_student_profile AS
SELECT
    st.student_id, st.roll_no, st.full_name, st.father_name,
    d.department_name AS program, se.session_name AS current_session,
    sm.semester_name AS current_semester, st.batch_year, st.status,
    sf.total_amount, sf.paid_amount, sf.remaining_amount, sf.status AS fee_status
FROM students st
JOIN departments d ON d.department_id = st.program_id
JOIN sessions se ON se.session_id = st.current_session_id
JOIN semesters sm ON sm.semester_id = st.current_semester_id
LEFT JOIN student_fee sf ON sf.student_id = st.student_id AND sf.semester_id = st.current_semester_id;

-- Pending admission queue ordered by submission time (for review), separate
-- from the payment-driven student_id assignment
CREATE OR REPLACE VIEW vw_pending_admissions AS
SELECT application_id, temp_application_no, full_name, program_id, session_id, submitted_at
FROM admission_applications
WHERE application_status IN ('Submitted','Under Review')
ORDER BY submitted_at ASC;

-- =====================================================================
-- 10. LMS MODULE SEED DATA
-- =====================================================================

-- Roles: add Student role for LMS
INSERT INTO roles (role_name) VALUES ('Student') ON DUPLICATE KEY UPDATE role_name = role_name;

-- LMS Users: 5 teachers (5001-5005) + 20 students (9001-9020)
-- Password for all: teacher123 / student123 (bcrypt)
SET @teacher_role = (SELECT role_id FROM roles WHERE role_name = 'Teacher');
SET @student_role = (SELECT role_id FROM roles WHERE role_name = 'Student');
SET @cs_dept = (SELECT department_id FROM departments WHERE department_code = 'CS' LIMIT 1);
SET @sem_1 = (SELECT semester_id FROM semesters WHERE semester_number = 1 LIMIT 1);

-- Department fallback: if CS doesn't exist, use first department
SET @dept_fallback = COALESCE(@cs_dept, (SELECT department_id FROM departments LIMIT 1));
SET @sem_fallback  = COALESCE(@sem_1, (SELECT semester_id FROM semesters LIMIT 1));

INSERT INTO users (full_name, username, email, password_hash, role_id, department_id, login_id, profile_photo, status) VALUES
    ('Dr. Sara Khan',    'teacher_sara',    'sara.khan@university.edu',    '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @teacher_role, @dept_fallback, '5001', NULL, 'Active'),
    ('Dr. Ahmed Ali',    'teacher_ahmed',   'ahmed.ali@university.edu',    '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @teacher_role, @dept_fallback, '5002', NULL, 'Active'),
    ('Dr. Fatima Noor',  'teacher_fatima',  'fatima.noor@university.edu',  '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @teacher_role, @dept_fallback, '5003', NULL, 'Active'),
    ('Prof. Hassan Raza','teacher_hassan',   'hassan.raza@university.edu',  '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @teacher_role, @dept_fallback, '5004', NULL, 'Active'),
    ('Ms. Ayesha Siddiq','teacher_ayesha',  'ayesha.siddiq@university.edu','$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @teacher_role, @dept_fallback, '5005', NULL, 'Active');

INSERT INTO users (full_name, username, email, password_hash, role_id, department_id, login_id, profile_photo, status) VALUES
    ('Ali Raza',         'stu_ali',         'ali.raza@student.edu',         '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9001', NULL, 'Active'),
    ('Amina Noor',       'stu_amina',       'amina.noor@student.edu',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9002', NULL, 'Active'),
    ('Bilal Ahmed',      'stu_bilal',       'bilal.ahmed@student.edu',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9003', NULL, 'Active'),
    ('Hira Khan',        'stu_hira',        'hira.khan@student.edu',        '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9004', NULL, 'Active'),
    ('Hassan Ali',       'stu_hassan_s',    'hassan.ali@student.edu',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9005', NULL, 'Active'),
    ('Maryam Iqbal',     'stu_maryam',      'maryam.iqbal@student.edu',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9006', NULL, 'Active'),
    ('Usman Tariq',      'stu_usman',       'usman.tariq@student.edu',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9007', NULL, 'Active'),
    ('Sana Raza',        'stu_sana',        'sana.raza@student.edu',        '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9008', NULL, 'Active'),
    ('Zain Malik',       'stu_zain',        'zain.malik@student.edu',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9009', NULL, 'Active'),
    ('Iqra Javed',       'stu_iqra',        'iqra.javed@student.edu',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9010', NULL, 'Active'),
    ('Kamran Shah',      'stu_kamran',      'kamran.shah@student.edu',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9011', NULL, 'Active'),
    ('Nadia Bibi',       'stu_nadia',       'nadia.bibi@student.edu',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9012', NULL, 'Active'),
    ('Omar Farooq',      'stu_omar',        'omar.farooq@student.edu',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9013', NULL, 'Active'),
    ('巴基斯坦', 'stu_pakistan', 'pakistan@student.edu', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9014', NULL, 'Active'),
    ('Qasim Nawaz',      'stu_qasim',       'qasim.nawaz@student.edu',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9015', NULL, 'Active'),
    ('Rabia Malik',      'stu_rabia',       'rabia.malik@student.edu',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9016', NULL, 'Active'),
    ('Saad Tariq',       'stu_saad',        'saad.tariq@student.edu',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9017', NULL, 'Active'),
    ('Tania Iqbal',      'stu_tania',       'tania.iqbal@student.edu',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9018', NULL, 'Active'),
    ('Umar Hayat',       'stu_umar',        'umar.hayat@student.edu',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9019', NULL, 'Active'),
    ('Zara Hussain',     'stu_zara',        'zara.hussain@student.edu',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', @student_role, @dept_fallback, '9020', NULL, 'Active');

-- LMS Courses (assigned to first teacher)
SET @t1 = (SELECT user_id FROM users WHERE login_id = '5001' LIMIT 1);

INSERT INTO courses (course_code, course_title, description, credit_hours, department_id, semester_id, semester_name, teacher_id, status) VALUES
    ('CS101',  'Introduction to Programming',    'Fundamentals of programming using clear logic and problem solving.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active'),
    ('CS102',  'Object Oriented Programming',     'OOP concepts: classes, inheritance, polymorphism, and design patterns.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active'),
    ('SE201',  'Software Engineering',            'Software development lifecycle, agile methodologies, and project management.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active'),
    ('SE204',  'Database Systems',                'Design and manage relational databases and SQL-based applications.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active'),
    ('CS301',  'Data Structures and Algorithms',  'Arrays, linked lists, trees, graphs, sorting and searching algorithms.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active'),
    ('CS302',  'Operating Systems',               'Process management, memory management, file systems, and concurrency.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active'),
    ('SE301',  'Web Engineering',                 'Modern web development: HTML, CSS, JavaScript, PHP, and frameworks.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active'),
    ('SE302',  'Information Security',            'Cryptography, network security, access control, and security auditing.', 3, @dept_fallback, @sem_fallback, 'Spring 2026', @t1, 'Active');

-- LMS Enrollments: all 20 students in all 8 courses
INSERT INTO lms_enrollments (student_user_id, course_id)
SELECT u.user_id, c.course_id
FROM users u
CROSS JOIN courses c
WHERE u.role_id = @student_role AND c.semester_name = 'Spring 2026';

-- LMS Assignments
INSERT INTO lms_assignments (course_id, title, description, file_path, due_date) VALUES
    ((SELECT course_id FROM courses WHERE course_code = 'CS101'),  'Loops Practice',         'Submit five solved loop problems.',                   NULL, '2026-07-20'),
    ((SELECT course_id FROM courses WHERE course_code = 'CS101'),  'Functions Assignment',    'Write functions for common mathematical operations.',  NULL, '2026-07-25'),
    ((SELECT course_id FROM courses WHERE course_code = 'CS102'),  'Class Design',            'Design a class hierarchy for a banking system.',       NULL, '2026-07-22'),
    ((SELECT course_id FROM courses WHERE course_code = 'CS102'),  'Inheritance Lab',         'Implement inheritance and polymorphism examples.',     NULL, '2026-07-28'),
    ((SELECT course_id FROM courses WHERE course_code = 'SE201'),  'SDLC Report',             'Write a report on SDLC phases for a real project.',    NULL, '2026-07-23'),
    ((SELECT course_id FROM courses WHERE course_code = 'SE204'),  'ERD Design',              'Create an ERD for a library management system.',        NULL, '2026-07-25'),
    ((SELECT course_id FROM courses WHERE course_code = 'SE204'),  'SQL Queries',             'Write 10 complex SQL queries with joins.',             NULL, '2026-07-30'),
    ((SELECT course_id FROM courses WHERE course_code = 'CS301'),  'Binary Tree Implementation', 'Implement a BST with insert, delete, search.',     NULL, '2026-07-24'),
    ((SELECT course_id FROM courses WHERE course_code = 'CS302'),  'Process Scheduling',      'Simulate FCFS, SJF, and Round Robin algorithms.',      NULL, '2026-07-26'),
    ((SELECT course_id FROM courses WHERE course_code = 'SE301'),  'Web Page Design',         'Build a responsive landing page using HTML/CSS.',       NULL, '2026-07-27'),
    ((SELECT course_id FROM courses WHERE course_code = 'SE302'),  'Security Audit Report',   'Perform a basic security audit on a sample system.',    NULL, '2026-07-29');

-- LMS Submissions: some students submitted assignments
INSERT INTO lms_submissions (assignment_id, student_user_id, content, submission_file, submitted_at, grade, feedback) VALUES
    (1,  (SELECT user_id FROM users WHERE login_id = '9001'), 'Loops practice submitted for review.', NULL, NOW(), NULL, NULL),
    (1,  (SELECT user_id FROM users WHERE login_id = '9002'), 'Practice file uploaded.',              NULL, NOW(), NULL, NULL),
    (6,  (SELECT user_id FROM users WHERE login_id = '9003'), 'ERD draft uploaded.',                  NULL, NOW(), NULL, NULL),
    (2,  (SELECT user_id FROM users WHERE login_id = '9004'), 'Functions assignment submitted.',       NULL, NOW(), 8.00, 'Good work.'),
    (7,  (SELECT user_id FROM users WHERE login_id = '9005'), 'SQL queries submitted.',               NULL, NOW(), NULL, NULL),
    (3,  (SELECT user_id FROM users WHERE login_id = '9006'), 'Class design document uploaded.',       NULL, NOW(), NULL, NULL),
    (8,  (SELECT user_id FROM users WHERE login_id = '9007'), 'BST implementation submitted.',         NULL, NOW(), 7.50, 'Needs more test cases.'),
    (1,  (SELECT user_id FROM users WHERE login_id = '9008'), 'Loops solved.',                        NULL, NOW(), NULL, NULL);

-- LMS Attendance
SET @cs101 = (SELECT course_id FROM courses WHERE course_code = 'CS101' LIMIT 1);
SET @cs102 = (SELECT course_id FROM courses WHERE course_code = 'CS102' LIMIT 1);
SET @se204 = (SELECT course_id FROM courses WHERE course_code = 'SE204' LIMIT 1);
SET @cs301 = (SELECT course_id FROM courses WHERE course_code = 'CS301' LIMIT 1);

INSERT INTO lms_enrollments (student_user_id, course_id)
SELECT u.user_id, c.course_id
FROM users u
CROSS JOIN courses c
WHERE u.role_id = @student_role AND c.semester_name = 'Spring 2026'
ON DUPLICATE KEY UPDATE enrolled_at = enrolled_at;

-- Note: LMS attendance uses lms_enrollments for student-course links.
-- The main attendance table uses students(student_id). For LMS, attendance
-- is tracked in lms_enrollments with class_date and status added as needed by the app.
-- Since the LMS PHP code references `attendance` directly, we keep using the main
-- attendance table but with LMS user IDs mapped.

-- LMS Internal Marks: comprehensive marks for all students
INSERT INTO lms_marks (course_id, student_user_id, component, marks_obtained, total_marks)
SELECT
    c.course_id,
    u.user_id,
    m.component,
    m.marks_obtained,
    m.total_marks
FROM users u
CROSS JOIN courses c
CROSS JOIN (
    SELECT 'assignment_1' AS component, 3 AS marks_obtained, 10 AS total_marks UNION ALL
    SELECT 'assignment_2', 5, 10 UNION ALL
    SELECT 'assignment_3', 2, 10 UNION ALL
    SELECT 'test_1', 7, 10 UNION ALL
    SELECT 'test_2', 4, 10 UNION ALL
    SELECT 'test_3', 6, 10 UNION ALL
    SELECT 'presentation', 8, 10 UNION ALL
    SELECT 'major_assignment', 9, 10 UNION ALL
    SELECT 'mid_term', 20, 25
) m
WHERE u.role_id = @student_role
  AND c.semester_name = 'Spring 2026'
  AND u.login_id IN ('9001','9002','9003','9004','9005','9006','9007','9008','9009','9010')
ON DUPLICATE KEY UPDATE marks_obtained = m.marks_obtained;

-- LMS Queries
INSERT INTO lms_queries (user_id, subject, message, status, reply, created_at) VALUES
    ((SELECT user_id FROM users WHERE login_id = '9001'), 'Attendance correction', 'Please check my attendance for CS101.', 'open', NULL, NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9002'), 'Assignment deadline',   'Can the assignment deadline be extended?', 'open', NULL, NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9003'), 'Marks review',          'Please review my recent mid term marks.', 'open', NULL, NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9004'), 'Course material',       'Where can I find the slides for OOP?', 'answered', 'Check the lectures section.', NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9005'), 'Lab access',            'I cannot access the database lab.', 'open', NULL, NOW());

-- LMS Applications
INSERT INTO lms_applications (user_id, type, details, status, created_at) VALUES
    ((SELECT user_id FROM users WHERE login_id = '9001'), 'Leave Request',          'Medical leave for one day.',                    'pending',  NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9002'), 'Leave Request',          'Family event on Monday.',                       'pending',  NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9003'), 'Course Withdrawal',      'I want to drop SE302 this semester.',           'pending',  NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9004'), 'Transcript Request',     'Need official transcript for job application.', 'approved', NOW()),
    ((SELECT user_id FROM users WHERE login_id = '9005'), 'Semester Freeze',        'Medical emergency, need to freeze this semester.','pending', NOW());

-- LMS Notifications
INSERT INTO lms_notifications (recipient_user_id, sender_user_id, category, title, body, link_url, is_read, created_at)
SELECT u.user_id, @t1, 'notification', 'New assignment posted', CONCAT('An assignment has been uploaded for ', c.course_code, '.'), 'student/submissions.php', 0, NOW()
FROM users u
CROSS JOIN courses c
WHERE u.role_id = @student_role
  AND c.semester_name = 'Spring 2026'
  AND u.login_id IN ('9001','9002','9003','9004','9005')
LIMIT 50;

INSERT INTO lms_notifications (recipient_user_id, sender_user_id, category, title, body, link_url, is_read, created_at)
SELECT u.user_id, @t1, 'message', 'Welcome to University LMS', 'Check your courses and keep an eye on assignments in the dashboard.', 'student/dashboard.php', 0, NOW()
FROM users u
WHERE u.role_id = @student_role
  AND u.login_id IN ('9006','9007','9008','9009','9010');

INSERT INTO lms_notifications (recipient_user_id, sender_user_id, category, title, body, link_url, is_read, created_at)
SELECT u.user_id, @t1, 'announcement', 'Semester update', 'Spring 2026 notices will appear here.', 'student/dashboard.php', 0, NOW()
FROM users u
WHERE u.role_id = @student_role
  AND u.login_id IN ('9011','9012','9013','9014','9015');

-- LMS Fee Records
INSERT INTO lms_fee_records (student_user_id, semester, description, amount, paid_amount, due_date, status)
SELECT u.user_id, 'Spring 2026', 'Semester Tuition Fee', 85000.00,
    CASE
        WHEN u.login_id IN ('9001','9004','9006','9008','9010','9012','9014','9016','9018','9020') THEN 85000.00
        WHEN u.login_id IN ('9002','9007','9011','9015','9019') THEN 42500.00
        ELSE 0.00
    END,
    '2026-07-15',
    CASE
        WHEN u.login_id IN ('9001','9004','9006','9008','9010','9012','9014','9016','9018','9020') THEN 'paid'
        WHEN u.login_id IN ('9002','9007','9011','9015','9019') THEN 'partial'
        ELSE 'unpaid'
    END
FROM users u
WHERE u.role_id = @student_role;

INSERT INTO lms_fee_records (student_user_id, semester, description, amount, paid_amount, due_date, status)
SELECT u.user_id, 'Spring 2026', 'Library and Lab Charges', 7500.00, 7500.00, '2026-07-15', 'paid'
FROM users u
WHERE u.role_id = @student_role AND u.login_id IN ('9001','9002','9003','9004','9005');

-- =====================================================================
-- END OF SCRIPT
-- =====================================================================
