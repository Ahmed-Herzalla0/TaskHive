<?php require_once 'config/includes/navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskHive - Collaborative Team & Task Management Platform</title>
    <meta name="description" content="TaskHive - The ultimate platform for managing programming teams, tasks, and project submissions efficiently with version control and collaboration features">
    <meta name="keywords" content="team management, task management, project collaboration, submission tracking, version control, team collaboration, task assignment, project management, programming teams, developer collaboration">
    <meta name="author" content="AhmadHerzalla">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>🐝</text></svg>">
    <link rel="apple-touch-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='0.9em' font-size='90'>🐝</text></svg>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>

<?php render_unified_navbar(th_nav_template('landing', [
        'links' => [
                ['label' => 'Overview', 'href' => '#hero', 'slug' => 'overview'],
                ['label' => 'Features', 'href' => '#features', 'slug' => 'features'],
                ['label' => 'About', 'href' => '#about', 'slug' => 'about'],
        ],
])); ?>

<div class="hero hero-section" id="hero">
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="container hero-content text-center">
        <h1 class="animate-fadeInDown">Collaborate & Build<br>Together with <span class="text-highlight">TaskHive</span></h1>
        <p class="animate-fadeInUp">The ultimate platform for managing programming teams, tasks, and project submissions with advanced version control and real-time collaboration.</p>
        <div class="hero-buttons animate-fadeInUp">
            <a href="auth/register.php" class="btn btn-warning btn-xl btn-rounded">🚀 Get Started Free</a>
            <a href="auth/login.php" class="btn btn-ghost btn-xl btn-rounded btn-ghost-light">Sign In</a>
        </div>
        <div class="hero-badges animate-fadeInUp">
            <p>✓ Free to use &nbsp;&nbsp; ✓ No credit card required &nbsp;&nbsp; ✓ Unlimited teams</p>
        </div>
    </div>
</div>

<section id="features" class="features-section">
    <div class="container">
    <div class="text-center section-header">
        <h2>Why Choose <span class="text-gradient">TaskHive</span>?</h2>
        <p class="section-subtitle">Everything you need to manage your development team efficiently</p>
    </div>
    <div class="row stagger-animation features-row">
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-icon"><span class="glyphicon glyphicon-user"></span></div>
                <h3>Create Teams</h3>
                <p>Form your own programming teams and invite members easily using a unique invite link. Manage team roles and permissions effortlessly.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-icon"><span class="glyphicon glyphicon-tasks"></span></div>
                <h3>Assign Tasks</h3>
                <p>Leaders can distribute programming tasks to team members and track their progress with real-time status updates.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-icon"><span class="glyphicon glyphicon-cloud-upload"></span></div>
                <h3>Submit Work</h3>
                <p>Team members can upload their code submissions with version control for review by the team leader.</p>
            </div>
        </div>
    </div>
    <div class="row stagger-animation features-row-second">
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-icon"><span class="glyphicon glyphicon-refresh"></span></div>
                <h3>Version Control</h3>
                <p>Track all submission versions with major and minor versioning. Never lose your work with complete history.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-icon"><span class="glyphicon glyphicon-ok-circle"></span></div>
                <h3>Review & Approve</h3>
                <p>Team leaders can review, approve or request changes on submissions with detailed feedback.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-box">
                <div class="feature-icon"><span class="glyphicon glyphicon-stats"></span></div>
                <h3>Track Progress</h3>
                <p>Monitor team performance with intuitive dashboards and progress tracking for all tasks.</p>
            </div>
        </div>
    </div>
</section>

<section id="about" style="background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 100px 0;">
    <div class="container">
        <div class="row align-center">
            <div class="col-md-6">
                <h2 style="font-size: 36px; margin-bottom: 24px;">Built for <span class="text-gradient">Modern Teams</span></h2>
                <p style="color: var(--gray-600); font-size: 18px; line-height: 1.8; margin-bottom: 24px;">
                    TaskHive is designed to help development teams collaborate more effectively. 
                    Whether you're working on a small project or managing a large team, 
                    our platform provides all the tools you need.
                </p>
                <ul style="list-style: none; padding: 0; color: var(--gray-600);">
                    <li style="padding: 8px 0;">✅ Easy team creation and management</li>
                    <li style="padding: 8px 0;">✅ Advanced task assignment system</li>
                    <li style="padding: 8px 0;">✅ Built-in version control for submissions</li>
                    <li style="padding: 8px 0;">✅ Real-time progress tracking</li>
                    <li style="padding: 8px 0;">✅ Code review and approval workflow</li>
                </ul>
            </div>
            <div class="col-md-6 text-center">
                <div style="background: var(--primary-gradient); border-radius: 20px; padding: 60px 40px; color: white; box-shadow: var(--shadow-xl);">
                    <div style="font-size: 80px; margin-bottom: 20px;">🐝</div>
                    <h3 style="color: white; font-size: 28px; margin-bottom: 16px;">Join the Hive</h3>
                    <p style="opacity: 0.9; margin-bottom: 24px;">Start collaborating with your team today</p>
                    <a href="auth/register.php" class="btn btn-warning btn-lg btn-rounded">Create Free Account</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section style="background: var(--primary-gradient); padding: 80px 0; text-align: center; color: white;">
    <div class="container">
        <h2 style="color: white; font-size: 36px; margin-bottom: 16px;">Ready to boost your team's productivity?</h2>
        <p style="opacity: 0.9; font-size: 18px; margin-bottom: 32px;">Join thousands of teams already using TaskHive to streamline their workflow.</p>
        <a href="auth/register.php" class="btn btn-warning btn-xl btn-rounded">🚀 Get Started Now - It's Free</a>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h4 style="color: white; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 28px;">🐝</span> TaskHive
                </h4>
                <p style="margin-top: 16px;">The ultimate platform for managing programming teams, tasks, and project submissions efficiently.</p>
            </div>
            <div class="col-md-2 col-md-offset-2">
                <h5>Product</h5>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 4px 0;"><a href="#features">Features</a></li>
                    <li style="padding: 4px 0;"><a href="#about">About</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h5>Account</h5>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 4px 0;"><a href="auth/login.php">Login</a></li>
                    <li style="padding: 4px 0;"><a href="auth/register.php">Sign Up</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h5>Legal</h5>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 4px 0;"><a href="#">Privacy</a></li>
                    <li style="padding: 4px 0;"><a href="#">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p style="margin: 0;">&copy; <?php echo date('Y'); ?> TaskHive. All rights reserved. | Made with ❤️ by AhmadHerzalla</p>
        </div>
    </div>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Navbar scroll effect
$(window).on('load scroll', function() {
    var $landingNav = $('[data-th-navbar].th-navbar--landing');
    if (!$landingNav.length) {
        return;
    }
    if ($(this).scrollTop() > 50) {
        $landingNav.addClass('th-navbar--scrolled');
    } else {
        $landingNav.removeClass('th-navbar--scrolled');
    }
});

// Smooth scroll for anchor links
$('a[href^="#"]').on('click', function(e) {
    var target = $(this.hash);
    if (target.length) {
        e.preventDefault();
        $('html, body').animate({
            scrollTop: target.offset().top - 80
        }, 600);
    }
});
</script>
</body>
</html>
