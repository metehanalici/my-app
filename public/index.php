<?php

declare(strict_types=1);

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use GuzzleHttp\Client;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if (false) { // Should be set to true in production
	$containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Set up repositories
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

/** @var SettingsInterface $settings */
$settings = $container->get(SettingsInterface::class);

$displayErrorDetails = $settings->get('displayErrorDetails');
$logError = $settings->get('logError');
$logErrorDetails = $settings->get('logErrorDetails');

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logError, $logErrorDetails);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Guzzle HTTP istemcisini kullanarak JSONPlaceholder API'lerinden veri çek
$client = new Client();
$responsePosts = $client->request('GET', 'https://jsonplaceholder.typicode.com/posts');
$responseComments = $client->request('GET', 'https://jsonplaceholder.typicode.com/comments');

$posts = json_decode($responsePosts->getBody()->getContents(), true);
$comments = json_decode($responseComments->getBody()->getContents(), true);

// Veritabanına kaydetme işlemleri
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'my-app',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Veritabanına bağlantıyı test etmek için
try {
    $capsule->getConnection()->getPdo();
    echo "Veritabanına başarılı bir şekilde bağlandınız.";
} catch (\Exception $e) {
    echo "Veritabanına bağlanırken hata oluştu: " . $e->getMessage();
}

foreach ($posts as $post) {
    $existingPost = $capsule->getConnection()->table('posts')->where('id', $post['id'])->first();

    if (!$existingPost) {
        $capsule->getConnection()->table('posts')->insert([
            'userId' => $post['userId'],
            'id' => $post['id'], 
            'title' => $post['title'],
            'body' => $post['body'],
            // Diğer sütunlar...
        ]);
    }
}

foreach ($comments as $comment) {
    $existingComment = $capsule->getConnection()->table('comments')->where('id', $comment['id'])->first();

    if (!$existingComment) {
        $capsule->getConnection()->table('comments')->insert([
            'postId' => $comment['postId'],
            'id' => $comment['id'], 
            'name' => $comment['name'],
            'email' => $comment['email'],
            'body' => $comment['body'],
            // Diğer sütunlar...
        ]);
    }
}


// Run App & Emit Response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
