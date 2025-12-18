<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DashboardAuditTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function worker_can_access_all_dashboard_routes()
    {
        $user = User::factory()->create([
            'user_type' => 'worker',
            'role' => 'worker',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        \App\Models\WorkerProfile::factory()->for($user)->create([
            'onboarding_completed' => true,
            'is_complete' => true,
        ]);

        $routes = Config::get('dashboard.navigation.worker');
        $this->assertNotEmpty($routes, 'Worker routes should be defined in config');

        // Flatten nested navigation structure
        $flatRoutes = $this->flattenNavigation($routes);

        foreach ($flatRoutes as $item) {
            if (! isset($item['route'])) {
                continue;
            }
            $routeName = $item['route'];
            if (Route::has($routeName)) {
                $response = $this->actingAs($user)->get(route($routeName));

                if ($response->status() !== 200) {
                    echo "\nFailed Route (Worker): ".$routeName.' Status: '.$response->status();
                }

                $response->assertStatus(200);
            }
        }
    }

    /**
     * Flatten nested navigation structure into a single array of items
     */
    private function flattenNavigation(array $routes): array
    {
        $flat = [];
        foreach ($routes as $key => $item) {
            if (is_string($key) && is_array($item)) {
                // This is a section with nested items
                foreach ($item as $subItem) {
                    if (is_array($subItem)) {
                        $flat[] = $subItem;
                    }
                }
            } elseif (is_array($item)) {
                // This is a flat item
                $flat[] = $item;
            }
        }

        return $flat;
    }

    /** @test */
    public function business_can_access_all_dashboard_routes()
    {
        $user = User::factory()->create([
            'user_type' => 'business',
            'role' => 'business',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        // Create verified business profile
        \App\Models\BusinessProfile::factory()->for($user)->create([
            'onboarding_completed' => true,
            'is_verified' => true,
            'is_complete' => true,
        ]);

        $routes = Config::get('dashboard.navigation.business');
        $this->assertNotEmpty($routes, 'Business routes should be defined in config');

        $flatRoutes = $this->flattenNavigation($routes);

        foreach ($flatRoutes as $item) {
            if (! isset($item['route'])) {
                continue;
            }
            $routeName = $item['route'];
            if (Route::has($routeName)) {
                $response = $this->actingAs($user)->get(route($routeName));

                if ($response->status() !== 200) {
                    echo "\nFailed Route (Business): ".$routeName.' Status: '.$response->status();
                }
                $response->assertStatus(200);
            }
        }
    }

    /** @test */
    public function agency_can_access_all_dashboard_routes()
    {
        $user = User::factory()->create([
            'user_type' => 'agency',
            'role' => 'agency',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        \App\Models\AgencyProfile::factory()->for($user)->create([
            'onboarding_completed' => true,
            'is_verified' => true,
            'is_complete' => true,
        ]);

        $routes = Config::get('dashboard.navigation.agency');
        $this->assertNotEmpty($routes, 'Agency routes should be defined in config');

        $flatRoutes = $this->flattenNavigation($routes);

        foreach ($flatRoutes as $item) {
            if (! isset($item['route'])) {
                continue;
            }
            $routeName = $item['route'];
            if (Route::has($routeName)) {
                $response = $this->actingAs($user)->get(route($routeName));
                if ($response->status() !== 200) {
                    echo "\nFailed Route (Agency): ".$routeName.' Status: '.$response->status();
                }
                $response->assertStatus(200);
            }
        }
    }

    /** @test */
    public function admin_can_access_all_dashboard_routes()
    {
        $user = User::factory()->create([
            'user_type' => 'admin',
            'role' => 'admin',
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $routes = Config::get('dashboard.navigation.admin');

        // Skip if no routes defined or config missing
        if (empty($routes)) {
            $this->markTestSkipped('Admin routes config not found');

            return;
        }

        $flatRoutes = $this->flattenNavigation($routes);

        foreach ($flatRoutes as $item) {
            if (! isset($item['route'])) {
                continue;
            }
            $routeName = $item['route'];
            // Skip logout or other special routes
            if ($routeName === 'logout') {
                continue;
            }

            if (Route::has($routeName)) {
                $response = $this->actingAs($user)->get(route($routeName));

                if ($response->status() !== 200) {
                    echo "\nFailed Route (Admin): ".$routeName.' Status: '.$response->status();
                }
                $response->assertStatus(200);
            }
        }
    }
}
