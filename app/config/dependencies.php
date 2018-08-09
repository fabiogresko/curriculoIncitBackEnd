<?php

// Base
use Tx\Mailer;
use Pheanstalk\Pheanstalk;

// Model
use IntecPhp\Model\Account;
use IntecPhp\Model\Contact;
    //Uses digitados para a nova aplicação
use IntecPhp\Model\Access;

// Middleware
use IntecPhp\Middleware\AuthenticationMiddleware;
use IntecPhp\Middleware\HttpMiddleware;
use IntecPhp\Controller\ContactController;

// Service
use IntecPhp\Service\DbHandler;
use IntecPhp\Service\Cookie;
use IntecPhp\Service\JwtWrapper;

// Worker
use IntecPhp\Worker\EmailWorker;

// View
use IntecPhp\View\Layout;

// Entity
    //Uses digitados para a nova aplicação
use IntecPhp\Entity\User;

// Controller
    //Uses digitados para a nova aplicação
use IntecPhp\Controller\UserController;

// ----------------------------------------- Base

$dependencies[PDO::class] = function ($c) {
    $db = $c['settings']['db'];

    return new PDO(
        'mysql:host='.$db['host'].';dbname='.$db['db_name'].';charset=' . $db['charset'],
        $db['db_user'],
        $db['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
};

$dependencies[Mailer::class] = $dependencies->factory(function($c) {
    $credentials = $c['settings']['mail']['credentials'];
    $txMailer = new Mailer();
    $txMailer
        ->setServer($credentials['smtp_server'], $credentials['smtp_port'],$credentials['ssl'])
        ->setAuth($credentials['auth_email'], $credentials['auth_pass']);

    return $txMailer;
});

$dependencies[Pheanstalk::class] = function ($c) {
    $settings = $c['settings']['pheanstalk'];
    return new Pheanstalk($settings['host'], $settings['port']);
};

// ----------------------------------------- /Base

// ----------------------------------------- Model

$dependencies[Account::class] = function ($c) {
    $jwt = $c[JwtWrapper::class];
    $sessionCookie = $c[Cookie::class];
    return new Account($jwt, $sessionCookie);
};

$dependencies[Contact::class] = function ($c) {
    $settings = $c['settings']['contact'];
    return new Contact($settings['toEmail']);
};

$dependencies[Access::class] = function ($c) {
    $user = $c[User::class];
    return new Access($user);
};

// ----------------------------------------- /Model

// ----------------------------------------- Service

$dependencies[DbHandler::class] = function ($c) {
    $pdo = $c[PDO::class];
    return new DbHandler($pdo);
};

$dependencies[Cookie::class] = function ($c) {
    $cookieSettings = $c['settings']['session'];
    return new Cookie($cookieSettings['cookie_name'], $cookieSettings['cookie_expires']);
};
$dependencies[JwtWrapper::class] = function ($c) {
    $jwtSettings = $c['settings']['jwt'];
    return new JwtWrapper($jwtSettings['app_secret'], $jwtSettings['token_expires']);
};

// ----------------------------------------- /Service

// ----------------------------------------- Worker

$dependencies[EmailWorker::class] = function($c) {
    $messageConfig = $c['settings']['mail']['message'];
    $mailer = $c[Mailer::class];
    return new EmailWorker($mailer, $messageConfig);
};

// ----------------------------------------- /Worker

// ----------------------------------------- Controller

$dependencies[ContactController::class] = function($c) {
    $contact = $c[Contact::class];
    $emailProducer = $c[Pheanstalk::class];
    $emailProducer->useTube('email');
    return new ContactController($contact, $emailProducer);
};

$dependencies[UserController::class] = function($c) {
    $access = $c[Access::class];
    return new UserController($access);
};

// ----------------------------------------- /Controller

// ----------------------------------------- Middleware

$dependencies[AuthenticationMiddleware::class] = function ($c) {
    $layout = new Layout();
    $account = $c[Account::class];
    return new AuthenticationMiddleware($layout, $account);
};

$dependencies[HttpMiddleware::class] = function ($c) {
    $layout = new Layout();
    return new HttpMiddleware($layout, $c['settings']['display_errors']);
};


// ----------------------------------------- /Middleware

// ----------------------------------------- Entity

$dependencies[User::class] = function($c) {
    $conn = $c[DbHandler::class];
    return new User($conn);
};

// ----------------------------------------- /Entity