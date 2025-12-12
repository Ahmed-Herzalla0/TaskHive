<?php
require_once __DIR__ . '/../bootstrap.php';

if (!function_exists('th_nav_template')) {
    function th_nav_template(string $template = 'default', array $overrides = []): array
    {
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Account';
        // نحدد اسم يوزر افتراضي إذا لم يكن المستخدم مسجلاً الدخول
        $templates = [
            'default' => [
                'brand' => 'TaskHive',
                'brand_link' => '/index.php',
                'brand_icon' => '🐝',
                'theme' => 'primary',
                'links' => [],
                'actions' => [],
            ],
            'landing' => [
                'brand' => 'TaskHive',
                'brand_link' => 'index.php',
                'brand_icon' => '🐝',
                'theme' => 'landing',
                'class' => '',
                'links' => [
                    ['label' => 'Overview', 'href' => '#hero', 'slug' => 'overview'],
                    ['label' => 'Features', 'href' => '#features', 'slug' => 'features'],
                    ['label' => 'About', 'href' => '#about', 'slug' => 'about'],
                ],
                'actions' => [
                    ['label' => 'Login', 'href' => 'auth/login.php', 'style' => 'ghost', 'slug' => 'login'],
                    ['label' => 'Get Started', 'href' => 'auth/register.php', 'style' => 'primary', 'slug' => 'register'],
                ],
            ],
            'user' => [
                'brand' => 'TaskHive',
                'brand_link' => '../user/dashboard.php',
                'brand_icon' => '🐝',
                'theme' => 'primary',
                'links' => [
                    ['label' => 'Dashboard', 'href' => '../user/dashboard.php', 'icon' => 'glyphicon glyphicon-home', 'slug' => 'dashboard'],
                    ['label' => 'Create Team', 'href' => '../team/create_team.php', 'icon' => 'glyphicon glyphicon-plus', 'slug' => 'create-team'],
                    ['label' => 'Join Team', 'href' => '../team/join_team.php', 'icon' => 'glyphicon glyphicon-log-in', 'slug' => 'join-team'],
                ],
                'actions' => [
                    ['label' => $username, 'href' => '../user/edit_profile.php', 'icon' => 'glyphicon glyphicon-user', 'style' => 'ghost', 'slug' => 'profile'],
                    ['label' => 'Logout', 'href' => '../auth/logout.php', 'icon' => 'glyphicon glyphicon-log-out', 'style' => 'danger', 'slug' => 'logout'],
                ],
            ],
            'admin' => [
                'brand' => 'TaskHive Admin',
                'brand_link' => 'admin_dashboard.php',
                'brand_icon' => '🐝',
                'theme' => 'admin',
                'links' => [
                    ['label' => 'Dashboard', 'href' => 'admin_dashboard.php', 'icon' => 'glyphicon glyphicon-home', 'slug' => 'dashboard'],
                    ['label' => 'Users', 'href' => 'admin.php', 'icon' => 'glyphicon glyphicon-user', 'slug' => 'users'],
                    ['label' => 'Teams', 'href' => 'admin.php?view=teams', 'icon' => 'glyphicon glyphicon-flag', 'slug' => 'teams'],
                    ['label' => 'Tasks', 'href' => 'admin.php?view=tasks', 'icon' => 'glyphicon glyphicon-tasks', 'slug' => 'tasks'],
                    ['label' => 'System', 'href' => 'status.php', 'icon' => 'glyphicon glyphicon-cog', 'slug' => 'system'],
                    ['label' => 'Activity', 'href' => 'activity_log.php', 'icon' => 'glyphicon glyphicon-time', 'slug' => 'activity'],
                ],
                'actions' => [
                    ['label' => $username, 'href' => 'admin_dashboard.php', 'icon' => 'glyphicon glyphicon-dashboard', 'style' => 'ghost', 'slug' => 'admin-profile'],
                    ['label' => 'Logout', 'href' => '../auth/logout.php', 'icon' => 'glyphicon glyphicon-log-out', 'style' => 'danger', 'slug' => 'logout'],
                ],
            ],
        ];
        $template = $templates[$template] ?? $templates['default']; // إذا طلب المستخدم قالب غير موجود، يتم استخدام القالب الافتراضي

        if (isset($overrides['links'])) {
            $template['links'] = $overrides['links'];
            unset($overrides['links']);
        }
        // منسوي reset
        if (isset($overrides['actions'])) {
            $template['actions'] = $overrides['actions'];
            unset($overrides['actions']);
        }
        // منسوي reset
        return array_replace_recursive($template, $overrides); //دمج مصفوفتين مع استبدال القيم المتكررة 
    }
}

if (!function_exists('render_unified_navbar')) {
    function render_unified_navbar(array $config = []): void
    {
        $defaults = [
            'brand' => 'TaskHive',
            'brand_link' => '/',
            'brand_icon' => '🐝',
            'theme' => 'primary',
            'class' => '',
            'links' => [],
            'actions' => [],
            'active' => null,
            'menu_id' => 'th-nav-' . substr(md5(uniqid('', true)), 0, 8),
        ];

        $config = array_replace_recursive($defaults, $config); // دمج المصفوفات مع استبدال القيم المتكررة
        $active = $config['active'];
        // تحديد الفئة classes
        $themeClass = 'th-navbar--' . $config['theme'];
        $classes = trim('th-navbar ' . $themeClass . ' ' . $config['class']);

        $brand = htmlspecialchars($config['brand'], ENT_QUOTES, 'UTF-8');
        $brandLink = htmlspecialchars($config['brand_link'], ENT_QUOTES, 'UTF-8');
        $brandIcon = $config['brand_icon'];
        
        $linksHtml = '';
        foreach ($config['links'] as $link) {
            $link = array_merge([
                'label' => '',
                'href' => '#',
                'slug' => null,
                'icon' => null,
                'target' => null,
                'badge' => null,
            ], $link);

            $slug = $link['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $link['label']));
            $isActive = $link['active'] ?? ($active !== null && $slug === $active);
            $href = htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8');
            $icon = $link['icon'] ? '<span class="' . htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') . '"></span>' : '';
            $target = $link['target'] ? ' target="' . htmlspecialchars($link['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener"' : '';
            $badge = $link['badge'] ? '<span class="th-navbar__badge">' . htmlspecialchars($link['badge'], ENT_QUOTES, 'UTF-8') . '</span>' : '';

            $linksHtml .= '<li class="th-navbar__item' . ($isActive ? ' is-active' : '') . '">';
            $linksHtml .= '<a href="' . $href . '"' . $target . '>';
            $linksHtml .= $icon ? $icon . ' ' : '';
            $linksHtml .= $label . $badge . '</a></li>';
        }

        $actionsHtml = '';
        foreach ($config['actions'] as $action) {
            $action = array_merge([
                'label' => '',
                'href' => '#',
                'slug' => null,
                'icon' => null,
                'style' => 'ghost',
                'target' => null,
            ], $action);

            $slug = $action['slug'] ?? strtolower(preg_replace('/[^a-z0-9]+/i', '-', $action['label']));
            $isActive = $action['active'] ?? ($active !== null && $slug === $active);
            $href = htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8');
            $icon = $action['icon'] ? '<span class="' . htmlspecialchars($action['icon'], ENT_QUOTES, 'UTF-8') . '"></span>' : '';
            $target = $action['target'] ? ' target="' . htmlspecialchars($action['target'], ENT_QUOTES, 'UTF-8') . '" rel="noopener"' : '';
            $styleClass = 'th-navbar__action--' . preg_replace('/[^a-z0-9-]/i', '', $action['style']);

            $actionsHtml .= '<a class="th-navbar__action ' . $styleClass . ($isActive ? ' is-active' : '') . '" href="' . $href . '"' . $target . '>';
            $actionsHtml .= $icon ? $icon . ' ' : '';
            $actionsHtml .= $label . '</a>';
        }

        $actionsWrapper = $actionsHtml ? '<div class="th-navbar__actions">' . $actionsHtml . '</div>' : '';

        echo '<header class="' . $classes . '" data-th-navbar>';
        echo '  <div class="th-navbar__inner">';
        echo '      <a class="th-navbar__brand" href="' . $brandLink . '">';
        echo '          <span class="th-navbar__logo">' . $brandIcon . '</span>';
        echo '          <span class="th-navbar__title">' . $brand . '</span>';
        echo '      </a>';
        echo '      <button class="th-navbar__toggle" type="button" aria-expanded="false" aria-controls="' . $config['menu_id'] . '" data-th-toggle>';
        echo '          <span></span><span></span><span></span>';
        echo '      </button>';
        echo '      <div class="th-navbar__menu" id="' . $config['menu_id'] . '" data-th-menu aria-hidden="true">';
        echo '          <div class="th-navbar__menu-body">';
        echo '              <ul class="th-navbar__links">' . $linksHtml . '</ul>';
        echo                    $actionsWrapper;
        echo '          </div>';
        echo '      </div>';
        echo '      <div class="th-navbar__overlay" data-th-overlay></div>';
        echo '  </div>';
        echo '</header>';
    }
}
