<?php

use App\Router;
use App\Intouch;
use React\EventLoop\Factory;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

$config = parse_ini_file('.env' , true);
$_ENV = $config;

require __DIR__. '/vendor/autoload.php';

$loop   = Factory::create();
$router = new Router();


function pdo_connect_mysql() {
    $DATABASE_HOST = $_ENV['DATABASE_HOST'];
    $DATABASE_USER = $_ENV['DATABASE_USER'];
    $DATABASE_PASS = $_ENV['DATABASE_PASS'];
    $DATABASE_NAME = $_ENV['DATABASE_NAME'];
    try {
    	return new PDO('mysql:host=' . $DATABASE_HOST . ';dbname=' . $DATABASE_NAME . ';charset=utf8', $DATABASE_USER, $DATABASE_PASS);
    } catch (PDOException $exception) {
    	exit('Failed to connect to database!');
    }
}


function saveCount($id, $pdo)
{
    $stmt = $pdo->prepare('UPDATE senhas SET usado=usado+1 WHERE id=:id');
    return $stmt->execute(array( ':id' => $id));
}

function getDados($pdo)
{
    $stmt = $pdo->prepare('SELECT * FROM senhas WHERE servico="Intouch" and status=1 ORDER BY usado ASC');
    $stmt->execute();
    $con = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $con;
}

function saveCookie($cookie, $id, $pdo)
{
    $stmt = $pdo->prepare('UPDATE senhas SET cookie=:cookie WHERE id=:id');
    $stmt->execute(array( ':id' => $id, ':cookie' => $cookie));
    return $stmt->rowCount();
}

function saveStatus($status, $id, $pdo)
{
    $stmt = $pdo->prepare('UPDATE senhas SET status=:status WHERE id=:id');
    $stmt->execute(array( ':id' => $id, ':status' => $status));
    return $stmt->rowCount();
}

$pdo = pdo_connect_mysql();
$error = 0;
$cookie = '';
$proxy = '';

$timer = $loop->addPeriodicTimer(30, function () use(&$cookie, &$proxy, &$error, &$pdo){
    $dados = getDados($pdo);
    foreach($dados as $dado){
        $cookie = $dado['cookie'];
        $proxy = $dado['proxy'];
    
        $ver = Intouch::status($cookie, $proxy);
        if($ver === false){
            $login = (object)[
                'usuario' => $dado['usuario'],
                'cliente' => $dado['empresa'],
                'senha'   => $dado['senha']
            ];
            $cookie = Intouch::login($login, $proxy);
            saveCookie($cookie, $dado['id'], $pdo);
        }
    }
});


$server = new React\Http\Server($loop, function (ServerRequestInterface $request) use(&$router, &$cookie, &$proxy, &$pdo){

    $router->get('/', function() use(&$cookie, &$proxy, &$request, &$pdo) {
        $params = $request->getQueryParams();

        $dados = getDados($pdo);
        $cookie = $dados[0]['cookie'];
        $proxy = $dados[0]['proxy'];
        if(array_key_exists('dados', $params)){
            $post = $params['dados'];
            $main = Intouch::getCidades($cookie, $post, $proxy);
            return new Response(200,array('Content-Type' => 'text/json'), $main);
        }else{
            $main = Intouch::main($cookie, $proxy);
            return new Response(200,array('Content-Type' => 'text/html'), $main);
        }
    });

    $router->post('/', function () use(&$request, &$cookie, &$proxy, &$pdo){
        $dados = getDados($pdo);
        $cookie = $dados[0]['cookie'];
        $proxy = $dados[0]['proxy'];

        $data = $request->getParsedBody();
        $main = Intouch::consultar($data, $cookie, $proxy);
        saveCount($dados[0]['id'], $pdo);
        return new Response(200,array('Content-Type' => 'text/html'), $main);
    });

    return $router->run($request->getMethod(), $request->getUri()->getPath());

});


$socket = new React\Socket\Server("0.0.0.0:8484", $loop);
$server->listen($socket);
$loop->run();


