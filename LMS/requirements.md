Perfect, Zahra — since your stack is **HTML, CSS, JS, and PHP**, we’ll adapt the structure and schema to fit a **classic PHP + MySQL setup**. Here’s a refined requirements doc tailored to your tech stack:

---

## 📄 Software Requirements Specification (SRS)

### 1. Introduction
- **Purpose:** Build a PHP-based LMS/MIS system with student and teacher modules, connected to SSO for authentication and course assignments.
- **Scope:** Teachers manage attendance, lectures, assignments, grading, and internal marks. Students access resources, submit work, view grades, track attendance, and communicate with teachers. Applications and fee records sync with SSO.

---

### 2. Functional Requirements

#### 🧑‍🏫 Teacher (MIS)
- View assigned courses (SSO sync).
- Mark attendance.
- Upload lectures/resources.
- Create assignments with helping files.
- Grade submissions.
- Manage internal marks (Assignments, Quizzes, Major Assignment, Presentation, Midterm).
- Respond to student queries.
- Forward applications to SSO.

#### 🎓 Student (LMS)
- Dashboard: fee record, session/semester intake, profile picture.
- Access lectures/resources.
- Submit assignments.
- View grades and feedback.
- Track attendance.
- View internal marks breakdown.
- Send queries to teachers.
- Submit applications (forwarded to SSO).

---

### 3. Non-Functional Requirements
- **Security:** PHP sessions, hashed passwords, role-based access.
- **Performance:** Optimized MySQL queries, caching where possible.
- **Scalability:** Modular PHP structure for future features.
- **Usability:** Responsive UI with HTML/CSS/JS.

---

## 📂 Suggested File Structure (PHP Project)

```
LMS-MIS/
│
├── public/                # Public entry point
│   ├── index.php          # Main router
│   ├── login.php          # Authentication
│   └── assets/            # CSS, JS, images
│
├── teacher/               # Teacher module
│   ├── dashboard.php
│   ├── attendance.php
│   ├── assignments.php
│   ├── grading.php
│   ├── internal_marks.php
│   └── queries.php
│
├── student/               # Student module
│   ├── dashboard.php
│   ├── courses.php
│   ├── submissions.php
│   ├── attendance.php
│   ├── marks.php
│   └── queries.php
│
├── sso/                   # SSO integration
│   └── applications.php
│
├── includes/              # Shared PHP files
│   ├── db.php             # Database connection
│   ├── auth.php           # Authentication middleware
│   ├── header.php
│   └── footer.php
│
├── config/                # Configurations
│   └── config.php
│
└── docs/
    └── requirements.md
```

---

## 🗄️ Database Schema (MySQL)

### **users**
- `user_id` INT PK AUTO_INCREMENT
- `name` VARCHAR(100)
- `email` VARCHAR(100) UNIQUE
- `password` VARCHAR(255) (hashed)
- `role` ENUM('student','teacher','admin')
- `sso_id` VARCHAR(50)
- `profile_picture` VARCHAR(255)

### **courses**
- `course_id` INT PK AUTO_INCREMENT
- `course_name` VARCHAR(100)
- `teacher_id` INT FK → users(user_id)
- `semester` VARCHAR(20)
- `sso_course_id` VARCHAR(50)

### **attendance**
- `attendance_id` INT PK AUTO_INCREMENT
- `course_id` INT FK → courses(course_id)
- `student_id` INT FK → users(user_id)
- `date` DATE
- `status` ENUM('Present','Absent')

### **assignments**
- `assignment_id` INT PK AUTO_INCREMENT
- `course_id` INT FK → courses(course_id)
- `title` VARCHAR(100)
- `description` TEXT
- `helping_file` VARCHAR(255)
- `due_date` DATE

### **submissions**
- `submission_id` INT PK AUTO_INCREMENT
- `assignment_id` INT FK → assignments(assignment_id)
- `student_id` INT FK → users(user_id)
- `file_path` VARCHAR(255)
- `grade` INT

### **internal_marks**
- `marks_id` INT PK AUTO_INCREMENT
- `student_id` INT FK → users(user_id)
- `course_id` INT FK → courses(course_id)
- `assignments` INT
- `quizzes` INT
- `major_assignment` INT
- `presentation` INT
- `midterm` INT
- `total` INT (calculated, max 50)

### **queries**
- `query_id` INT PK AUTO_INCREMENT
- `student_id` INT FK → users(user_id)
- `teacher_id` INT FK → users(user_id)
- `message` TEXT
- `response` TEXT

### **applications**
- `application_id` INT PK AUTO_INCREMENT
- `student_id` INT FK → users(user_id)
- `course_id` INT FK → courses(course_id)
- `status` ENUM('Pending','Forwarded','Resolved')
- `forwarded_to` VARCHAR(50)

### **fees**
- `fee_id` INT PK AUTO_INCREMENT
- `student_id` INT FK → users(user_id)
- `amount` DECIMAL(10,2)
- `status` ENUM('Paid','Unpaid')
- `semester` VARCHAR(20)

---

✅ This structure is now **PHP + MySQL ready**, with clear separation of teacher/student modules, reusable includes, and a normalized schema.  

Would you like me to also sketch out a **basic ER diagram** (entities + relationships) so you can visually present it to your supervisor?