<?php

use App\Http\Middleware\CorrelationId;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('generates a correlation id when none is provided', function (): void {
    $middleware = new CorrelationId;
    $request = Request::create('/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return new Response('ok');
    });

    expect($response->headers->has('X-Request-ID'))->toBeTrue();
    expect($response->headers->get('X-Request-ID'))->toMatch('/^[a-f0-9\-]{36}$/');
});

it('preserves an existing correlation id', function (): void {
    $middleware = new CorrelationId;
    $existingId = 'my-custom-request-id';
    $request = Request::create('/test', 'GET');
    $request->headers->set('X-Request-ID', $existingId);

    $response = $middleware->handle($request, function ($req) {
        return new Response('ok');
    });

    expect($response->headers->get('X-Request-ID'))->toBe($existingId);
});

it('adds correlation id to response header via HTTP request', function (): void {
    $this->getJson('/api/v1/login')
        ->assertHeader('X-Request-ID');
});
