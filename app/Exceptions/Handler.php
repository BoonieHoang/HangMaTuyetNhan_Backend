<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    return response()->json(['success' => false, 'message' => 'Unauthenticated.', 'errors' => (object)[]], 401);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException || $e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                    return response()->json(['success' => false, 'message' => 'This action is unauthorized.', 'errors' => (object)[]], 403);
                }
                if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return response()->json(['success' => false, 'message' => 'Not Found.', 'errors' => (object)[]], 404);
                }
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return response()->json(['success' => false, 'message' => $e->getMessage(), 'errors' => $e->errors()], 422);
                }
                if ($e instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                    return response()->json(['success' => false, 'message' => 'Too Many Requests.', 'errors' => (object)[]], 429);
                }
                
                return response()->json(['success' => false, 'message' => 'Server Error.', 'errors' => ['details' => $e->getMessage()]], 500);
            }
        });
    }
}
