# TaskHive - Programming Teams Management Platform

![TaskHive Logo](https://img.shields.io/badge/TaskHive-Team%20Management-blueviolet?style=for-the-badge)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue?style=flat-square)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange?style=flat-square)
![Bootstrap](https://img.shields.io/badge/Bootstrap-3.4.1-purple?style=flat-square)

## 📋 Overview

TaskHive is a comprehensive web-based platform designed for managing programming teams and distributing coding tasks efficiently. Team leaders can create teams, invite members via temporary links, assign programming tasks, and review submitted work.

## ✨ Features

### 🔐 User Management
- ✅ User registration with profile photo upload
- ✅ Secure login system with password hashing
- ✅ Role-based access control (Admin & User)
- ✅ Profile picture support

### 👨‍💼 Admin Panel
- ✅ View all users
- ✅ Add new users
- ✅ Edit user information
- ✅ Delete users
- ✅ Change user roles
- ✅ Reset user passwords

### 👥 Team Management
- ✅ Create development teams
- ✅ Generate temporary invite links (24-hour expiry)
- ✅ Join teams via invite link
- ✅ View team members
- ✅ Team leader designation

### 📝 Task Management
- ✅ Create programming tasks
- ✅ Assign tasks to specific members or entire team
- ✅ Task status tracking (Pending, Submitted, Approved, Rejected)
- ✅ Task descriptions and requirements

### 📤 Submission System
- ✅ Upload code files/archives
- ✅ Add comments to submissions
- ✅ File type validation (zip, rar, pdf, php, html, css, js, py, java, cpp, c, txt)
- ✅ File size limits (10MB for submissions, 5MB for profile pictures)
- ✅ Download submitted files

### ✅ Review System
- ✅ Team leaders can approve tasks
- ✅ Team leaders can reject tasks
- ✅ View all submissions with timestamps
- ✅ Member comments visible to leaders

## 🛠️ Technologies Used

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 3.4.1
- **Libraries**: jQuery 3.5.1
- **Security**: Prepared Statements, Password Hashing, Input Validation

## 📦 Installation

### Prerequisites
- Apache Server (XAMPP, WAMP, or AppServ)
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Setup Steps

1. **Clone or Download** the project to your web server directory:
   ```bash
   cd C:\AppServ\www\
   ```

2. **Create Database**:
   - Open phpMyAdmin or MySQL command line
   - Run the following command:
   ```bash
   mysql -u root -p < db.sql
   ```
   Or import `db.sql` via phpMyAdmin

3. **Configure Database Connection**:
   - Edit `includes/db_connect.php`
   - Update credentials if needed:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "your_password";
   $dbname = "TaskHive_db";
   ```

4. **Set Permissions**:
   - Ensure `uploads/` folder has write permissions
   ```bash
   chmod 777 uploads/
   ```

5. **Access the Application**:
   - Open browser: `http://localhost/TaskHive/`

## 👤 Default Admin Account

After database installation, you can login with:
- **Username**: admin
- **Email**: admin@TaskHive.com
- **Password**: admin123

⚠️ **Change this password immediately after first login!**

## 📁 Project Structure

```
TaskHive/
├── includes/
│   ├── db_connect.php      # Database connection
│   └── functions.php       # Helper functions
├── js/
│   └── main.js            # JavaScript enhancements
├── style/
│   └── main.css           # Enhanced CSS styles
├── uploads/               # File uploads directory
├── index.php              # Landing page
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # User dashboard
├── admin.php              # Admin panel
├── admin_user_form.php    # Add/Edit user form
├── create_team.php        # Team creation
├── create_task.php        # Task creation
├── join_team.php          # Join team via invite
├── upload_submission.php  # File upload handler
├── review_task.php        # Task review handler
├── logout.php             # Logout handler
└── db.sql                # Database schema
```

## 🔒 Security Features

- ✅ **SQL Injection Protection**: All queries use prepared statements
- ✅ **XSS Protection**: Input sanitization and output encoding
- ✅ **Password Security**: Bcrypt hashing algorithm
- ✅ **File Upload Validation**: Type and size restrictions
- ✅ **Session Management**: Secure session handling
- ✅ **Access Control**: Role-based permissions

## 🎨 Design Features

- 🎨 Modern gradient design with animations
- 📱 Responsive layout (mobile-friendly)
- ✨ Smooth transitions and hover effects
- 🌈 Color-coded status labels
- 💫 Interactive UI elements
- 📊 Enhanced tables with hover effects
- 🔔 Animated alerts and notifications

## 📊 Database Schema

### Tables

1. **users**: User accounts and profiles
2. **teams**: Development teams
3. **team_members**: Team membership relations
4. **tasks**: Programming tasks
5. **submissions**: Task submissions

### Relationships
- Teams → Users (leader_id)
- Team Members → Teams & Users
- Tasks → Teams
- Submissions → Tasks & Users

## 🚀 Usage Guide

### For Regular Users

1. **Register**: Create account with profile photo
2. **Join/Create Team**: Create your own team or join via invite link
3. **View Tasks**: See assigned tasks in dashboard
4. **Submit Work**: Upload code files with comments
5. **Track Status**: Monitor task approval status

### For Team Leaders

1. **Create Team**: Set up development team
2. **Invite Members**: Share invite link (valid 24 hours)
3. **Create Tasks**: Assign programming tasks
4. **Review Submissions**: Download and review work
5. **Approve/Reject**: Manage task completion

### For Administrators

1. **User Management**: Add, edit, delete users
2. **Role Assignment**: Set user roles (admin/user)
3. **Password Reset**: Change user passwords
4. **System Oversight**: Monitor all activities

## 🐛 Troubleshooting

### File Upload Issues
```php
// Check php.ini settings:
upload_max_filesize = 10M
post_max_size = 10M
```

### Database Connection Error
- Verify MySQL is running
- Check credentials in `db_connect.php`
- Ensure database exists

### Permission Denied
```bash
# Fix uploads folder permissions
chmod 777 uploads/
```

## 📝 Future Enhancements

- [ ] Email notifications
- [ ] Real-time chat
- [ ] Code review comments
- [ ] GitHub integration
- [ ] Project milestones
- [ ] Team analytics
- [ ] Export reports

## 👨‍💻 Development

Built with ❤️ using:
- Clean code principles
- Prepared statements for security
- Responsive design patterns
- Modern UI/UX practices

## 📄 License

This project is developed for educational purposes.

## 🤝 Support

For issues or questions:
1. Check the troubleshooting section
2. Review code comments
3. Verify database setup
4. Check server logs

---

**Made with 💜 for programming teams everywhere**
