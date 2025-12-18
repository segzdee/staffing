<?php

namespace Database\Seeders;

use App\Models\AuditChecklist;
use Illuminate\Database\Seeder;

/**
 * QUA-002: Quality Audits - Default Audit Checklists Seeder
 *
 * Seeds default audit checklist templates for the quality audit system.
 */
class AuditChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $checklists = [
            // Punctuality Checklist
            [
                'name' => 'Punctuality Assessment',
                'category' => AuditChecklist::CATEGORY_PUNCTUALITY,
                'sort_order' => 1,
                'items' => [
                    [
                        'id' => 'punct_1',
                        'question' => 'Worker arrived on time for the shift',
                        'weight' => 3.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'punct_2',
                        'question' => 'Worker clocked in within the allowed time window (15 minutes before/after)',
                        'weight' => 2.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'punct_3',
                        'question' => 'Worker was ready to start work at the scheduled time',
                        'weight' => 2.0,
                        'required' => false,
                    ],
                    [
                        'id' => 'punct_4',
                        'question' => 'Worker returned from breaks on time',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'punct_5',
                        'question' => 'Worker stayed for the entire scheduled shift duration',
                        'weight' => 2.5,
                        'required' => true,
                    ],
                ],
            ],

            // Appearance Checklist
            [
                'name' => 'Appearance & Dress Code',
                'category' => AuditChecklist::CATEGORY_APPEARANCE,
                'sort_order' => 2,
                'items' => [
                    [
                        'id' => 'appear_1',
                        'question' => 'Worker wore proper uniform/attire as specified',
                        'weight' => 3.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'appear_2',
                        'question' => 'Worker maintained a clean and neat appearance',
                        'weight' => 2.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'appear_3',
                        'question' => 'Worker wore required safety equipment (if applicable)',
                        'weight' => 3.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'appear_4',
                        'question' => 'Worker had proper footwear for the job',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'appear_5',
                        'question' => 'Worker displayed ID badge or name tag (if required)',
                        'weight' => 1.0,
                        'required' => false,
                    ],
                    [
                        'id' => 'appear_6',
                        'question' => 'Worker maintained professional appearance throughout the shift',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                ],
            ],

            // Performance Checklist
            [
                'name' => 'Job Performance & Skills',
                'category' => AuditChecklist::CATEGORY_PERFORMANCE,
                'sort_order' => 3,
                'items' => [
                    [
                        'id' => 'perf_1',
                        'question' => 'Worker performed assigned tasks correctly',
                        'weight' => 3.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'perf_2',
                        'question' => 'Worker demonstrated required skills and competencies',
                        'weight' => 3.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'perf_3',
                        'question' => 'Worker completed tasks within expected timeframes',
                        'weight' => 2.0,
                        'required' => false,
                    ],
                    [
                        'id' => 'perf_4',
                        'question' => 'Worker handled equipment and tools properly',
                        'weight' => 2.0,
                        'required' => false,
                    ],
                    [
                        'id' => 'perf_5',
                        'question' => 'Worker required minimal supervision',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'perf_6',
                        'question' => 'Worker took initiative to address issues',
                        'weight' => 1.0,
                        'required' => false,
                    ],
                    [
                        'id' => 'perf_7',
                        'question' => 'Quality of work met expectations',
                        'weight' => 2.5,
                        'required' => true,
                    ],
                ],
            ],

            // Attitude & Professionalism Checklist
            [
                'name' => 'Attitude & Professionalism',
                'category' => AuditChecklist::CATEGORY_ATTITUDE,
                'sort_order' => 4,
                'items' => [
                    [
                        'id' => 'att_1',
                        'question' => 'Worker displayed professional behavior throughout the shift',
                        'weight' => 2.5,
                        'required' => true,
                    ],
                    [
                        'id' => 'att_2',
                        'question' => 'Worker was courteous and respectful to others',
                        'weight' => 2.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'att_3',
                        'question' => 'Worker communicated effectively with supervisors and colleagues',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'att_4',
                        'question' => 'Worker handled customer/client interactions appropriately',
                        'weight' => 2.0,
                        'required' => false,
                    ],
                    [
                        'id' => 'att_5',
                        'question' => 'Worker showed positive attitude toward work',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'att_6',
                        'question' => 'Worker worked well as part of a team',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'att_7',
                        'question' => 'Worker responded appropriately to feedback and instructions',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                ],
            ],

            // Compliance Checklist
            [
                'name' => 'Compliance & Safety',
                'category' => AuditChecklist::CATEGORY_COMPLIANCE,
                'sort_order' => 5,
                'items' => [
                    [
                        'id' => 'comp_1',
                        'question' => 'Worker followed all venue rules and policies',
                        'weight' => 3.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'comp_2',
                        'question' => 'Worker adhered to safety requirements and procedures',
                        'weight' => 3.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'comp_3',
                        'question' => 'Worker followed proper clock-in/out procedures',
                        'weight' => 2.0,
                        'required' => true,
                    ],
                    [
                        'id' => 'comp_4',
                        'question' => 'Worker reported any incidents or issues promptly',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'comp_5',
                        'question' => 'Worker maintained confidentiality where required',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'comp_6',
                        'question' => 'Worker used only authorized areas and equipment',
                        'weight' => 1.5,
                        'required' => false,
                    ],
                    [
                        'id' => 'comp_7',
                        'question' => 'Worker complied with all health and hygiene requirements',
                        'weight' => 2.0,
                        'required' => true,
                    ],
                ],
            ],
        ];

        foreach ($checklists as $checklistData) {
            AuditChecklist::updateOrCreate(
                [
                    'name' => $checklistData['name'],
                    'category' => $checklistData['category'],
                ],
                [
                    'items' => $checklistData['items'],
                    'is_active' => true,
                    'sort_order' => $checklistData['sort_order'],
                ]
            );
        }

        $this->command->info('Audit checklists seeded: '.count($checklists));

        // Display summary by category
        $grouped = collect($checklists)->groupBy('category');
        foreach ($grouped as $category => $items) {
            $label = AuditChecklist::CATEGORIES[$category] ?? $category;
            $itemCount = collect($items)->sum(fn ($c) => count($c['items']));
            $this->command->info("  - {$label}: {$items->count()} checklist(s), {$itemCount} item(s)");
        }
    }
}
