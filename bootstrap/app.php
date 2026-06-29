<?php

use App\Exceptions\DomainException;
use App\Exceptions\InsufficientStockException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Domain/business logic exceptions → redirect back with flash message
        $exceptions->render(function (DomainException $e, Request $request) {
            if ($request->expectsJson()) {
                $data = ['message' => $e->getMessage()];
                if ($e instanceof InsufficientStockException) {
                    $data['errors'] = $e->getShortages();
                }
                return response()->json($data, 422);
            }

            return back()->with($e->getFlashKey(), $e->getMessage());
        });

        // Model not found → user-friendly message
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            $model = class_basename($e->getModel());

            if ($request->expectsJson()) {
                return response()->json(['message' => "{$model} tidak ditemukan."], 404);
            }

            return back()->with('error', "{$model} tidak ditemukan.");
        });

        // Generic 404
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Resource tidak ditemukan.'], 404);
            }

            return back()->with('error', 'Halaman atau resource tidak ditemukan.');
        });
    })->create();
