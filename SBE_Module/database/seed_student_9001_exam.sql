INSERT INTO sbe_exams (
    exam_code,
    course_id,
    teacher_id,
    title,
    exam_type,
    instructions,
    duration_minutes,
    total_questions,
    total_marks,
    passing_marks,
    selection_mode,
    negative_marking,
    shuffle_questions,
    shuffle_options,
    allow_review,
    status
)
SELECT
    'SBE-9001-TEST',
    101,
    5001,
    'SBE Demo Test for Student 9001',
    'Practice',
    'Read each MCQ carefully and select the best answer.',
    30,
    3,
    3.00,
    2.00,
    'Manual',
    0.00,
    0,
    0,
    1,
    'Published'
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_exams WHERE exam_code = 'SBE-9001-TEST'
);

SET @exam_id := (
    SELECT exam_id FROM sbe_exams WHERE exam_code = 'SBE-9001-TEST' LIMIT 1
);

INSERT INTO sbe_question_bank (
    course_id,
    teacher_id,
    topic,
    question_text,
    option_a,
    option_b,
    option_c,
    option_d,
    correct_option,
    explanation,
    marks,
    difficulty_level,
    status
)
SELECT 101, 5001, 'SBE Demo Test', 'What does SBE stand for in this module?', 'System Based Examination', 'Student Billing Entry', 'Semester Batch Evaluation', 'Subject Book Exchange', 'A', 'SBE is the System Based Examination module.', 1.00, 'Easy', 'Active'
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_question_bank WHERE topic = 'SBE Demo Test' AND question_text = 'What does SBE stand for in this module?'
);

INSERT INTO sbe_question_bank (
    course_id,
    teacher_id,
    topic,
    question_text,
    option_a,
    option_b,
    option_c,
    option_d,
    correct_option,
    explanation,
    marks,
    difficulty_level,
    status
)
SELECT 101, 5001, 'SBE Demo Test', 'Which user role attempts exams from the exam room?', 'Teacher', 'Student', 'Admin', 'Guest', 'B', 'Students attempt exams from the student exam room.', 1.00, 'Easy', 'Active'
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_question_bank WHERE topic = 'SBE Demo Test' AND question_text = 'Which user role attempts exams from the exam room?'
);

INSERT INTO sbe_question_bank (
    course_id,
    teacher_id,
    topic,
    question_text,
    option_a,
    option_b,
    option_c,
    option_d,
    correct_option,
    explanation,
    marks,
    difficulty_level,
    status
)
SELECT 101, 5001, 'SBE Demo Test', 'What must be true before a student can launch an exam?', 'The exam is archived', 'The schedule is ongoing', 'The account is inactive', 'The exam has no questions', 'B', 'The student launch page only shows ongoing schedules for published exams.', 1.00, 'Medium', 'Active'
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_question_bank WHERE topic = 'SBE Demo Test' AND question_text = 'What must be true before a student can launch an exam?'
);

SET @q1 := (
    SELECT question_id FROM sbe_question_bank
    WHERE topic = 'SBE Demo Test'
      AND question_text = 'What does SBE stand for in this module?'
    LIMIT 1
);
SET @q2 := (
    SELECT question_id FROM sbe_question_bank
    WHERE topic = 'SBE Demo Test'
      AND question_text = 'Which user role attempts exams from the exam room?'
    LIMIT 1
);
SET @q3 := (
    SELECT question_id FROM sbe_question_bank
    WHERE topic = 'SBE Demo Test'
      AND question_text = 'What must be true before a student can launch an exam?'
    LIMIT 1
);

INSERT INTO sbe_exam_questions (exam_id, question_id, question_order)
SELECT @exam_id, @q1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_exam_questions WHERE exam_id = @exam_id AND question_order = 1
);

INSERT INTO sbe_exam_questions (exam_id, question_id, question_order)
SELECT @exam_id, @q2, 2
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_exam_questions WHERE exam_id = @exam_id AND question_order = 2
);

INSERT INTO sbe_exam_questions (exam_id, question_id, question_order)
SELECT @exam_id, @q3, 3
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_exam_questions WHERE exam_id = @exam_id AND question_order = 3
);

INSERT INTO sbe_exam_schedule (
    exam_id,
    section,
    semester_id,
    exam_date,
    start_time,
    end_time,
    late_submission_grace_minutes,
    location,
    remarks,
    status
)
SELECT
    @exam_id,
    'CS-A',
    4,
    CURDATE(),
    '08:00:00',
    '23:59:00',
    10,
    'SBE Demo Lab',
    '9001 demo test schedule',
    'Ongoing'
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_exam_schedule WHERE exam_id = @exam_id AND remarks = '9001 demo test schedule'
);

SET @schedule_id := (
    SELECT schedule_id FROM sbe_exam_schedule
    WHERE exam_id = @exam_id
      AND remarks = '9001 demo test schedule'
    LIMIT 1
);

INSERT INTO sbe_student_exams (
    schedule_id,
    exam_id,
    student_id,
    attempt_no,
    status,
    started_at,
    expires_at
)
SELECT
    @schedule_id,
    @exam_id,
    9001,
    1,
    'In Progress',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 30 MINUTE)
WHERE NOT EXISTS (
    SELECT 1 FROM sbe_student_exams WHERE schedule_id = @schedule_id AND student_id = 9001 AND attempt_no = 1
);
