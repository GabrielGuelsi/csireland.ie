<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | Here you can change the default title of your admin panel.
    |
    | For detailed instructions you can look the title section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'title' => 'CICORE',
    'title_prefix' => '',
    'title_postfix' => ' | CICORE',

    /*
    |--------------------------------------------------------------------------
    | Favicon
    |--------------------------------------------------------------------------
    |
    | Here you can activate the favicon.
    |
    | For detailed instructions you can look the favicon section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_ico_only' => true,
    'use_full_favicon' => false,

    /*
    |--------------------------------------------------------------------------
    | Google Fonts
    |--------------------------------------------------------------------------
    |
    | Here you can allow or not the use of external google fonts. Disabling the
    | google fonts may be useful if your admin panel internet access is
    | restricted somehow.
    |
    | For detailed instructions you can look the google fonts section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'google_fonts' => [
        'allowed' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Logo
    |--------------------------------------------------------------------------
    |
    | Here you can change the logo of your admin panel.
    |
    | For detailed instructions you can look the logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'logo' => '<span style="color:#fff">CORE</span>',
    'logo_img' => 'img/ci-logo.png',
    'logo_img_class' => 'brand-image elevation-3',
    'logo_img_xl' => null,
    'logo_img_xl_class' => 'brand-image-xs',
    'logo_img_alt' => 'CICORE',

    /*
    |--------------------------------------------------------------------------
    | Authentication Logo
    |--------------------------------------------------------------------------
    |
    | Here you can setup an alternative logo to use on your login and register
    | screens. When disabled, the admin panel logo will be used instead.
    |
    | For detailed instructions you can look the auth logo section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'auth_logo' => [
        'enabled' => false,
        'img' => [
            'path' => 'vendor/adminlte/dist/img/AdminLTELogo.png',
            'alt' => 'Auth Logo',
            'class' => '',
            'width' => 50,
            'height' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preloader Animation
    |--------------------------------------------------------------------------
    |
    | Here you can change the preloader animation configuration. Currently, two
    | modes are supported: 'fullscreen' for a fullscreen preloader animation
    | and 'cwrapper' to attach the preloader animation into the content-wrapper
    | element and avoid overlapping it with the sidebars and the top navbar.
    |
    | For detailed instructions you can look the preloader section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'preloader' => [
        'enabled' => true,
        'mode' => 'fullscreen',
        'img' => [
            'path' => 'img/ci-logo.png',
            'alt' => 'CICORE',
            'effect' => 'animation__shake',
            'width' => 60,
            'height' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Menu
    |--------------------------------------------------------------------------
    |
    | Here you can activate and change the user menu.
    |
    | For detailed instructions you can look the user menu section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'usermenu_enabled' => true,
    'usermenu_header' => false,
    'usermenu_header_class' => 'bg-primary',
    'usermenu_image' => false,
    'usermenu_desc' => false,
    'usermenu_profile_url' => false,

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Here we change the layout of your admin panel.
    |
    | For detailed instructions you can look the layout section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'layout_topnav' => null,
    'layout_boxed' => null,
    'layout_fixed_sidebar' => null,
    'layout_fixed_navbar' => null,
    'layout_fixed_footer' => null,
    'layout_dark_mode' => null,

    /*
    |--------------------------------------------------------------------------
    | Authentication Views Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the authentication views.
    |
    | For detailed instructions you can look the auth classes section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_auth_card' => 'card-outline card-primary',
    'classes_auth_header' => '',
    'classes_auth_body' => '',
    'classes_auth_footer' => '',
    'classes_auth_icon' => '',
    'classes_auth_btn' => 'btn-flat btn-primary',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Classes
    |--------------------------------------------------------------------------
    |
    | Here you can change the look and behavior of the admin panel.
    |
    | For detailed instructions you can look the admin panel classes here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'classes_body' => '',
    'classes_brand' => '',
    'classes_brand_text' => '',
    'classes_content_wrapper' => '',
    'classes_content_header' => '',
    'classes_content' => '',
    'classes_sidebar' => 'sidebar-dark-primary elevation-4',
    'classes_sidebar_nav' => '',
    'classes_topnav' => 'navbar-white navbar-light border-bottom',
    'classes_topnav_nav' => 'navbar-expand',
    'classes_topnav_container' => 'container',

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar of the admin panel.
    |
    | For detailed instructions you can look the sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'sidebar_mini' => 'lg',
    'sidebar_collapse' => false,
    'sidebar_collapse_auto_size' => false,
    'sidebar_collapse_remember' => false,
    'sidebar_collapse_remember_no_transition' => true,
    'sidebar_scrollbar_theme' => 'os-theme-light',
    'sidebar_scrollbar_auto_hide' => 'l',
    'sidebar_nav_accordion' => true,
    'sidebar_nav_animation_speed' => 300,

    /*
    |--------------------------------------------------------------------------
    | Control Sidebar (Right Sidebar)
    |--------------------------------------------------------------------------
    |
    | Here we can modify the right sidebar aka control sidebar of the admin panel.
    |
    | For detailed instructions you can look the right sidebar section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Layout-and-Styling-Configuration
    |
    */

    'right_sidebar' => false,
    'right_sidebar_icon' => 'fas fa-cogs',
    'right_sidebar_theme' => 'dark',
    'right_sidebar_slide' => true,
    'right_sidebar_push' => true,
    'right_sidebar_scrollbar_theme' => 'os-theme-light',
    'right_sidebar_scrollbar_auto_hide' => 'l',

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Here we can modify the url settings of the admin panel.
    |
    | For detailed instructions you can look the urls section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Basic-Configuration
    |
    */

    'use_route_url' => false,
    'dashboard_url' => '/admin/dashboard',
    'logout_url' => 'logout',
    'login_url' => 'login',
    'register_url' => 'register',
    'password_reset_url' => 'password/reset',
    'password_email_url' => 'password/email',
    'profile_url' => false,
    'disable_darkmode_routes' => false,

    /*
    |--------------------------------------------------------------------------
    | Laravel Asset Bundling
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Laravel Asset Bundling option for the admin panel.
    | Currently, the next modes are supported: 'mix', 'vite' and 'vite_js_only'.
    | When using 'vite_js_only', it's expected that your CSS is imported using
    | JavaScript. Typically, in your application's 'resources/js/app.js' file.
    | If you are not using any of these, leave it as 'false'.
    |
    | For detailed instructions you can look the asset bundling section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'laravel_asset_bundling' => false,
    'laravel_css_path' => 'css/app.css',
    'laravel_js_path' => 'js/app.js',

    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Here we can modify the sidebar/top navigation of the admin panel.
    |
    | For detailed instructions you can look here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'menu' => [
        // Navbar
        [
            'type'         => 'fullscreen-widget',
            'topnav_right' => true,
        ],
        [
            'text'         => 'Language',
            'topnav_right' => true,
            'icon'         => 'fas fa-fw fa-language',
            'submenu'      => [
                [
                    'text' => 'English',
                    'url'  => '#',
                    'data' => ['lang' => 'en'],
                ],
                [
                    'text' => 'Português',
                    'url'  => '#',
                    'data' => ['lang' => 'pt-br'],
                ],
            ],
        ],

        // ---------- SALES PORTAL (prototype) ----------
        ['header' => 'SALES', 'can' => 'access-sales'],
        [
            'text'  => 'Sales Dashboard',
            'route' => 'sales.dashboard',
            'icon'  => 'fas fa-fw fa-tachometer-alt',
            'can'   => 'access-sales',
        ],
        [
            'text'  => 'Pipeline (Kanban)',
            'route' => 'sales.kanban',
            'icon'  => 'fas fa-fw fa-columns',
            'can'   => 'access-sales',
        ],
        [
            'text'  => 'New Lead',
            'route' => 'sales.leads.create',
            'icon'  => 'fas fa-fw fa-user-plus',
            'can'   => 'access-sales',
        ],
        [
            'text'  => 'My Students',
            'route' => 'sales.leads.ongoing',
            'icon'  => 'fas fa-fw fa-handshake',
            'can'   => 'access-sales',
        ],

        // ---------- CS AGENT PORTAL (/my) ----------
        ['header' => 'MY WORK', 'can' => 'access-my'],
        [
            'text'  => 'My Dashboard',
            'route' => 'my.dashboard',
            'icon'  => 'fas fa-fw fa-tachometer-alt',
            'can'   => 'access-my',
        ],
        [
            'text'  => 'My Students',
            'route' => 'my.students.index',
            'icon'  => 'fas fa-fw fa-user-graduate',
            'can'   => 'access-my',
        ],
        [
            'text'  => 'My Notifications',
            'route' => 'my.notifications.index',
            'icon'  => 'fas fa-fw fa-bell',
            'can'   => 'access-my',
        ],

        // ---------- ADMIN / APPLICATIONS ----------
        ['header' => 'MAIN', 'can' => 'access-admin'],
        [
            'text'  => 'Dashboard',
            'route' => 'admin.dashboard',
            'icon'  => 'fas fa-fw fa-tachometer-alt',
            'can'   => 'access-admin',
        ],

        ['header' => 'STUDENTS', 'can' => 'access-admin'],
        [
            'text'  => 'Students',
            'route' => 'admin.students.index',
            'icon'  => 'fas fa-fw fa-user-graduate',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Duplicates',
            'route' => 'admin.duplicates.index',
            'icon'  => 'fas fa-fw fa-compress-alt',
            'can'   => 'access-admin',
        ],

        ['header' => 'APPLICATIONS', 'can' => 'access-admin'],
        [
            'key'   => 'apps_new_entries',
            'text'  => 'New Entries',
            'route' => 'admin.applications.dispatch.index',
            'icon'  => 'fas fa-fw fa-inbox',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Application Pipeline',
            'route' => 'admin.applications.pipeline.index',
            'icon'  => 'fas fa-fw fa-stream',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_doc_requests',
            'text'  => 'Doc Requests',
            'route' => 'admin.applications.service-requests.documentation',
            'icon'  => 'fas fa-fw fa-file-alt',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_refunds',
            'text'  => 'Refunds',
            'route' => 'admin.applications.service-requests.refunds',
            'icon'  => 'fas fa-fw fa-undo-alt',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_cancellations',
            'text'  => 'Cancellations',
            'route' => 'admin.applications.service-requests.cancellations',
            'icon'  => 'fas fa-fw fa-times-circle',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_insurance_policies',
            'text'  => 'Insurance',
            'route' => 'admin.applications.insurance-policies.index',
            'icon'  => 'fas fa-fw fa-shield-alt',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_insurance_requests',
            'text'  => 'Insurance Requests',
            'route' => 'admin.applications.service-requests.insurance',
            'icon'  => 'fas fa-fw fa-shield-virus',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_reapplications',
            'text'  => 'Reapplications',
            'route' => 'admin.applications.reapplications.index',
            'icon'  => 'fas fa-fw fa-redo',
            'can'   => 'access-admin',
        ],

        ['header' => 'TEAM', 'can' => 'access-admin'],
        [
            'text'  => 'Team Members',
            'route' => 'admin.agents.index',
            'icon'  => 'fas fa-fw fa-users',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Removed Students',
            'route' => 'admin.students.removed',
            'icon'  => 'fas fa-fw fa-trash-restore',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Sales Consultants',
            'route' => 'admin.sales-consultants.index',
            'icon'  => 'fas fa-fw fa-briefcase',
            'can'   => 'access-admin',
        ],

        ['header' => 'CONFIGURATION', 'can' => 'access-admin'],
        [
            'text'  => 'Assignment Rules',
            'route' => 'admin.assignment-rules.index',
            'icon'  => 'fas fa-fw fa-random',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'SLA Settings',
            'route' => 'admin.sla-settings.index',
            'icon'  => 'fas fa-fw fa-clock',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Insurance Pricing',
            'route' => 'admin.insurance-settings.index',
            'icon'  => 'fas fa-fw fa-euro-sign',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Templates',
            'route' => 'admin.templates.index',
            'icon'  => 'fas fa-fw fa-envelope-open-text',
            'can'   => 'access-admin',
        ],

        ['header' => 'SALES MANAGEMENT', 'can' => 'access-admin'],
        [
            'text'  => 'Sales Goals',
            'route' => 'admin.sales-period-goals.index',
            'icon'  => 'fas fa-fw fa-bullseye',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Partials',
            'route' => 'admin.partials.index',
            'icon'  => 'fas fa-fw fa-chart-pie',
            'can'   => 'access-admin',
        ],
        [
            'text'  => 'Influencers',
            'route' => 'admin.influencers.index',
            'icon'  => 'fas fa-fw fa-bullhorn',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_special_approvals',
            'text'  => 'Special Approvals',
            'route' => 'admin.applications.special-approvals.index',
            'icon'  => 'fas fa-fw fa-star-half-alt',
            'can'   => 'access-admin',
        ],
        [
            'key'   => 'apps_removals',
            'text'  => 'Removals',
            'route' => 'admin.applications.service-requests.removals',
            'icon'  => 'fas fa-fw fa-user-slash',
            'can'   => 'access-admin',
        ],

        ['header' => 'ANALYTICS', 'can' => 'access-admin'],
        [
            'text'    => 'Reports',
            'icon'    => 'fas fa-fw fa-chart-bar',
            'can'     => 'access-admin',
            'submenu' => [
                [
                    'text'  => 'CS Overview',
                    'route' => 'admin.reports.index',
                    'icon'  => 'fas fa-fw fa-chart-line',
                ],
                [
                    'text'  => 'Sales Funnel',
                    'route' => 'admin.reports.sales',
                    'icon'  => 'fas fa-fw fa-funnel-dollar',
                ],
                [
                    'text'  => 'Insurance',
                    'route' => 'admin.reports.insurance',
                    'icon'  => 'fas fa-fw fa-shield-alt',
                ],
            ],
        ],

        ['header' => 'ACCOUNT'],
        [
            'text'  => 'Profile',
            'route' => 'profile.edit',
            'icon'  => 'fas fa-fw fa-user',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Here we can modify the menu filters of the admin panel.
    |
    | For detailed instructions you can look the menu filters section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Menu-Configuration
    |
    */

    'filters' => [
        JeroenNoten\LaravelAdminLte\Menu\Filters\GateFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\HrefFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\SearchFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ActiveFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\ClassesFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\LangFilter::class,
        JeroenNoten\LaravelAdminLte\Menu\Filters\DataFilter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugins Initialization
    |--------------------------------------------------------------------------
    |
    | Here we can modify the plugins used inside the admin panel.
    |
    | For detailed instructions you can look the plugins section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Plugins-Configuration
    |
    */

    'plugins' => [
        'CiTheme' => [
            'active' => true,
            'files' => [
                [
                    'type'     => 'css',
                    'asset'    => true,
                    'location' => 'css/ci-theme.css',
                ],
            ],
        ],
        'LocaleSwitcher' => [
            'active' => true,
            'files' => [
                [
                    'type'     => 'js',
                    'asset'    => true,
                    'location' => 'js/locale-switcher.js',
                ],
            ],
        ],
        'Datatables' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/js/dataTables.bootstrap4.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css',
                ],
            ],
        ],
        'Select2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js',
                ],
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.css',
                ],
            ],
        ],
        'Chartjs' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.bundle.min.js',
                ],
            ],
        ],
        'Sweetalert2' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdn.jsdelivr.net/npm/sweetalert2@8',
                ],
            ],
        ],
        'Pace' => [
            'active' => false,
            'files' => [
                [
                    'type' => 'css',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/blue/pace-theme-center-radar.min.css',
                ],
                [
                    'type' => 'js',
                    'asset' => false,
                    'location' => '//cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IFrame
    |--------------------------------------------------------------------------
    |
    | Here we change the IFrame mode configuration. Note these changes will
    | only apply to the view that extends and enable the IFrame mode.
    |
    | For detailed instructions you can look the iframe mode section here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/IFrame-Mode-Configuration
    |
    */

    'iframe' => [
        'default_tab' => [
            'url' => null,
            'title' => null,
        ],
        'buttons' => [
            'close' => true,
            'close_all' => true,
            'close_all_other' => true,
            'scroll_left' => true,
            'scroll_right' => true,
            'fullscreen' => true,
        ],
        'options' => [
            'loading_screen' => 1000,
            'auto_show_new_tab' => true,
            'use_navbar_items' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | Here we can enable the Livewire support.
    |
    | For detailed instructions you can look the livewire here:
    | https://github.com/jeroennoten/Laravel-AdminLTE/wiki/Other-Configuration
    |
    */

    'livewire' => false,
];
