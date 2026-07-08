# System Based Examination (SBE) Module
## University ERP Project

# Project Overview

We are developing a complete University ERP System for a university.

The project consists of multiple independent modules which communicate with each other.

Modules include:

- Admission
- Student Services Office (SSO)
- Finance
- Management Information System (MIS)
- Learning Management System (LMS)
- System Based Examination (SBE)
- Administration

Every module has its own independent MySQL database first.

Modules will integrate later through application logic and agreed identifiers, not cross-database foreign keys during initial development.

The backend database is MySQL.

The project should be designed using proper normalization, module-local foreign keys only, and scalable database practices.

---

# Existing Modules

## Admission

Admission creates student records.

Students already exist before entering SSO.

SBE should NEVER create students.

---

## Student Services Office (SSO)

SSO is the master academic module.

It already stores:

- Students
- Teachers
- Departments
- Sessions
- Semesters
- Courses
- Teacher Course Assignments
- Timetable
- Applications

SBE MUST read these records.

SBE must NEVER duplicate these tables.

Examples:

course_id
teacher_id
student_id

will come from SSO.

---

## Finance

Finance handles

- Fee Heads
- Student Fee
- Installments
- Payments
- Receipts

SBE does not interact directly with Finance.

---

## MIS

MIS handles

- Attendance
- Lecture Uploads
- Assignments
- Internal Marks

SBE is separate from MIS.

Only examination data belongs to SBE.

---

# SBE Module Purpose

The System Based Examination (SBE) module provides online MCQ-based examinations.

Teachers can:

- Upload question banks
- Manage questions
- Create exams
- Schedule exams
- Select duration
- Select total questions
- Select examination location
- Publish exams
- View results

Students can:

- View available exams
- Start exams
- Attempt MCQs
- Submit exams
- View results (if published)

The system automatically checks answers after submission.

---

# Examination Flow

Teacher Login

↓

Question Bank

↓

Create Exam

↓

Schedule Exam

↓

Publish Exam

↓

Student Login

↓

Start Exam

↓

Random Questions Generated

↓

Student Attempts Exam

↓

Submit

↓

Automatic Evaluation

↓

Teacher Views Result

↓

Student Views Result

---

# Randomization Rules

Every student should receive:

- Random questions
- Random question order
- Random option order

No two students should receive exactly the same paper whenever possible.

---

# Examination Rules

Students can only start the exam:

- On the scheduled date
- Between start time and end time

Students cannot:

- Attempt after submission
- Attempt after exam ends
- Attempt before exam starts

When exam duration expires:

The system automatically submits the exam.

---

# Result Rules

MCQs are checked automatically.

Each correct answer awards marks.

Wrong answers receive zero marks.

The system calculates:

- Obtained Marks
- Percentage
- Pass/Fail Status

Results become available to teachers immediately after submission.

Students can only see results after publication.

---

# Database Requirements

Use MySQL.

Use InnoDB Engine.

Use UTF8MB4 character set.

Use proper:

- Primary Keys
- Foreign Keys
- Indexes
- Constraints

Avoid duplicate data.

Normalize tables.

---

# Initial Database Scope

Develop only the SBE module.

Do NOT create tables for:

Students

Teachers

Courses

Departments

Semesters

Sessions

These already exist inside SSO.

Instead reference them using IDs.

Example:

teacher_id

course_id

student_id

---

# Initial Tables

The module should initially contain these tables.

1. question_bank

Stores every MCQ.

2. exams

Stores examination information.

3. exam_schedule

Stores date, time and location.

4. exam_questions

Maps questions to exams.

5. student_exams

Stores every student's attempt.

6. student_answers

Stores submitted answers.

7. exam_results

Stores final calculated results.

Additional tables can be added later if required.

---

# Question Bank Requirements

Each question belongs to one course and one teacher, but `course_id` and `teacher_id` are stored as indexed columns only for now.

Each question contains:

- Question Text
- Topic
- Option A
- Option B
- Option C
- Option D
- Correct Option
- Marks
- Difficulty Level
- Explanation
- Status

Questions should support future editing.

Questions should support activation/deactivation.

Questions should NOT be physically deleted.

Finalized table shape for review:

```sql
CREATE TABLE question_bank (
	question_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	course_id BIGINT UNSIGNED NOT NULL,
	teacher_id BIGINT UNSIGNED NOT NULL,
	topic VARCHAR(100) NOT NULL,
	question_text TEXT NOT NULL,
	option_a VARCHAR(255) NOT NULL,
	option_b VARCHAR(255) NOT NULL,
	option_c VARCHAR(255) NOT NULL,
	option_d VARCHAR(255) NOT NULL,
	correct_option CHAR(1) NOT NULL,
	explanation TEXT NULL,
	marks DECIMAL(6,2) NOT NULL DEFAULT 1.00,
	difficulty_level TINYINT UNSIGNED NOT NULL DEFAULT 2,
	status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (question_id),
	KEY idx_question_bank_course_id (course_id),
	KEY idx_question_bank_teacher_id (teacher_id),
	KEY idx_question_bank_topic (topic),
	KEY idx_question_bank_status (status),
	KEY idx_question_bank_course_status (course_id, status),
	CONSTRAINT chk_question_bank_correct_option CHECK (correct_option IN ('A', 'B', 'C', 'D')),
	CONSTRAINT chk_question_bank_marks CHECK (marks > 0),
	CONSTRAINT chk_question_bank_difficulty_level CHECK (difficulty_level BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Notes:

- `course_id` and `teacher_id` remain indexed only, with no foreign key constraints yet.
- `topic` is required so questions can be filtered and organized without needing a separate topic table.
- `explanation` is nullable so review text can be added later without forcing every row to contain it.
- `correct_option` uses `CHAR(1)` with a check constraint so the value stays compact and explicit.
- `option_a` through `option_d` use `VARCHAR(255)` to keep answers reasonably sized while remaining easy to render and process.

---

# Exam Requirements

Teacher creates an exam.

Teacher selects:

Course

Title

Duration

Total Questions

Passing Marks

Instructions

Status

Status may include:

Draft

Published

Closed

---

# Scheduling Requirements

Teacher schedules:

Date

Start Time

End Time

Location

Semester

Section

Only scheduled students can attempt.

---

# Student Attempt

Each student has one record per exam.

Store:

Start Time

Submission Time

Status

Obtained Marks

Students cannot have duplicate attempts.

---

# Student Answers

Store every selected option.

Each answer belongs to:

One student attempt

One question

System calculates whether answer is correct.

---

# Results

Store:

Total Marks

Obtained Marks

Percentage

Pass/Fail

Publication Status

---

# Coding Requirements

The project should be production ready.

Use:

- Foreign Keys
- Transactions where required
- Soft Deletes where appropriate
- Created At timestamps
- Updated At timestamps

Design for scalability.

Avoid redundant columns.

---

# Current Development Stage

Currently only the database is being developed.

No frontend.

No backend APIs.

Focus only on:

- Database Schema
- Relationships
- Constraints
- SQL Queries
- Sample Data
- Testing

The database should be designed first before any application code is written.