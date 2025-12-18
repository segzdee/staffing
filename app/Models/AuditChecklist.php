<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * QUA-002: Quality Audits - Audit Checklist Model
 *
 * Defines reusable audit checklist templates organized by category.
 * Each checklist contains weighted items that auditors evaluate.
 *
 * @property int $id
 * @property string $name
 * @property string $category
 * @property array $items
 * @property bool $is_active
 * @property int $sort_order
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class AuditChecklist extends Model
{
    use HasFactory;

    /**
     * Checklist categories.
     */
    public const CATEGORY_PUNCTUALITY = 'punctuality';

    public const CATEGORY_APPEARANCE = 'appearance';

    public const CATEGORY_PERFORMANCE = 'performance';

    public const CATEGORY_COMPLIANCE = 'compliance';

    public const CATEGORY_ATTITUDE = 'attitude';

    public const CATEGORIES = [
        self::CATEGORY_PUNCTUALITY => 'Punctuality',
        self::CATEGORY_APPEARANCE => 'Appearance',
        self::CATEGORY_PERFORMANCE => 'Performance',
        self::CATEGORY_COMPLIANCE => 'Compliance',
        self::CATEGORY_ATTITUDE => 'Attitude & Professionalism',
    ];

    protected $fillable = [
        'name',
        'category',
        'items',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'items' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'category_label',
        'item_count',
        'total_weight',
    ];

    // =========================================
    // Accessors
    // =========================================

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Get the number of items in the checklist.
     */
    public function getItemCountAttribute(): int
    {
        return count($this->items ?? []);
    }

    /**
     * Get the total weight of all items.
     */
    public function getTotalWeightAttribute(): float
    {
        if (empty($this->items)) {
            return 0;
        }

        return collect($this->items)->sum('weight');
    }

    /**
     * Get the count of required items.
     */
    public function getRequiredItemCountAttribute(): int
    {
        if (empty($this->items)) {
            return 0;
        }

        return collect($this->items)->where('required', true)->count();
    }

    // =========================================
    // Scopes
    // =========================================

    /**
     * Scope to get active checklists only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get checklists by category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // =========================================
    // Methods
    // =========================================

    /**
     * Add an item to the checklist.
     */
    public function addItem(string $question, float $weight = 1.0, bool $required = false): void
    {
        $items = $this->items ?? [];
        $items[] = [
            'id' => uniqid('item_'),
            'question' => $question,
            'weight' => $weight,
            'required' => $required,
        ];
        $this->update(['items' => $items]);
    }

    /**
     * Remove an item from the checklist by ID.
     */
    public function removeItem(string $itemId): bool
    {
        $items = $this->items ?? [];
        $originalCount = count($items);

        $items = collect($items)
            ->filter(fn ($item) => $item['id'] !== $itemId)
            ->values()
            ->toArray();

        if (count($items) < $originalCount) {
            $this->update(['items' => $items]);

            return true;
        }

        return false;
    }

    /**
     * Update an item in the checklist.
     */
    public function updateItem(string $itemId, array $data): bool
    {
        $items = $this->items ?? [];

        foreach ($items as $key => $item) {
            if ($item['id'] === $itemId) {
                $items[$key] = array_merge($item, $data);
                $this->update(['items' => $items]);

                return true;
            }
        }

        return false;
    }

    /**
     * Get items as a collection.
     */
    public function getItemsCollection(): \Illuminate\Support\Collection
    {
        return collect($this->items ?? []);
    }

    /**
     * Get required items.
     */
    public function getRequiredItems(): array
    {
        return collect($this->items ?? [])
            ->where('required', true)
            ->values()
            ->toArray();
    }

    /**
     * Calculate score based on completed checklist results.
     *
     * @param  array  $results  Array of [item_id => ['passed' => bool, 'notes' => string]]
     * @return array ['score' => float, 'passed_all_required' => bool, 'details' => array]
     */
    public function calculateScore(array $results): array
    {
        $items = collect($this->items ?? []);
        $totalWeight = $items->sum('weight');
        $earnedWeight = 0;
        $passedAllRequired = true;
        $details = [];

        foreach ($items as $item) {
            $itemId = $item['id'];
            $result = $results[$itemId] ?? null;
            $passed = $result['passed'] ?? false;

            if ($passed) {
                $earnedWeight += $item['weight'];
            } elseif ($item['required'] ?? false) {
                $passedAllRequired = false;
            }

            $details[] = [
                'id' => $itemId,
                'question' => $item['question'],
                'weight' => $item['weight'],
                'required' => $item['required'] ?? false,
                'passed' => $passed,
                'notes' => $result['notes'] ?? null,
            ];
        }

        $score = $totalWeight > 0 ? round(($earnedWeight / $totalWeight) * 100, 2) : 0;

        return [
            'score' => $score,
            'passed_all_required' => $passedAllRequired,
            'total_weight' => $totalWeight,
            'earned_weight' => $earnedWeight,
            'details' => $details,
        ];
    }

    /**
     * Clone this checklist with a new name.
     */
    public function duplicate(string $newName): self
    {
        return self::create([
            'name' => $newName,
            'category' => $this->category,
            'items' => $this->items,
            'is_active' => true,
            'sort_order' => self::max('sort_order') + 1,
        ]);
    }

    /**
     * Get all categories with their labels.
     */
    public static function getAllCategories(): array
    {
        return self::CATEGORIES;
    }

    /**
     * Get active checklists grouped by category.
     */
    public static function getGroupedByCategory(): \Illuminate\Support\Collection
    {
        return self::active()
            ->ordered()
            ->get()
            ->groupBy('category');
    }
}
