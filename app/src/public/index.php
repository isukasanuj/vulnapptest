<?php
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../view.php';
foreach (glob(__DIR__ . '/../controllers/*.php') as $c) {
    require $c;
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/');
if ($path === '') {
    $path = '/';
}

/* ---- Static-ish routes ---------------------------------------------------- */
$routes = [
    'GET /'                          => fn() => redirect(current_user() ? '/dashboard' : '/login'),
    'GET /login'                     => 'ctl_login',
    'POST /login'                    => 'ctl_login',
    'GET /logout'                    => 'ctl_logout',
    'GET /forgot-password'           => 'ctl_forgot_password',
    'POST /forgot-password'          => 'ctl_forgot_password',
    'GET /dashboard'                 => 'ctl_dashboard',
    'GET /search'                    => 'ctl_search',

    'GET /subrogation'               => 'ctl_subrogation_index',
    'GET /subrogation/create'        => 'ctl_subrogation_create',
    'POST /subrogation/store'        => 'ctl_subrogation_store',

    'GET /customer'                  => 'ctl_customer_index',
    'GET /customer/create'           => 'ctl_customer_create',
    'POST /customer/store'           => 'ctl_customer_store',

    'GET /announcement'              => 'ctl_announcement_index',
    'GET /announcement/create'       => 'ctl_announcement_create',
    'POST /announcement/store'       => 'ctl_announcement_store',

    'GET /dealer-account'            => 'ctl_dealer_index',
    'GET /dealer-account/create'     => 'ctl_dealer_create',

    'GET /user'                      => 'ctl_user_index',

    'GET /profile'                   => 'ctl_profile',
    'POST /profile/update'           => 'ctl_profile_update',

    'GET /contract'                  => 'ctl_contract_index',
    'POST /contract_template_front_page_pdf/store' => 'ctl_contract_store',
    'GET /contract/download'         => 'ctl_contract_download',

    'GET /export/announcements.csv'  => 'ctl_export_announcements',
    'GET /export/users.csv'          => 'ctl_export_users',
];

$key = $method . ' ' . $path;
if (isset($routes[$key])) {
    ($routes[$key])();
    exit;
}

/* ---- Parameterised routes ------------------------------------------------- */
if ($method === 'POST' && preg_match('#^/user/(\d+)/dealer-account/store$#', $path, $m)) {
    ctl_dealer_store((int) $m[1]);
    exit;
}
if ($method === 'GET' && preg_match('#^/user/(\d+)/dealer-account/create$#', $path, $m)) {
    ctl_dealer_create((int) $m[1]);
    exit;
}
if ($method === 'GET' && preg_match('#^/announcement/(\d+)$#', $path, $m)) {
    ctl_announcement_show((int) $m[1]);
    exit;
}
if ($method === 'GET' && preg_match('#^/subrogation/(\d+)$#', $path, $m)) {
    ctl_subrogation_show((int) $m[1]);
    exit;
}
if ($method === 'GET' && preg_match('#^/subrogation/(\d+)/edit$#', $path, $m)) {
    ctl_subrogation_edit((int) $m[1]);
    exit;
}
if ($method === 'POST' && preg_match('#^/subrogation/(\d+)/update$#', $path, $m)) {
    ctl_subrogation_update((int) $m[1]);
    exit;
}
if ($method === 'POST' && preg_match('#^/subrogation/(\d+)/note$#', $path, $m)) {
    ctl_subrogation_note((int) $m[1]);
    exit;
}
if ($method === 'GET' && preg_match('#^/customer/(\d+)$#', $path, $m)) {
    ctl_customer_show((int) $m[1]);
    exit;
}
if ($method === 'GET' && preg_match('#^/dealer-account/(\d+)$#', $path, $m)) {
    ctl_dealer_show((int) $m[1]);
    exit;
}
if ($method === 'GET' && preg_match('#^/user/(\d+)$#', $path, $m)) {
    ctl_user_show((int) $m[1]);
    exit;
}

http_response_code(404);
layout_top('Not found', current_user());
echo '<div class="card"><h1>404</h1><p>No route for <code>' . e($key) . '</code>.</p></div>';
layout_bottom();
