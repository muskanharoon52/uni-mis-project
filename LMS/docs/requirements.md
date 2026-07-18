LMS/
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
    └── requirements.md   [make this in our folder]
```
