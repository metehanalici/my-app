<?php

use App\Application\Handlers\HttpErrorHandler;
use Slim\App;

return function (App $app) {
    $app->get('/posts', function ($request, $response, $args) {
        // PDO bağlantısı
        $pdo = new PDO('mysql:host=localhost;dbname=my-app', 'root', '');
    
        // Hata kontrolü
        if (!$pdo) {
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Database connection error']));
        }
    
        // Sorgu hazırlama ve çalıştırma
        $stmt = $pdo->query('SELECT * FROM posts');
    
        // Hata kontrolü
        if (!$stmt) {
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Query execution error']));
        }
    
        // Verileri alma
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // JSON olarak dönüş
        $response->getBody()->write(json_encode($posts));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    
    $app->get('/comments', function ($request, $response, $args) {
        // PDO bağlantısı
        $pdo = new PDO('mysql:host=localhost;dbname=my-app', 'root', '');
    
        // Hata kontrolü
        if (!$pdo) {
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Database connection error']));
        }
    
        // Sorgu hazırlama ve çalıştırma
        $stmt = $pdo->query('SELECT * FROM comments');
    
        // Hata kontrolü
        if (!$stmt) {
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Query execution error']));
        }
    
        // Verileri alma
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // JSON olarak dönüş
        $response->getBody()->write(json_encode($comments));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    
    $app->get('/posts/{post_id}/comments', function ($request, $response, $args) {
        $post_id = $args['post_id'];
    
        // PDO bağlantısı
        $pdo = new PDO('mysql:host=localhost;dbname=my-app', 'root', '');
    
        // Hata kontrolü
        if (!$pdo) {
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Database connection error']));
        }
    
        // Parametreli sorgu hazırlama ve çalıştırma
        $stmt = $pdo->prepare('SELECT * FROM comments WHERE postId = :post_id');
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
    
        // Hata kontrolü
        if (!$stmt) {
            return $response->withStatus(500)
                            ->withHeader('Content-Type', 'application/json')
                            ->write(json_encode(['error' => 'Query execution error']));
        }
    
        // Verileri alma
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // JSON olarak dönüş
        $response->getBody()->write(json_encode($comments));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
};
