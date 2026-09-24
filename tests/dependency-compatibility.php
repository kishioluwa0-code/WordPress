<?php

declare(strict_types=1);

$autoload = __DIR__ . '/../project/school-management-pro/includes/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Missing upgraded Composer autoloader.\n");
    exit(1);
}
require_once $autoload;

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
};

$assert(interface_exists('Kreait\\Firebase\\Contract\\Messaging'), 'Kreait messaging contract is autoloadable');
$assert(class_exists('Kreait\\Firebase\\Factory'), 'Kreait Factory is autoloadable');
$assert(class_exists('Kreait\\Firebase\\Messaging\\CloudMessage'), 'Kreait CloudMessage is autoloadable');
$assert(class_exists('GuzzleHttp\\Client'), 'Guzzle client is autoloadable');
$assert(class_exists('Firebase\\JWT\\JWT'), 'JWT implementation is autoloadable');
$assert(class_exists('Stripe\\StripeClient'), 'Stripe v21 client is autoloadable');
$assert(class_exists('Twilio\\Rest\\Client'), 'Twilio v8 client is autoloadable');

$message = \Kreait\Firebase\Messaging\CloudMessage::new()
	->toToken('compatibility-token')
	->withNotification(['title' => 'Compatibility', 'body' => 'Upgrade test']);
$payload = $message->jsonSerialize();
$assert(($payload['token'] ?? null) === 'compatibility-token', 'Firebase token target is preserved');
$assert(($payload['notification']['title'] ?? null) === 'Compatibility', 'Firebase notification title is preserved');
$assert(($payload['notification']['body'] ?? null) === 'Upgrade test', 'Firebase notification body is preserved');

$assert(method_exists('Kreait\\Firebase\\Factory', 'withServiceAccount'), 'Firebase Factory accepts service-account configuration');
$assert(method_exists('Kreait\\Firebase\\Factory', 'createMessaging'), 'Firebase Factory creates a messaging client');

$handler = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\MockHandler([
    new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], '{"ok":true}'),
]));
$client = new \GuzzleHttp\Client(['handler' => $handler, 'http_errors' => true]);
$response = $client->request('POST', 'https://example.invalid/compatibility', [
    'json' => ['environment' => 'dev'],
    'timeout' => 3,
    'connect_timeout' => 2,
]);
$assert($response->getStatusCode() === 200, 'Guzzle mock request returns expected status');
$assert(json_decode((string) $response->getBody(), true)['ok'] === true, 'Guzzle response body remains readable');

$jwtKey = str_repeat('k', 32);
$jwt = \Firebase\JWT\JWT::encode(['sub' => 'compatibility', 'iat' => time()], $jwtKey, 'HS256');
$claims = (array) \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($jwtKey, 'HS256'));
$assert(($claims['sub'] ?? null) === 'compatibility', 'JWT v7 encode/decode contract works');

fwrite(STDOUT, "Dependency compatibility checks passed.\n");
