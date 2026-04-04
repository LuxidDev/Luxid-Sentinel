# Luxid Haven Documentation

## Overview

**Luxid Haven** is a modern, session-based authentication package for the Luxid Framework. It provides a complete authentication system with user registration, login, logout, and session management, seamlessly integrated with Luxid's architecture and Rocket ORM.

## Table of Contents

1. [Introduction](#introduction)
2. [Architecture Overview](#architecture-overview)
3. [Installation](#installation)
4. [How It Works](#how-it-works)
5. [Core Components](#core-components)
6. [API Reference](#api-reference)
7. [Integration with Luxid](#integration-with-luxid)
8. [Configuration](#configuration)
9. [Customization](#customization)
10. [Security Features](#security-features)
11. [Troubleshooting](#troubleshooting)
12. [Best Practices](#best-practices)

---

## Introduction

Luxid Haven is designed to be:

- **Simple** - One command installation with `php juice haven:install`
- **Secure** - Passwords hashed with bcrypt, session-based authentication
- **Modern** - Uses PHP 8 attributes, Rocket ORM, and the latest Luxid features
- **Extensible** - Custom guards, providers, and user entities
- **Developer-Friendly** - Clean API with the `auth()` helper function

### Features

- ✅ Session-based authentication
- ✅ User registration and login
- ✅ Password hashing with bcrypt
- ✅ "Remember me" functionality
- ✅ JSON API responses for modern applications
- ✅ Automatic route registration
- ✅ Rocket ORM integration
- ✅ Middleware for protected routes
- ✅ Extensible guard system

---

## Architecture Overview

### Design Philosophy

Haven follows a clean, layered architecture that separates concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                     Application Layer                        │
│  (Your Actions: AuthAction.php, Protected Actions)          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                     Auth Helper                              │
│              (auth() function - Global helper)               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Haven Static Facade                       │
│              (Haven::attempt(), Haven::user())               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                     AuthManager                              │
│          (Manages guards, authentication flow)               │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   SessionGuard                               │
│          (Handles session-based authentication)              │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      User Entity                             │
│           (Rocket ORM entity with Authenticatable)          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   Database (Users Table)                     │
└─────────────────────────────────────────────────────────────┘
```

### Flow Diagram

```
User Registration Flow:
┌──────────┐    POST /auth/register    ┌──────────────┐
│  Client  │ ─────────────────────────> │ AuthAction   │
└──────────┘                            └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Create User  │
                                        │ Entity       │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Validate &   │
                                        │ Save to DB   │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ auth()->     │
                                        │ login($user) │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Session      │
                                        │ Created      │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Return User  │
                                        │ with 201     │
                                        └──────────────┘

User Login Flow:
┌──────────┐     POST /auth/login      ┌──────────────┐
│  Client  │ ─────────────────────────> │ AuthAction   │
└──────────┘                            └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Validate     │
                                        │ Credentials  │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ auth()->     │
                                        │ attempt()    │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Verify       │
                                        │ Password     │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Create       │
                                        │ Session      │
                                        └──────────────┘
                                               │
                                               ▼
                                        ┌──────────────┐
                                        │ Return User  │
                                        │ with Cookie  │
                                        └──────────────┘
```

---

## Installation

### Prerequisites

- PHP 8.0 or higher
- Luxid Framework (v0.6.7 or higher)
- Rocket ORM (v0.1.8 or higher)

### Step-by-Step Installation

1. **Create a new Luxid project** (if not already done):
```bash
composer create-project luxid/framework my-app
cd my-app
```

2. **Configure your database** in `.env`:
```env
DB_DSN=mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=myapp
DB_USER=your_username
DB_PASSWORD=your_password
```

3. **Install haven**:
```bash
composer require luxid/haven
```

4. **Run the installation command**:
```bash
php juice haven:install
```

This command will:
- Publish the configuration file to `config/haven.php`
- Create the users table migration with Rocket ORM
- Generate the User entity with proper attributes
- Create the AuthAction with all auth methods
- Register authentication routes

5. **Run database migrations**:
```bash
php juice db:migrate
```

6. **Start the development server**:
```bash
php juice start
```

### What Gets Installed

After installation, your project will have:

```
my-app/
├── app/
│   ├── Actions/
│   │   └── AuthAction.php          # Authentication actions
│   └── Entities/
│       └── User.php                # User entity with Authenticatable
├── config/
│   └── haven.php                   # Haven configuration
├── migrations/
│   └── m00001_create_users_table.php  # Users table migration
└── routes/
    └── api.php                     # Routes (with auth endpoints)
```

---

## How It Works

### The Auth Helper

The `auth()` helper function is the primary interface for authentication operations:

```php
// Get the current user
$user = auth()->user();

// Check if user is authenticated
if (auth()->check()) {
    echo "Logged in as: " . $user->email;
}

// Attempt login with credentials
if (auth()->attempt(['email' => $email, 'password' => $password])) {
    // Login successful
}

// Logout
auth()->logout();
```

### Authentication Flow

1. **Registration** (`POST /auth/register`)
   - Validates input (email, password, name)
   - Creates a new User entity
   - Hashes password automatically
   - Saves to database
   - Logs user in automatically
   - Returns user data with 201 status

2. **Login** (`POST /auth/login`)
   - Validates credentials
   - Verifies password hash
   - Creates session with user ID
   - Returns user data with session cookie

3. **Protected Routes** (`GET /auth/me`, `POST /auth/logout`)
   - Require authentication via `->auth()` middleware
   - Checks session for user ID
   - Retrieves user from database
   - Executes action if authenticated

4. **Logout** (`POST /auth/logout`)
   - Clears session data
   - Removes "remember me" token (if used)
   - Returns success response

### Middleware Protection

Routes can be protected using the `->auth()` method:

```php
// routes/api.php
route('protected')
    ->get('/api/user')
    ->uses(UserAction::class, 'show')
    ->auth();  // Only authenticated users can access
```

---

## Core Components

### 1. AuthManager (`src/AuthManager.php`)

The `AuthManager` is the central authentication orchestrator:

```php
class AuthManager implements AuthManagerContract
{
    // Get the current guard
    public function guard(?string $name = null): GuardContract;
    
    // Set default guard
    public function shouldUse(string $name): self;
    
    // Get current user
    public function user(): ?AuthenticatableContract;
    
    // Check authentication status
    public function check(): bool;
    public function guest(): bool;
    
    // Authentication operations
    public function attempt(array $credentials = [], bool $remember = false): bool;
    public function login(AuthenticatableContract $user, bool $remember = false): bool;
    public function logout(): void;
}
```

### 2. SessionGuard (`src/SessionGuard.php`)

The `SessionGuard` handles session-based authentication:

```php
class SessionGuard implements GuardContract
{
    // Session keys
    protected const SESSION_USER_KEY = 'haven_user_id';
    protected const SESSION_REMEMBER_KEY = 'haven_remember_token';
    
    // Authentication methods
    public function attempt(array $credentials = [], bool $remember = false): bool;
    public function login(AuthenticatableContract $user, bool $remember = false): bool;
    public function logout(): void;
    
    // User retrieval
    public function user(): ?AuthenticatableContract;
    public function id();
    
    // Status checks
    public function check(): bool;
    public function guest(): bool;
}
```

### 3. PasswordHasher (`src/PasswordHasher.php`)

The `PasswordHasher` provides secure password hashing:

```php
class PasswordHasher
{
    // Hash a password
    public function hash(string $value, array $options = []): string;
    
    // Verify a password against a hash
    public function check(string $value, string $hashedValue): bool;
    
    // Check if password needs rehashing
    public function needsRehash(string $hashedValue, array $options = []): bool;
}
```

### 4. User Entity (`app/Entities/User.php`)

The User entity implements `Authenticatable` and uses Rocket ORM attributes:

```php
#[EntityAttr(table: 'users')]
class User extends UserEntity implements Authenticatable
{
    #[Column(primary: true, autoIncrement: true)]
    public int $id = 0;
    
    #[Column]
    #[Required]
    #[Email]
    #[Unique]
    public string $email = '';
    
    #[Column(hidden: true)]
    #[Required]
    #[Min(8)]
    public string $password = '';
    
    // ... other columns and methods
}
```

### 5. AuthAction (`app/Actions/AuthAction.php`)

The `AuthAction` handles all authentication endpoints:

```php
class AuthAction extends LuxidAction
{
    public function register(): string;   // POST /auth/register
    public function login(): string;      // POST /auth/login
    public function logout(): string;     // POST /auth/logout
    public function me(): string;         // GET /auth/me
}
```

---

## API Reference

### Global Helper Functions

#### `auth()`

The main authentication helper.

```php
function auth(?string $guard = null): AuthManager|Guard
```

**Returns**: The AuthManager instance (or specific guard if guard name provided)

**Examples**:
```php
// Get auth manager
$auth = auth();

// Get current user
$user = auth()->user();

// Check if logged in
if (auth()->check()) { ... }

// Get user ID
$id = auth()->id();

// Check if guest
if (auth()->guest()) { ... }
```

### Authentication Methods

#### `attempt()`

Attempt to authenticate a user with credentials.

```php
public function attempt(array $credentials = [], bool $remember = false): bool
```

**Parameters**:
- `$credentials` - Array of credentials (email, password)
- `$remember` - Whether to set a remember token

**Returns**: `true` if authentication successful, `false` otherwise

**Example**:
```php
if (auth()->attempt(['email' => 'user@example.com', 'password' => 'secret'])) {
    // Login successful
}
```

#### `login()`

Log a user in directly.

```php
public function login(AuthenticatableContract $user, bool $remember = false): bool
```

**Parameters**:
- `$user` - Authenticatable user entity
- `$remember` - Whether to set a remember token

**Returns**: `true` on success

**Example**:
```php
$user = User::find(1);
auth()->login($user);
```

#### `logout()`

Log the current user out.

```php
public function logout(): void
```

**Example**:
```php
auth()->logout();
```

#### `user()`

Get the currently authenticated user.

```php
public function user(): ?AuthenticatableContract
```

**Returns**: User entity or `null`

**Example**:
```php
$user = auth()->user();
if ($user) {
    echo $user->email;
}
```

#### `check()`

Check if the user is authenticated.

```php
public function check(): bool
```

**Returns**: `true` if authenticated, `false` otherwise

#### `guest()`

Check if the user is a guest (not authenticated).

```php
public function guest(): bool
```

**Returns**: `true` if not authenticated, `false` otherwise

#### `validate()`

Validate credentials without logging in.

```php
public function validate(array $credentials = []): bool
```

**Returns**: `true` if credentials are valid, `false` otherwise

**Example**:
```php
if (auth()->validate(['email' => 'user@example.com', 'password' => 'secret'])) {
    echo "Credentials are correct!";
}
```

#### `id()`

Get the ID of the authenticated user.

```php
public function id()
```

**Returns**: User ID or `null`

### Middleware

#### `RequireAuth`

Middleware that protects routes from unauthenticated access.

```php
class RequireAuth extends BaseMiddleware
{
    public function execute(): void;
}
```

**Usage**:
```php
route('protected')
    ->get('/dashboard')
    ->uses(DashboardAction::class, 'index')
    ->auth();  // Adds RequireAuth middleware
```

---

## Integration with Luxid

### Service Provider Integration

Haven automatically registers its service provider through Composer's `extra` section:

```json
{
    "extra": {
        "luxid": {
            "providers": [
                "Luxid\\Haven\\Providers\\HavenServiceProvider"
            ]
        }
    }
}
```

The service provider:
1. Registers the AuthManager with the application container
2. Makes the `auth()` helper available globally
3. Sets up the authentication configuration

### Route Builder Integration

Haven's `auth()` middleware is available in routes:

```php
// routes/api.php
route('auth.me')
    ->get('/auth/me')
    ->uses(AuthAction::class, 'me')
    ->auth();  // Only authenticated users
```

### Application Integration

The `Application` class has an `$auth` property that holds the AuthManager:

```php
class Application
{
    public ?AuthManagerContract $auth = null;
    
    public function registerAuth(AuthManagerContract $auth): void
    {
        $this->auth = $auth;
    }
}
```

### Request/Response Integration

Haven uses Luxid's built-in `Request` and `Response` classes:

```php
// In AuthAction
$data = $this->request()->input();  // Get JSON/form data
return Response::success(['user' => $user]);  // JSON response
```

---

## Configuration

### Configuration File (`config/haven.php`)

```php
return [
    /*
    | Default Authentication Guard
    */
    'default' => 'session',
    
    /*
    | Authentication Guards
    */
    'guards' => [
        'session' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],
    
    /*
    | User Providers
    */
    'providers' => [
        'users' => [
            'entity' => \App\Entities\User::class,
        ],
    ],
    
    /*
    | Password Hashing
    */
    'hashing' => [
        'driver' => 'bcrypt',
        'bcrypt' => [
            'rounds' => $_ENV['BCRYPT_ROUNDS'] ?? 12,
        ],
    ],
    
    /*
    | Remember Me
    */
    'remember' => [
        'enabled' => true,
        'lifetime' => 43200, // 30 days in minutes
    ],
];
```

### Configuration Options

| Option | Description |
|--------|-------------|
| `default` | Default authentication guard |
| `guards` | Guard configurations (session, token, etc.) |
| `providers` | User provider configurations |
| `hashing` | Password hashing settings |
| `remember` | "Remember me" token settings |

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `BCRYPT_ROUNDS` | Number of bcrypt rounds | 12 |

---

## Customization

### Custom User Entity

You can extend the User entity with additional fields:

```php
#[EntityAttr(table: 'users')]
class User extends UserEntity implements Authenticatable
{
    // Add custom fields
    #[Column]
    public string $phone = '';
    
    #[Column]
    public string $avatar = '';
    
    // Add custom methods
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

### Custom Guard

Create a custom guard for API tokens:

```php
class TokenGuard implements GuardContract
{
    public function check(): bool { ... }
    public function user(): ?AuthenticatableContract { ... }
    // ... implement all methods
}
```

Then register it in `config/haven.php`:

```php
'guards' => [
    'api' => [
        'driver' => 'token',
        'provider' => 'users',
    ],
],
```

### Custom Password Hashing

You can customize password hashing options:

```php
// In service provider
$hasher = new PasswordHasher([
    'cost' => 14,  // Increase cost for better security
]);
```

---

## Security Features

### Password Hashing

- Uses `password_hash()` with bcrypt (PASSWORD_DEFAULT)
- Configurable cost factor (default: 12)
- Automatic rehashing when algorithm updates

### Session Security

- Session IDs regenerated on login
- Session data stored server-side
- Configurable session lifetime
- "Remember me" tokens with secure storage

### CSRF Protection

- CSRF tokens automatically included in forms
- Tokens validated on all POST requests
- Configurable token length and lifetime

### Input Validation

- Email validation
- Password strength requirements (min 8 chars)
- Unique email validation
- XSS protection via output escaping

### Response Headers

Haven's middleware adds security headers:

```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
```

---

## Troubleshooting

### Common Issues

#### 1. "Haven not initialized" Error

**Problem**: Haven's AuthManager isn't set when calling `auth()`.

**Solution**: Ensure Haven is properly installed and the service provider is registered.

```bash
composer require luxid/haven
php juice haven:install
```

#### 2. "Unknown column 'remember_token'" Error

**Problem**: Migration ran before Haven installation.

**Solution**: Rollback and reinstall:

```bash
php juice db:rollback
php juice haven:install --force
php juice db:migrate
```

#### 3. 403 Forbidden on Public Routes

**Problem**: Routes marked as `open()` are still protected.

**Solution**: Ensure you're using the correct method:

```php
route('auth.register')
    ->post('/auth/register')
    ->uses(AuthAction::class, 'register')
    ->open();  // Not ->auth()
```

#### 4. Session Not Persisting

**Problem**: Session data is lost between requests.

**Solution**: Check session configuration in `.env`:

```env
SESSION_LIFETIME=120
```

#### 5. Password Not Hashing

**Problem**: Passwords stored in plain text.

**Solution**: Ensure `beforeSave()` hook is in User entity:

```php
protected function beforeSave(): void
{
    if (!empty($this->password) && !$this->isPasswordHashed()) {
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
    }
}
```

### Debugging

Enable debug mode in `.env`:

```env
APP_DEBUG=true
```

Check error logs:

```bash
tail -f /tmp/php-errors.log
```

Enable Haven debug output:

```php
// In SessionGuard.php temporarily
error_log("Session data: " . json_encode($_SESSION));
```

---

## Best Practices

### 1. Use the Auth Helper

Always use the `auth()` helper instead of static `Haven::` calls:

```php
// ✅ Good
auth()->user();

// ❌ Avoid if possible
Haven::user();
```

### 2. Protect Routes Properly

```php
// Public routes
route('auth.register')
  ->post('/auth/register')
  ->open();

// Protected routes
route('dashboard')
  ->get('/dashboard')
  ->uses(DashboardAction::class, 'index')
  ->auth();
```

### 3. Validate Input

Always validate user input before processing:

```php
$data = $this->request()->input();

if (empty($data['email'])) {
    return Response::error('Email is required', null, 422);
}
```

### 4. Use Strong Passwords

Enforce password complexity:

```php
#[Min(8)]
public string $password = '';
```

### 5. Hash Passwords

Never store plain text passwords. The User entity's `beforeSave()` hook handles hashing automatically.

### 6. Keep Sessions Secure

- Use HTTPS in production
- Set secure session cookies
- Regenerate session ID on login

### 7. Secure "Remember Me"

Only enable remember tokens for trusted devices and use secure token storage.

---

## API Endpoints Reference

| Method | Endpoint | Description | Authentication |
|--------|----------|-------------|----------------|
| POST | `/auth/register` | Register a new user | None |
| POST | `/auth/login` | Login user | None |
| GET | `/auth/me` | Get current user | Required |
| POST | `/auth/logout` | Logout user | Required |

### Request/Response Examples

#### Register

**Request**:
```json
{
    "email": "user@example.com",
    "password": "password123",
    "firstname": "John",
    "lastname": "Doe"
}
```

**Response**:
```json
{
    "success": true,
    "message": "201",
    "data": {
        "user": {
            "id": 1,
            "email": "user@example.com",
            "firstname": "John",
            "lastname": "Doe",
            "display_name": "John Doe",
            "created_at": "2026-03-29 10:00:00",
            "updated_at": "2026-03-29 10:00:00"
        },
        "message": "Registration successful!"
    }
}
```

#### Login

**Request**:
```json
{
    "email": "user@example.com",
    "password": "password123",
    "remember": true
}
```

**Response**:
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "user": {
            "id": 1,
            "email": "user@example.com",
            "firstname": "John",
            "lastname": "Doe",
            "display_name": "John Doe",
            "created_at": "2026-03-29 10:00:00",
            "updated_at": "2026-03-29 10:00:00"
        },
        "message": "Login successful!"
    }
}
```

#### Get Current User

**Response**:
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "user": {
            "id": 1,
            "email": "user@example.com",
            "firstname": "John",
            "lastname": "Doe",
            "display_name": "John Doe"
        }
    }
}
```

#### Logout

**Response**:
```json
{
    "success": true,
    "message": "Success",
    "data": {
        "message": "Logged out successfully!"
    }
}
```

---

## Conclusion

Luxid Haven provides a complete, secure, and developer-friendly authentication system for Luxid applications. With its clean API, modern architecture, and deep integration with Luxid's ecosystem, you can quickly add authentication to your applications with minimal code.

### Key Takeaways

- One-command installation
- Built on modern PHP 8 features
- Seamless integration with Luxid
- Secure by default
- Extensible and customizable
- Great developer experience

---

## Resources

- [Luxid Framework Documentation](https://luxid.dev/docs)
- [Rocket ORM Documentation](https://luxid.dev/docs/rocket)
---
