# QBox
# QBox - Online Quiz Web Application

QBox is a simple and modern online multiple-choice quiz platform built using HTML, CSS, PHP, and MySQL (XAMPP). It supports user and admin roles, has a timer, per-quiz score calculation, and a gradient UI for a clean, attractive interface.

# Technical Stack

Frontend: HTML, CSS (gradient UI)
Backend: PHP
Database: MySQL (phpMyAdmin via XAMPP)
Server: Apache (XAMPP)
Additional: CSV upload for quizzes

# QBox allows users to:

Register and login to the system
Take quizzes with 20 questions per session
View quiz results immediately after submission
Track quiz history

# Admins can:

Login to admin dashboard
Upload quiz questions via CSV
View all users’ quiz history
The system is built to be beginner-friendly and easily deployable on XAMPP.

# Features
# User Features

Register & login
Quiz page with 20 random questions per attempt
Submit answers and calculate score
View quiz results
View quiz history

# Admin Features

Admin login
Upload quiz questions via CSV
View all users’ quiz results and history

Optional: manage users
 # Project Structure 
 QBox/
├── index.php               
├── register.php            
├── logout.php               
├── config/
│   └── db.php             
├── assets/
│   ├── css/
│   │   └── style.css       
│   └── js/
│       └── script.js        
├── user/
│   ├── dashboard.php        
│   ├── quiz.php           
│   ├── result.php           
│   └── history.php         
├── admin/
│   ├── dashboard.php        
│   ├── upload_quiz.php      
│   ├── history.php          
│   └── manage_users.php    
├── csv/
│   └── sample_questions.csv # Sample CSV quiz
└── README.md                
