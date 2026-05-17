<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/auth/login', 'POST', ['email' => 'admin@gmail.com', 'password' => 'password']);
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
$data = json_decode($response->getContent(), true);

if (!isset($data['token'])) {
    echo "Login failed: " . $response->getContent() . "\n";
    exit;
}
$token = $data['token'];

$req2 = Illuminate\Http\Request::create('/api/auth/me', 'GET');
$req2->headers->set('Accept', 'application/json');
$req2->headers->set('Authorization', 'Bearer ' . $token);
$res2 = $kernel->handle($req2);
echo "Auth/Me response: \n" . $res2->getContent() . "\n";
