# University LMS database

This folder contains the import files for a fresh setup.

1. Import `schema.sql` into MySQL first.
2. Import `seed.sql` right after it.

Demo login:

- Teacher: `teacher@lms.test`
- Student: `student@lms.test`
- Password for both: `password123`

The app will still auto-create and seed the database at runtime, but these SQL files make the project easy to share on GitHub and easy to restore on a new machine.
