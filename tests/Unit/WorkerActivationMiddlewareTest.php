<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\EnsureWorkerActivated;
use App\Models\User;
use App\Models\WorkerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Mockery;

/**
 * Unit Test for EnsureWorkerActivated Middleware
 * STAFF-REG-011: Worker Account Activation
 */
class WorkerActivationMiddlewareTest extends TestCase
{
    protected EnsureWorkerActivated $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new EnsureWorkerActivated();
    }

    /** @test */
    public function middleware_allows_non_worker_users()
    {
        $user = new User();
        $user->user_type = 'business';

        $request = Request::create('/worker/shifts', 'GET');
        $request->setUserResolver(fn() => $user);

        $next = fn($request) => new Response('OK', 200);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function middleware_blocks_non_activated_worker_on_protected_route()
    {
        $user = new User();
        $user->user_type = 'worker';

        $profile = new WorkerProfile();
        $profile->is_activated = false;
        $profile->is_matching_eligible = false;
        $user->setRelation('workerProfile', $profile);

        // Ensure route exists (it should be defined in routes/web.php)
        // Just ensure routes are loaded
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();

        $request = Request::create('/worker/shifts/browse', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->setRouteResolver(function() {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('getName')->andReturn('worker.shifts.browse');
            return $route;
        });

        $next = fn($request) => new Response('OK', 200);

        $response = $this->middleware->handle($request, $next);

        // Should redirect
        $this->assertEquals(302, $response->status());
    }

    /** @test */
    public function middleware_allows_activated_worker_on_protected_route()
    {
        $user = new User();
        $user->user_type = 'worker';

        $profile = new WorkerProfile();
        $profile->is_activated = true;
        $profile->is_matching_eligible = true;
        $user->setRelation('workerProfile', $profile);

        $request = Request::create('/worker/shifts/browse', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->setRouteResolver(function() {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('getName')->andReturn('worker.shifts.browse');
            return $route;
        });

        $next = fn($request) => new Response('OK', 200);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->status());
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function middleware_allows_access_to_profile_routes_without_activation()
    {
        $user = new User();
        $user->user_type = 'worker';

        $profile = new WorkerProfile();
        $profile->is_activated = false;
        $profile->is_matching_eligible = false;
        $user->setRelation('workerProfile', $profile);

        $request = Request::create('/worker/profile', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->setRouteResolver(function() {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('getName')->andReturn('worker.profile');
            return $route;
        });

        $next = fn($request) => new Response('OK', 200);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function middleware_allows_access_to_activation_routes_without_activation()
    {
        $user = new User();
        $user->user_type = 'worker';

        $profile = new WorkerProfile();
        $profile->is_activated = false;
        $profile->is_matching_eligible = false;
        $user->setRelation('workerProfile', $profile);

        $request = Request::create('/worker/activation', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->setRouteResolver(function() {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('getName')->andReturn('worker.activation.index');
            return $route;
        });

        $next = fn($request) => new Response('OK', 200);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->status());
    }

    /** @test */
    public function middleware_blocks_worker_without_matching_eligibility()
    {
        $user = new User();
        $user->user_type = 'worker';

        $profile = new WorkerProfile();
        $profile->is_activated = true;
        $profile->is_matching_eligible = false;
        $profile->matching_eligibility_reason = 'Account suspended';
        $user->setRelation('workerProfile', $profile);

        // Ensure route exists (it should be defined in routes/web.php)
        \Illuminate\Support\Facades\Route::getRoutes()->refreshNameLookups();

        $request = Request::create('/worker/shifts/browse', 'GET');
        $request->setUserResolver(fn() => $user);
        $request->setRouteResolver(function() {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('getName')->andReturn('worker.shifts.browse');
            return $route;
        });

        $next = fn($request) => new Response('OK', 200);

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(302, $response->status());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
