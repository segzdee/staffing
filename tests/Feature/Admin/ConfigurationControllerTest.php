<?php

namespace Tests\Feature\Admin;

use App\Models\SystemSettings;
use App\Models\SystemSettingAudit;
use App\Models\User;
use Tests\Traits\DatabaseMigrationsWithTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ADM-003: Platform Configuration Management Tests
 */
class ConfigurationControllerTest extends TestCase
{
    use DatabaseMigrationsWithTransactions;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize migrations (runs once, sets up transactions)
        $this->initializeMigrations();

        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'user_type' => 'admin',
            'is_dev_account' => true, // Skip MFA for tests
        ]);

        // Create regular user
        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'user_type' => 'worker',
        ]);

        // Seed some test settings
        $this->seedTestSettings();
    }

    protected function seedTestSettings(): void
    {
        SystemSettings::create([
            'key' => 'platform_fee_percentage',
            'value' => '10',
            'category' => 'fees',
            'description' => 'Platform commission fee percentage',
            'data_type' => 'decimal',
            'is_public' => true,
        ]);

        SystemSettings::create([
            'key' => 'feature_instant_claim',
            'value' => '1',
            'category' => 'features',
            'description' => 'Enable instant shift claiming',
            'data_type' => 'boolean',
            'is_public' => true,
        ]);

        SystemSettings::create([
            'key' => 'max_shifts_per_day_worker',
            'value' => '3',
            'category' => 'limits',
            'description' => 'Maximum shifts per day',
            'data_type' => 'integer',
            'is_public' => true,
        ]);

        SystemSettings::create([
            'key' => 'supported_currencies',
            'value' => '["USD","EUR"]',
            'category' => 'payment',
            'description' => 'Supported currencies',
            'data_type' => 'json',
            'is_public' => true,
        ]);
    }

    /** @test */
    public function admin_can_view_configuration_index()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.configuration.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.configuration.index');
        $response->assertSee('Platform Configuration');
        $response->assertSee('platform_fee_percentage');
    }

    /** @test */
    public function regular_user_cannot_access_configuration()
    {
        $response = $this->actingAs($this->regularUser)->get(route('admin.configuration.index'));

        // Should redirect or show unauthorized
        $this->assertTrue($response->status() === 302 || $response->status() === 403);
    }

    /** @test */
    public function admin_can_filter_by_category()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.configuration.index', [
            'category' => 'fees',
        ]));

        $response->assertStatus(200);
        $response->assertSee('platform_fee_percentage');
    }

    /** @test */
    public function admin_can_search_settings()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.configuration.index', [
            'search' => 'fee',
        ]));

        $response->assertStatus(200);
        $response->assertSee('platform_fee_percentage');
    }

    /** @test */
    public function admin_can_update_settings()
    {
        $response = $this->actingAs($this->admin)->put(route('admin.configuration.update'), [
            'settings' => [
                'platform_fee_percentage' => '12.5',
                'max_shifts_per_day_worker' => '5',
            ],
        ]);

        $response->assertRedirect(route('admin.configuration.index'));
        $response->assertSessionHas('success');

        $this->assertEquals('12.5', SystemSettings::get('platform_fee_percentage'));
        $this->assertEquals(5, SystemSettings::get('max_shifts_per_day_worker'));
    }

    /** @test */
    public function update_creates_audit_trail()
    {
        $this->actingAs($this->admin)->put(route('admin.configuration.update'), [
            'settings' => [
                'platform_fee_percentage' => '15',
            ],
        ]);

        $this->assertDatabaseHas('system_setting_audits', [
            'key' => 'platform_fee_percentage',
            'old_value' => '10',
            'new_value' => '15',
            'changed_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function admin_can_view_history()
    {
        // Create some audit entries
        SystemSettingAudit::create([
            'setting_id' => SystemSettings::where('key', 'platform_fee_percentage')->first()->id,
            'key' => 'platform_fee_percentage',
            'old_value' => '10',
            'new_value' => '12',
            'changed_by' => $this->admin->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.configuration.history'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.configuration.history');
        $response->assertSee('Configuration History');
    }

    /** @test */
    public function admin_can_reset_setting_to_default()
    {
        // Change the value first
        SystemSettings::set('platform_fee_percentage', '25', $this->admin->id);

        $response = $this->actingAs($this->admin)->post(route('admin.configuration.reset', 'platform_fee_percentage'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Value should be reset to default (10)
        $this->assertEquals('10', SystemSettings::where('key', 'platform_fee_percentage')->first()->value);
    }

    /** @test */
    public function admin_can_export_settings()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.configuration.export'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
        $response->assertHeader('Content-Disposition');
    }

    /** @test */
    public function admin_can_clear_cache()
    {
        // Set a value in cache
        Cache::put('system_settings:platform_fee_percentage', '10', 3600);

        $response = $this->actingAs($this->admin)->post(route('admin.configuration.clear-cache'));

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function validates_integer_data_type()
    {
        $response = $this->actingAs($this->admin)->put(route('admin.configuration.update'), [
            'settings' => [
                'max_shifts_per_day_worker' => 'not-a-number',
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function validates_decimal_data_type()
    {
        $response = $this->actingAs($this->admin)->put(route('admin.configuration.update'), [
            'settings' => [
                'platform_fee_percentage' => 'invalid',
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function validates_json_data_type()
    {
        $response = $this->actingAs($this->admin)->put(route('admin.configuration.update'), [
            'settings' => [
                'supported_currencies' => 'not-valid-json{',
            ],
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function setting_history_endpoint_returns_json()
    {
        // Create audit entries
        SystemSettingAudit::create([
            'setting_id' => SystemSettings::where('key', 'platform_fee_percentage')->first()->id,
            'key' => 'platform_fee_percentage',
            'old_value' => '10',
            'new_value' => '12',
            'changed_by' => $this->admin->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.configuration.setting-history', 'platform_fee_percentage'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }

    /** @test */
    public function caching_works_correctly()
    {
        // First call should hit the database
        $value1 = SystemSettings::get('platform_fee_percentage');

        // Second call should hit the cache
        $value2 = SystemSettings::get('platform_fee_percentage');

        $this->assertEquals($value1, $value2);
        $this->assertEquals(10.0, $value1); // Decimal type
    }

    /** @test */
    public function type_casting_works_correctly()
    {
        // Integer
        $this->assertIsInt(SystemSettings::get('max_shifts_per_day_worker'));

        // Decimal
        $this->assertIsFloat(SystemSettings::get('platform_fee_percentage'));

        // Boolean
        $this->assertIsBool(SystemSettings::get('feature_instant_claim'));

        // JSON
        $this->assertIsArray(SystemSettings::get('supported_currencies'));
    }

    /** @test */
    public function public_settings_scope_works()
    {
        $publicSettings = SystemSettings::public()->get();

        $this->assertGreaterThan(0, $publicSettings->count());
        $this->assertTrue($publicSettings->every(fn($s) => $s->is_public === true));
    }

    /** @test */
    public function category_scope_works()
    {
        $feeSettings = SystemSettings::category('fees')->get();

        $this->assertGreaterThan(0, $feeSettings->count());
        $this->assertTrue($feeSettings->every(fn($s) => $s->category === 'fees'));
    }

    /** @test */
    public function batch_update_works()
    {
        $updates = [
            'platform_fee_percentage' => '20',
            'max_shifts_per_day_worker' => '10',
        ];

        SystemSettings::batchUpdate($updates, $this->admin->id);

        $this->assertEquals(20.0, SystemSettings::get('platform_fee_percentage'));
        $this->assertEquals(10, SystemSettings::get('max_shifts_per_day_worker'));
    }

    /** @test */
    public function all_grouped_returns_correct_structure()
    {
        $grouped = SystemSettings::allGrouped();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $grouped);
        $this->assertTrue($grouped->has('fees'));
        $this->assertTrue($grouped->has('features'));
    }

    /** @test */
    public function audit_statistics_are_calculated()
    {
        // Create some audit entries
        for ($i = 0; $i < 5; $i++) {
            SystemSettingAudit::create([
                'setting_id' => SystemSettings::first()->id,
                'key' => 'platform_fee_percentage',
                'old_value' => (string) $i,
                'new_value' => (string) ($i + 1),
                'changed_by' => $this->admin->id,
                'created_at' => now(),
            ]);
        }

        $stats = SystemSettingAudit::getStatistics();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('today', $stats);
        $this->assertArrayHasKey('this_week', $stats);
        $this->assertArrayHasKey('this_month', $stats);
        $this->assertEquals(5, $stats['total']);
    }
}
