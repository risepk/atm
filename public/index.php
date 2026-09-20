<?php
// public/index.php - Front Controller

date_default_timezone_set('Asia/Karachi');

// Autoloader for application classes
spl_autoload_register(function ($class) {
    $dirs = [
        __DIR__ . '/../app/Core/',
        __DIR__ . '/../app/Middleware/',
        __DIR__ . '/../app/Models/',
        __DIR__ . '/../app/Services/',
        __DIR__ . '/../app/Controllers/',
        __DIR__ . '/../app/Helpers/'
    ];

    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/../app/Config/constants.php';
require_once __DIR__ . '/../app/Helpers/SecurityHelper.php';
require_once __DIR__ . '/../app/Helpers/CurrencyHelper.php';

Session::start();

$router = new Router();

$router->registerMiddleware('auth', AuthMiddleware::class);
$router->registerMiddleware('role.admin', RoleMiddleware::class);
$router->registerMiddleware('csrf', CsrfMiddleware::class);
$router->registerMiddleware('guest', GuestMiddleware::class);

// Root redirect
$router->get('/', function(Request $req) {
    if (Session::has('user_id')) {
        Response::redirect('/dashboard');
    } else {
        Response::redirect('/login');
    }
});

// Authentication Routes
$router->get('/login', 'AuthController@showLogin', ['guest']);
$router->post('/login', 'AuthController@login', ['guest']);
// Point 19: POST /logout with CSRF protection
$router->post('/logout', 'AuthController@logout', ['auth', 'csrf']);

// Dashboard Routes
$router->get('/dashboard', 'DashboardController@index', ['auth']);

// Account Routes
$router->get('/accounts', 'AccountController@index', ['auth']);
$router->get('/api/accounts', 'AccountController@apiList', ['auth']);
$router->post('/api/accounts/save', 'AccountController@apiSave', ['auth', 'csrf']);
$router->post('/api/accounts/{id}/delete', 'AccountController@apiDelete', ['auth', 'csrf']);

// Transaction Routes
$router->get('/transactions', 'TransactionController@index', ['auth']);
$router->get('/transactions/rapid', 'TransactionController@rapid', ['auth']);
$router->get('/transactions/detailed', 'TransactionController@detailed', ['auth']);
$router->get('/transactions/create', 'TransactionController@create', ['auth']);
$router->get('/api/accounts/{id}/recent-transactions', 'TransactionController@apiRecent', ['auth']);
$router->get('/api/narrations/suggestions', 'TransactionController@apiSuggestions', ['auth']);
// Point 2: Transaction Editing
$router->get('/transactions/{id}/edit', 'TransactionController@edit', ['auth']);
$router->post('/api/transactions/save', 'TransactionController@apiSave', ['auth', 'csrf']);
$router->post('/api/transactions/{id}/update', 'TransactionController@apiUpdate', ['auth', 'csrf']);
$router->post('/api/transactions/{id}/void', 'TransactionController@apiVoid', ['auth', 'csrf']);

// Narration Template Routes (Admin only for mutations)
$router->get('/templates', 'TemplateController@index', ['auth', 'role.admin']);
$router->get('/templates/create', 'TemplateController@create', ['auth', 'role.admin']);
$router->get('/templates/{id}/edit', 'TemplateController@edit', ['auth', 'role.admin']);
$router->get('/api/templates/active', 'TemplateController@apiActive', ['auth']);
$router->post('/api/templates/save', 'TemplateController@apiSave', ['auth', 'role.admin', 'csrf']);

// Presets Routes
$router->get('/api/templates/{template_id}/presets', 'PresetController@apiGetForTemplate', ['auth']);
$router->post('/api/presets/save', 'PresetController@apiSave', ['auth', 'csrf']);
$router->post('/api/presets/{id}/delete', 'PresetController@apiDelete', ['auth', 'csrf']);

// Statements Routes
$router->get('/statements', 'StatementController@index', ['auth']);
$router->get('/api/statements/generate', 'StatementController@apiGenerate', ['auth']);
$router->get('/statements/print', 'StatementController@printView', ['auth']);

// Master Data & Bank Management Routes
$router->get('/banks', 'MasterDataController@index', ['auth', 'role.admin']);
$router->get('/api/master/banks', 'MasterDataController@banks', ['auth']);
$router->get('/api/master/banks/all', 'MasterDataController@allBanks', ['auth', 'role.admin']);
// Point 12: Dynamic Add Bank (Admin only)
$router->post('/api/master/banks/add', 'MasterDataController@addBank', ['auth', 'role.admin', 'csrf']);
$router->post('/api/master/banks/{id}/update', 'MasterDataController@updateBank', ['auth', 'role.admin', 'csrf']);
$router->post('/api/master/banks/{id}/toggle-status', 'MasterDataController@toggleBankStatus', ['auth', 'role.admin', 'csrf']);
$router->post('/api/master/generate-value', 'MasterDataController@generateValue', ['auth']);

// Point 18: User Administration Routes (Admin only)
$router->get('/users', 'UserController@index', ['auth', 'role.admin']);
$router->post('/api/users/create', 'UserController@apiCreate', ['auth', 'role.admin', 'csrf']);
$router->post('/api/users/{id}/update', 'UserController@apiUpdate', ['auth', 'role.admin', 'csrf']);
$router->post('/api/users/{id}/reset-password', 'UserController@apiResetPassword', ['auth', 'role.admin', 'csrf']);

// Dispatch Request & Point 20: Production Error Handling
try {
    $request = new Request();
    $router->dispatch($request);
} catch (Throwable $e) {
    // Log complete technical context and stack trace for administrators/developers
    error_log("Unhandled Application Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());

    $isAjax = isset($request) && $request->isAjax();
    
    // Never expose raw SQL/database errors, paths or credentials to clients
    $isDatabaseError = ($e instanceof PDOException) || (strpos($e->getMessage(), 'SQLSTATE') !== false);
    $clientMessage = $isDatabaseError ? "An internal error occurred. Please try again." : $e->getMessage();

    if ($isAjax) {
        Response::error($clientMessage, null, $isDatabaseError ? 500 : 400);
    } else {
        http_response_code(500);
        Response::render('errors/500', [
            'message' => 'An internal error occurred. Please try again.',
            'error' => null // Suppressed from user view
        ]);
    }
}
