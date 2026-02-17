<?php
// Example: routes/api.php
// This shows how the new DSL integrates with existing code

use Luxid\Middleware\LoggingMiddleware;
use App\Actions\TodoAction;
use App\Actions\AuthAction;
use Luxid\Nodes\Route;
use Luxid\Middleware\RateLimitMiddleware;
use Luxid\Middleware\CsrfMiddleware;

// Existing syntax still works (backward compatibility preserved)
$router->get('/old/todos', [TodoAction::class, 'index'])
       ->middleware(new AuthMiddleware());

// New fluent DSL
route('todos.index')
    ->get('/api/todos')
    ->uses(TodoAction::class)  // defaults to 'index'
    ->secure();

route('todos.show')
    ->get('/api/todos/{id}')
    ->uses(TodoAction::class, 'show')
    ->secure();

route('todos.create')
    ->post('/api/todos')
    ->uses(TodoAction::class, 'create')
    ->secure();

route('todos.update')
    ->put('/api/todos/{id}')
    ->uses(TodoAction::class, 'update')
    ->secure();

route('auth.login')
    ->post('/api/login')
    ->uses(AuthAction::class, 'login')
    ->open();

route('auth.register')
    ->post('/api/register')
    ->uses(AuthAction::class, 'register')
    ->open();

// Example with custom middleware
route('todos.export')
    ->get('/api/todos/export')
    ->uses(TodoAction::class, 'export')
    ->with(new LoggingMiddleware())
    ->secure();


// Option 1: Explicit (current behavior)
Route::group(['auth' => true], function () {
    route('todos.index')
        ->get('/todos')
        ->uses(TodoAction::class)
        ->auth(); // Explicit

    route('todos.show')
        ->get('/todos/{id}')
        ->uses(TodoAction::class, 'show')
        ->auth(); // Explicit
});

// Option 2: With inheritance (new feature)
Route::group(['auth' => true], function () {
    route('todos.index')
        ->get('/todos')
        ->uses(TodoAction::class);
        // Inherits auth from group automatically

    route('todos.create')
        ->get('/todos/create')
        ->uses(TodoAction::class, 'create')
        ->withoutInheritance()
        ->open(['create']); // Override inheritance
});

// Nested groups with inheritance
Route::group(['prefix' => '/api'], function () {
    Route::group(['auth' => true], function () {
        route('api.todos')
            ->get('/todos')
            ->uses(TodoAction::class);
            // Path: /api/todos, inherits auth

        route('api.todos.details')
            ->get('/todos/{id}')
            ->uses(TodoAction::class, 'show')
            ->withoutInheritance()
            ->open(['show']); // Override
    });
});

// Optional parameters
route('blog.archive')
    ->get('/blog/{year}/{month?}/{day?}')
    ->uses(BlogAction::class, 'archive')
    ->open(['archive']);

// Route with middleware validation (throws if class doesn't exist)
route('payments.process')
    ->post('/payments')
    ->uses(PaymentAction::class, 'process')
    ->with(RateLimitMiddleware::class) // Validated
    ->with(CsrfMiddleware::class) // Validated
    ->auth();
