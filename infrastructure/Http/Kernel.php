<?php

namespace Infrastructure\Http;

use Exception;
use FrenchFrogs\App\Http\Middleware\FrenchFrogsMiddleware;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Throwable;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \Infrastructure\Http\Middleware\RequestMiddleware::class,
        \Infrastructure\Http\Middleware\TrustProxies::class,
        \Infrastructure\Http\Middleware\SecureHeaderMiddleware::class
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [

        \Ref::INTERFACE_DEFAULT => [
            \Infrastructure\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'frenchfrogs:' . \Ref::INTERFACE_DEFAULT
        ],

        \Ref::INTERFACE_PARTNER => [
            \Infrastructure\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Barryvdh\Cors\HandleCors::class,
            'frenchfrogs:' . \Ref::INTERFACE_PARTNER,
            'language:' . \Ref::INTERFACE_PARTNER,
        ],


        \Ref::INTERFACE_FRONT => [
            \Infrastructure\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'frenchfrogs:' . \Ref::INTERFACE_FRONT,
            'language:' . \Ref::INTERFACE_FRONT,
            'view' => \Infrastructure\Http\Middleware\ViewMiddleware::class,
            \Infrastructure\Http\Middleware\BehaviorMiddleware::class,
            \Infrastructure\Http\Middleware\CheckBrowser::class,
            'frenchfrogs:' . \Ref::INTERFACE_FRONT


        ],

        \Ref::INTERFACE_JOBMAKER => [
            \Infrastructure\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'auth:' . \Ref::INTERFACE_JOBMAKER,
            \Infrastructure\Http\Middleware\JobmakerMiddleware::class,
            'language:' . \Ref::INTERFACE_JOBMAKER,
            'view' => \Infrastructure\Http\Middleware\ViewMiddleware::class,
            \Infrastructure\Http\Middleware\BehaviorMiddleware::class,
            'frenchfrogs:' . \Ref::INTERFACE_JOBMAKER
        ],

        //Shared by all APIs
        "apiShared" => [
            \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
            \Infrastructure\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \Barryvdh\Cors\HandleCors::class,
            //ADD MIDDLEWARE TO CHECK IF USER IS AN OPERATOR
            'throttle:60,1',
            'bindings'
        ],

        \Ref::API_PARTNER => [
            //ADD MIDDLEWARE TO CHECK IF USER IS AN PRESCRIBER
        ],

        \Ref::API_OPERATOR => [
            //ADD MIDDLEWARE TO CHECK IF USER IS AN OPERATOR
        ],
    ];


    /**
     * Handle an incoming HTTP request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle($request)
    {


        try {
            $request->enableHttpMethodParameterOverride();
            $response = $this->sendRequestThroughRouter($request);
        } catch (Exception $e) {
            $this->reportException($e);
            $response = $this->renderException($request, $e);
        } catch (Throwable $e) {
            $this->reportException($e = new FatalThrowableError($e));
            $response = $this->renderException($request, $e);
        }

        $this->app['events']->dispatch(
            new RequestHandled($request, $response)
        );

        return $response;
    }

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.api' => \Infrastructure\Auth\Middleware\AccessTokenChecker::class,
        'language' => \Infrastructure\Http\Middleware\LanguageMiddleware::class,
        'frenchfrogs' => FrenchFrogsMiddleware::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \Infrastructure\Http\Middleware\RedirectIfAuthenticated::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'development' => \Infrastructure\Http\Middleware\DevelopmentMiddleware::class,
    ];
}
