<?php

namespace Luxid\Foundation;

use Luxid\Routing\Router;
use Luxid\Http\Response;
use Luxid\Http\Request;
use Luxid\Http\SessionInterface;
use Luxid\Database\Database;
use Luxid\Database\DbEntity;

class Application
{
    public static string $ROOT_DIR;
    public string $frame = 'app';

    public string $userClass;
    public Router $router;
    public Request $request;
    public Response $response;
    public SessionInterface $session;
    public static Application $app;
    public ?Action $action = null;
    public ?Database $db = null;
    public ?DbEntity $user = null;
    public Screen $screen;

    public function __construct($rootPath, array $config)
    {
        $this->userClass = $config['userClass'];

        self::$ROOT_DIR = $rootPath;
        self::$app = $this;

        $this->request = new Request();
        $this->response = new Response();

        // Only create Session if not in CLI mode
        if (php_sapi_name() !== 'cli') {
            $this->session = new \Luxid\Http\Session();
        } else {
            // Create a null session for CLI
            $this->session = new \Luxid\Http\NullSession();
        }

        $this->router = new Router($this->request, $this->response);
        $this->router->addGlobalMiddleware(new \Luxid\Middleware\CorsMiddleware());
        $this->screen = new Screen();

        // Initialize database only if config has db settings
        if (isset($config['db'])) {
            $this->db = new Database($config['db']);
        }

        $this->user = null;

        // Only check session for user if session exists and is started
        if ($this->session->isStarted()) {
            $primaryValue = $this->session->get('user');
            if ($primaryValue !== null) {
                $primaryKey = $this->userClass::primaryKey();
                $this->user = $this->userClass::findOne([$primaryKey => $primaryValue]) ?? null;
            }
        }
    }

    public static function isGuest()
    {
        return !self::$app->user;
    }

    public function run()
    {
        try {
            echo $this->router->resolve();
        } catch (\Exception $e) {
            // Get the exception code and ensure it's a valid HTTP status code
            $code = $e->getCode();

            // Convert to integer if needed
            if (!is_int($code)) {
                $code = (int)$code;
            }

            // Validate it's a proper HTTP status code (100-599)
            if ($code < 100 || $code > 599) {
                // For PDOExceptions and other non-HTTP exceptions, use 500
                if ($e instanceof \PDOException) {
                    $code = 500; // Internal Server Error for database issues
                } else {
                    $code = $e instanceof \Luxid\Exceptions\NotFoundException ? 404 : 500;
                }
            }

            $this->response->setStatusCode($code);

            // Check if this is an API request
            $path = $this->request->getPath();
            $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            $isApiRequest = strpos($path, '/api/') === 0 ||
                           strpos($acceptHeader, 'application/json') !== false ||
                           strpos($contentType, 'application/json') !== false;

            if ($isApiRequest) {
                // Return JSON error for API requests
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'error' => $e->getMessage(),
                    'code' => $code  // Use the validated code
                ]);
            } else {
                // Return HTML error for web requests
                echo $this->screen->renderScreen('_error', [
                    'exception' => $e
                ]);
            }
        }
    }

    // getter | setter ==================================
    public function getAction()
    {
        return $this->action;
    }
    public function setAction(Action $action)
    {
        $this->action = $action;
    }
    // =============================================

    public function login(DbEntity $user)
    {
        $this->user = $user;
        $primaryKey = $user->primaryKey();
        $primaryValue = $user->{$primaryKey};

        $this->session->set('user', $primaryValue);

        return true;
    }

    public function logout()
    {
        $this->user = null;
        $this->session->remove('user');
    }
}
