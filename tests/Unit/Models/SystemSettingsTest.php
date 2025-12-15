<?php

namespace Tests\Unit\Models;

use App\Models\SystemSettings;
use App\Models\SystemSettingAudit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * ADM-003: SystemSettings Model Unit Tests
 */
class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_casts_string_values_correctly()
    {
        $setting = SystemSettings::create([
            'key' => 'test_string',
            'value' => 'hello world',
            'category' => 'general',
            'data_type' => 'string',
            'is_public' => true,
        ]);

        $this->assertIsString($setting->typed_value);
        $this->assertEquals('hello world', $setting->typed_value);
    }

    /** @test */
    public function it_casts_integer_values_correctly()
    {
        $setting = SystemSettings::create([
            'key' => 'test_integer',
            'value' => '42',
            'category' => 'general',
            'data_type' => 'integer',
            'is_public' => true,
        ]);

        $this->assertIsInt($setting->typed_value);
        $this->assertEquals(42, $setting->typed_value);
    }

    /** @test */
    public function it_casts_decimal_values_correctly()
    {
        $setting = SystemSettings::create([
            'key' => 'test_decimal',
            'value' => '3.14159',
            'category' => 'general',
            'data_type' => 'decimal',
            'is_public' => true,
        ]);

        $this->assertIsFloat($setting->typed_value);
        $this->assertEquals(3.14159, $setting->typed_value);
    }

    /** @test */
    public function it_casts_boolean_true_correctly()
    {
        $setting = SystemSettings::create([
            'key' => 'test_bool_true',
            'value' => '1',
            'category' => 'general',
            'data_type' => 'boolean',
            'is_public' => true,
        ]);

        $this->assertIsBool($setting->typed_value);
        $this->assertTrue($setting->typed_value);
    }

    /** @test */
    public function it_casts_boolean_false_correctly()
    {
        $setting = SystemSettings::create([
            'key' => 'test_bool_false',
            'value' => '0',
            'category' => 'general',
            'data_type' => 'boolean',
            'is_public' => true,
        ]);

        $this->assertIsBool($setting->typed_value);
        $this->assertFalse($setting->typed_value);
    }

    /** @test */
    public function it_casts_json_values_correctly()
    {
        $setting = SystemSettings::create([
            'key' => 'test_json',
            'value' => '{"name":"test","items":[1,2,3]}',
            'category' => 'general',
            'data_type' => 'json',
            'is_public' => true,
        ]);

        $this->assertIsArray($setting->typed_value);
        $this->assertEquals(['name' => 'test', 'items' => [1, 2, 3]], $setting->typed_value);
    }

    /** @test */
    public function get_method_uses_cache()
    {
        SystemSettings::create([
            'key' => 'cached_setting',
            'value' => 'original',
            'category' => 'general',
            'data_type' => 'string',
            'is_public' => true,
        ]);

        // First call populates cache
        $value1 = SystemSettings::get('cached_setting');

        // Directly update database bypassing model events
        \DB::table('system_settings')
            ->where('key', 'cached_setting')
            ->update(['value' => 'modified']);

        // Second call should still return cached value
        $value2 = SystemSettings::get('cached_setting');

        $this->assertEquals('original', $value1);
        $this->assertEquals('original', $value2);

        // Clear cache and verify
        SystemSettings::clearCache('cached_setting');
        $value3 = SystemSettings::get('cached_setting');
        $this->assertEquals('modified', $value3);
    }

    /** @test */
    public function get_returns_default_for_missing_key()
    {
        $value = SystemSettings::get('nonexistent_key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /** @test */
    public function set_method_updates_value()
    {
        $user = User::factory()->create();

        SystemSettings::create([
            'key' => 'updatable_setting',
            'value' => 'old_value',
            'category' => 'general',
            'data_type' => 'string',
            'is_public' => true,
        ]);

        SystemSettings::set('updatable_setting', 'new_value', $user->id);

        $this->assertEquals('new_value', SystemSettings::get('updatable_setting'));
    }

    /** @test */
    public function set_method_throws_exception_for_missing_key()
    {
        $this->expectException(\InvalidArgumentException::class);

        SystemSettings::set('nonexistent_key', 'value');
    }

    /** @test */
    public function set_method_creates_audit_entry()
    {
        $user = User::factory()->create();

        $setting = SystemSettings::create([
            'key' => 'audited_setting',
            'value' => 'old',
            'category' => 'general',
            'data_type' => 'string',
            'is_public' => true,
        ]);

        SystemSettings::set('audited_setting', 'new', $user->id);

        $this->assertDatabaseHas('system_setting_audits', [
            'setting_id' => $setting->id,
            'key' => 'audited_setting',
            'old_value' => 'old',
            'new_value' => 'new',
            'changed_by' => $user->id,
        ]);
    }

    /** @test */
    public function batch_update_updates_multiple_settings()
    {
        $user = User::factory()->create();

        SystemSettings::create(['key' => 'batch_1', 'value' => 'a', 'category' => 'general', 'data_type' => 'string', 'is_public' => true]);
        SystemSettings::create(['key' => 'batch_2', 'value' => 'b', 'category' => 'general', 'data_type' => 'string', 'is_public' => true]);

        SystemSettings::batchUpdate([
            'batch_1' => 'x',
            'batch_2' => 'y',
        ], $user->id);

        $this->assertEquals('x', SystemSettings::get('batch_1'));
        $this->assertEquals('y', SystemSettings::get('batch_2'));
    }

    /** @test */
    public function category_scope_filters_correctly()
    {
        SystemSettings::create(['key' => 'fees_1', 'value' => '1', 'category' => 'fees', 'data_type' => 'string', 'is_public' => true]);
        SystemSettings::create(['key' => 'fees_2', 'value' => '2', 'category' => 'fees', 'data_type' => 'string', 'is_public' => true]);
        SystemSettings::create(['key' => 'limits_1', 'value' => '3', 'category' => 'limits', 'data_type' => 'string', 'is_public' => true]);

        $feeSettings = SystemSettings::category('fees')->get();

        $this->assertEquals(2, $feeSettings->count());
        $this->assertTrue($feeSettings->pluck('key')->contains('fees_1'));
        $this->assertTrue($feeSettings->pluck('key')->contains('fees_2'));
    }

    /** @test */
    public function public_scope_filters_correctly()
    {
        SystemSettings::create(['key' => 'public_1', 'value' => '1', 'category' => 'general', 'data_type' => 'string', 'is_public' => true]);
        SystemSettings::create(['key' => 'private_1', 'value' => '2', 'category' => 'general', 'data_type' => 'string', 'is_public' => false]);

        $publicSettings = SystemSettings::public()->get();

        $this->assertTrue($publicSettings->pluck('key')->contains('public_1'));
        $this->assertFalse($publicSettings->pluck('key')->contains('private_1'));
    }

    /** @test */
    public function private_scope_filters_correctly()
    {
        SystemSettings::create(['key' => 'public_2', 'value' => '1', 'category' => 'general', 'data_type' => 'string', 'is_public' => true]);
        SystemSettings::create(['key' => 'private_2', 'value' => '2', 'category' => 'general', 'data_type' => 'string', 'is_public' => false]);

        $privateSettings = SystemSettings::private()->get();

        $this->assertFalse($privateSettings->pluck('key')->contains('public_2'));
        $this->assertTrue($privateSettings->pluck('key')->contains('private_2'));
    }

    /** @test */
    public function search_scope_finds_by_key()
    {
        SystemSettings::create(['key' => 'platform_fee', 'value' => '1', 'category' => 'fees', 'data_type' => 'string', 'is_public' => true, 'description' => 'Fee setting']);
        SystemSettings::create(['key' => 'max_workers', 'value' => '2', 'category' => 'limits', 'data_type' => 'string', 'is_public' => true, 'description' => 'Limit setting']);

        $results = SystemSettings::search('fee')->get();

        $this->assertEquals(1, $results->count());
        $this->assertEquals('platform_fee', $results->first()->key);
    }

    /** @test */
    public function search_scope_finds_by_description()
    {
        SystemSettings::create(['key' => 'setting_abc', 'value' => '1', 'category' => 'general', 'data_type' => 'string', 'is_public' => true, 'description' => 'This is a special description']);

        $results = SystemSettings::search('special')->get();

        $this->assertEquals(1, $results->count());
        $this->assertEquals('setting_abc', $results->first()->key);
    }

    /** @test */
    public function all_grouped_groups_by_category()
    {
        SystemSettings::create(['key' => 'group_fees_1', 'value' => '1', 'category' => 'fees', 'data_type' => 'string', 'is_public' => true]);
        SystemSettings::create(['key' => 'group_limits_1', 'value' => '2', 'category' => 'limits', 'data_type' => 'string', 'is_public' => true]);

        // Clear cache to ensure fresh data
        Cache::forget(SystemSettings::CACHE_ALL_KEY . ':grouped');

        $grouped = SystemSettings::allGrouped();

        $this->assertTrue($grouped->has('fees'));
        $this->assertTrue($grouped->has('limits'));
    }

    /** @test */
    public function get_public_settings_returns_key_value_array()
    {
        SystemSettings::create(['key' => 'public_setting', 'value' => '100', 'category' => 'general', 'data_type' => 'integer', 'is_public' => true]);
        SystemSettings::create(['key' => 'private_setting', 'value' => 'secret', 'category' => 'general', 'data_type' => 'string', 'is_public' => false]);

        // Clear cache
        Cache::forget(SystemSettings::CACHE_PREFIX . 'public');

        $publicSettings = SystemSettings::getPublicSettings();

        $this->assertArrayHasKey('public_setting', $publicSettings);
        $this->assertArrayNotHasKey('private_setting', $publicSettings);
        $this->assertEquals(100, $publicSettings['public_setting']); // Should be typed
    }

    /** @test */
    public function get_defaults_returns_expected_structure()
    {
        $defaults = SystemSettings::getDefaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('platform_fee_percentage', $defaults);

        $setting = $defaults['platform_fee_percentage'];
        $this->assertArrayHasKey('value', $setting);
        $this->assertArrayHasKey('category', $setting);
        $this->assertArrayHasKey('description', $setting);
        $this->assertArrayHasKey('data_type', $setting);
        $this->assertArrayHasKey('is_public', $setting);
    }

    /** @test */
    public function get_categories_returns_all_categories()
    {
        $categories = SystemSettings::getCategories();

        $this->assertIsArray($categories);
        $this->assertArrayHasKey('fees', $categories);
        $this->assertArrayHasKey('payment', $categories);
        $this->assertArrayHasKey('limits', $categories);
        $this->assertArrayHasKey('features', $categories);
    }

    /** @test */
    public function get_data_types_returns_all_types()
    {
        $types = SystemSettings::getDataTypes();

        $this->assertIsArray($types);
        $this->assertArrayHasKey('string', $types);
        $this->assertArrayHasKey('integer', $types);
        $this->assertArrayHasKey('decimal', $types);
        $this->assertArrayHasKey('boolean', $types);
        $this->assertArrayHasKey('json', $types);
    }

    /** @test */
    public function reset_to_default_works()
    {
        $user = User::factory()->create();

        SystemSettings::create([
            'key' => 'platform_fee_percentage',
            'value' => '25', // Changed from default
            'category' => 'fees',
            'data_type' => 'decimal',
            'is_public' => true,
        ]);

        $result = SystemSettings::resetToDefault('platform_fee_percentage', $user->id);

        $this->assertTrue($result);
        $this->assertEquals('10', SystemSettings::where('key', 'platform_fee_percentage')->first()->value);
    }

    /** @test */
    public function reset_to_default_returns_false_for_unknown_key()
    {
        $result = SystemSettings::resetToDefault('unknown_setting_key');

        $this->assertFalse($result);
    }

    /** @test */
    public function model_has_last_modified_by_relationship()
    {
        $user = User::factory()->create();

        $setting = SystemSettings::create([
            'key' => 'rel_test',
            'value' => '1',
            'category' => 'general',
            'data_type' => 'string',
            'is_public' => true,
            'last_modified_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $setting->lastModifiedBy);
        $this->assertEquals($user->id, $setting->lastModifiedBy->id);
    }

    /** @test */
    public function model_has_audits_relationship()
    {
        $user = User::factory()->create();

        $setting = SystemSettings::create([
            'key' => 'audit_rel_test',
            'value' => '1',
            'category' => 'general',
            'data_type' => 'string',
            'is_public' => true,
        ]);

        SystemSettingAudit::create([
            'setting_id' => $setting->id,
            'key' => $setting->key,
            'old_value' => '1',
            'new_value' => '2',
            'changed_by' => $user->id,
            'created_at' => now(),
        ]);

        $this->assertCount(1, $setting->audits);
    }

    /** @test */
    public function clear_all_cache_clears_everything()
    {
        // Populate some cached values
        Cache::put(SystemSettings::CACHE_PREFIX . 'test_key', 'value', 3600);
        Cache::put(SystemSettings::CACHE_ALL_KEY, 'all', 3600);
        Cache::put(SystemSettings::CACHE_ALL_KEY . ':grouped', 'grouped', 3600);

        SystemSettings::clearAllCache();

        $this->assertNull(Cache::get(SystemSettings::CACHE_ALL_KEY));
        $this->assertNull(Cache::get(SystemSettings::CACHE_ALL_KEY . ':grouped'));
    }
}
