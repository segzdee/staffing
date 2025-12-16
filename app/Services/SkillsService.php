<?php

namespace App\Services;

use App\Models\Skill;
use App\Models\User;
use App\Models\WorkerSkill;
use App\Models\CertificationType;
use App\Models\SkillCertificationRequirement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * STAFF-REG-007: Skills Management Service
 *
 * Handles skill management for workers including:
 * - Fetching available skills by category
 * - Adding/updating worker skills
 * - Validating skill combinations
 * - Checking certification requirements
 */
class SkillsService
{
    /**
     * Get all available skills grouped by industry.
     *
     * @param string|null $industry Filter by specific industry
     * @return Collection
     */
    public function getAvailableSkills(?string $industry = null): Collection
    {
        $query = Skill::query()
            ->active()
            ->ordered()
            ->with(['requiredCertifications' => function ($q) {
                $q->where('certification_types.is_active', true);
            }]);

        if ($industry) {
            $query->byIndustry($industry);
        }

        return $query->get()->groupBy('industry');
    }

    /**
     * Get skills by category within an industry.
     *
     * @param string $industry
     * @param string|null $category
     * @return Collection
     */
    public function getSkillsByCategory(string $industry, ?string $category = null): Collection
    {
        $query = Skill::query()
            ->active()
            ->byIndustry($industry)
            ->ordered();

        if ($category) {
            $query->byCategory($category);
        }

        return $query->get();
    }

    /**
     * Get all unique categories for an industry.
     *
     * @param string $industry
     * @return Collection
     */
    public function getCategoriesForIndustry(string $industry): Collection
    {
        return Skill::query()
            ->active()
            ->byIndustry($industry)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');
    }

    /**
     * Add a skill to a worker's profile.
     *
     * @param User $worker
     * @param int $skillId
     * @param array $data
     * @return array
     */
    public function addWorkerSkill(User $worker, int $skillId, array $data): array
    {
        try {
            // Check if worker already has this skill
            $existing = WorkerSkill::where('worker_id', $worker->id)
                ->where('skill_id', $skillId)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'error' => 'You already have this skill added to your profile.',
                ];
            }

            // Verify skill exists and is active
            $skill = Skill::active()->find($skillId);
            if (!$skill) {
                return [
                    'success' => false,
                    'error' => 'Invalid skill selected.',
                ];
            }

            // Calculate experience level if not provided
            $yearsExperience = $data['years_experience'] ?? 0;
            $experienceLevel = $data['experience_level'] ??
                WorkerSkill::calculateExperienceLevel($yearsExperience);

            DB::beginTransaction();

            // Create the worker skill
            $workerSkill = WorkerSkill::create([
                'worker_id' => $worker->id,
                'skill_id' => $skillId,
                'proficiency_level' => $experienceLevel, // Keep for backward compat
                'experience_level' => $experienceLevel,
                'years_experience' => $yearsExperience,
                'experience_notes' => $data['experience_notes'] ?? null,
                'last_used_date' => $data['last_used_date'] ?? null,
                'self_assessed' => true,
                'verified' => false,
                'is_active' => true,
            ]);

            // Check if certification requirements are met
            $certCheck = $this->checkCertificationRequirements($worker, $skill);

            // If skill requires certification and worker doesn't have it, mark as inactive
            if (!$certCheck['met']) {
                $workerSkill->update(['is_active' => false]);
            }

            DB::commit();

            Log::info('Worker skill added', [
                'worker_id' => $worker->id,
                'skill_id' => $skillId,
                'experience_level' => $experienceLevel,
            ]);

            return [
                'success' => true,
                'skill' => $workerSkill->load('skill'),
                'certification_requirements' => $certCheck,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add worker skill', [
                'worker_id' => $worker->id,
                'skill_id' => $skillId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to add skill. Please try again.',
            ];
        }
    }

    /**
     * Update a worker's skill.
     *
     * @param User $worker
     * @param int $workerSkillId
     * @param array $data
     * @return array
     */
    public function updateWorkerSkill(User $worker, int $workerSkillId, array $data): array
    {
        try {
            $workerSkill = WorkerSkill::where('id', $workerSkillId)
                ->where('worker_id', $worker->id)
                ->first();

            if (!$workerSkill) {
                return [
                    'success' => false,
                    'error' => 'Skill not found.',
                ];
            }

            // Calculate experience level if years changed
            if (isset($data['years_experience'])) {
                $data['experience_level'] = $data['experience_level'] ??
                    WorkerSkill::calculateExperienceLevel($data['years_experience']);
                $data['proficiency_level'] = $data['experience_level'];
            }

            $workerSkill->update($data);

            // Re-check certification requirements
            $certCheck = $this->checkCertificationRequirements($worker, $workerSkill->skill);
            if (!$certCheck['met'] && $workerSkill->is_active) {
                $workerSkill->update(['is_active' => false]);
            } elseif ($certCheck['met'] && !$workerSkill->is_active) {
                $workerSkill->update(['is_active' => true]);
            }

            return [
                'success' => true,
                'skill' => $workerSkill->fresh()->load('skill'),
                'certification_requirements' => $certCheck,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update worker skill', [
                'worker_id' => $worker->id,
                'worker_skill_id' => $workerSkillId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update skill.',
            ];
        }
    }

    /**
     * Remove a skill from worker's profile.
     *
     * @param User $worker
     * @param int $workerSkillId
     * @return array
     */
    public function removeWorkerSkill(User $worker, int $workerSkillId): array
    {
        try {
            $deleted = WorkerSkill::where('id', $workerSkillId)
                ->where('worker_id', $worker->id)
                ->delete();

            if (!$deleted) {
                return [
                    'success' => false,
                    'error' => 'Skill not found.',
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Failed to remove worker skill', [
                'worker_id' => $worker->id,
                'worker_skill_id' => $workerSkillId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to remove skill.',
            ];
        }
    }

    /**
     * Get a worker's skills.
     *
     * @param User $worker
     * @param bool $activeOnly
     * @return Collection
     */
    public function getWorkerSkills(User $worker, bool $activeOnly = false): Collection
    {
        $query = WorkerSkill::where('worker_id', $worker->id)
            ->with(['skill', 'skill.requiredCertifications']);

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get required certifications for a skill.
     *
     * @param Skill $skill
     * @param string|null $country
     * @param string|null $state
     * @return Collection
     */
    public function getRequiredCertifications(
        Skill $skill,
        ?string $country = null,
        ?string $state = null
    ): Collection {
        $requirements = $skill->certificationRequirements()
            ->active()
            ->with('certificationType')
            ->get();

        // Filter by region if specified
        if ($country || $state) {
            $requirements = $requirements->filter(function ($req) use ($country, $state) {
                return $req->appliesTo($country, $state);
            });
        }

        return $requirements;
    }

    /**
     * Check if worker meets certification requirements for a skill.
     *
     * @param User $worker
     * @param Skill $skill
     * @param string|null $country
     * @param string|null $state
     * @return array
     */
    public function checkCertificationRequirements(
        User $worker,
        Skill $skill,
        ?string $country = null,
        ?string $state = null
    ): array {
        if (!$skill->hasCertificationRequirements()) {
            return [
                'met' => true,
                'required' => [],
                'missing' => [],
            ];
        }

        // Get required certifications
        $requirements = $this->getRequiredCertifications($skill, $country, $state)
            ->where('requirement_level', 'required');

        if ($requirements->isEmpty()) {
            return [
                'met' => true,
                'required' => [],
                'missing' => [],
            ];
        }

        // Get worker's valid certifications
        $workerCertTypeIds = $worker->certifications()
            ->wherePivot('verified', true)
            ->wherePivotNull('expiry_date')
            ->orWherePivot('expiry_date', '>', now())
            ->pluck('certifications.id')
            ->toArray();

        // Also check new certification_type_id
        $workerNewCertTypeIds = \App\Models\WorkerCertification::where('worker_id', $worker->id)
            ->whereNotNull('certification_type_id')
            ->where('verified', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            })
            ->pluck('certification_type_id')
            ->toArray();

        $required = [];
        $missing = [];

        foreach ($requirements as $req) {
            $certType = $req->certificationType;
            $required[] = $certType;

            if (!in_array($certType->id, $workerNewCertTypeIds)) {
                $missing[] = $certType;
            }
        }

        return [
            'met' => empty($missing),
            'required' => $required,
            'missing' => $missing,
        ];
    }

    /**
     * Validate a skill combination (for skills that conflict or require others).
     *
     * @param User $worker
     * @param array $skillIds
     * @return array
     */
    public function validateSkillCombination(User $worker, array $skillIds): array
    {
        // Get existing worker skills
        $existingSkillIds = WorkerSkill::where('worker_id', $worker->id)
            ->pluck('skill_id')
            ->toArray();

        $allSkillIds = array_unique(array_merge($existingSkillIds, $skillIds));

        // For now, all combinations are valid
        // This can be extended to check for conflicting skills or required prerequisite skills

        return [
            'valid' => true,
            'conflicts' => [],
            'missing_prerequisites' => [],
        ];
    }

    /**
     * Update worker's skill summary (for profile display).
     *
     * @param User $worker
     * @return array
     */
    public function updateSkillSummary(User $worker): array
    {
        $skills = $this->getWorkerSkills($worker, true);

        $summary = [
            'total_skills' => $skills->count(),
            'verified_skills' => $skills->where('verified', true)->count(),
            'by_industry' => $skills->groupBy('skill.industry')
                ->map(fn($group) => $group->count()),
            'top_skills' => $skills->sortByDesc('years_experience')
                ->take(5)
                ->map(fn($ws) => [
                    'name' => $ws->skill->name,
                    'level' => $ws->experience_level,
                    'years' => $ws->years_experience,
                ]),
        ];

        // Update worker profile if needed
        if ($worker->workerProfile) {
            $worker->workerProfile->update([
                'industries' => $summary['by_industry']->keys()->toArray(),
            ]);
        }

        return $summary;
    }

    /**
     * Activate skills when certification is verified.
     *
     * @param User $worker
     * @param int $certificationTypeId
     * @return int Number of skills activated
     */
    public function activateSkillsForCertification(User $worker, int $certificationTypeId): int
    {
        // Find skills that require this certification
        $skillIds = SkillCertificationRequirement::where('certification_type_id', $certificationTypeId)
            ->where('requirement_level', 'required')
            ->where('is_active', true)
            ->pluck('skill_id');

        // Get worker skills that are inactive due to missing certification
        $workerSkills = WorkerSkill::where('worker_id', $worker->id)
            ->whereIn('skill_id', $skillIds)
            ->where('is_active', false)
            ->get();

        $activated = 0;
        foreach ($workerSkills as $workerSkill) {
            $skill = Skill::find($workerSkill->skill_id);
            $certCheck = $this->checkCertificationRequirements($worker, $skill);

            if ($certCheck['met']) {
                $workerSkill->activate();
                $activated++;
            }
        }

        return $activated;
    }

    /**
     * Deactivate skills when certification expires.
     *
     * @param User $worker
     * @param int $certificationTypeId
     * @return int Number of skills deactivated
     */
    public function deactivateSkillsForExpiredCertification(User $worker, int $certificationTypeId): int
    {
        // Find skills that require this certification
        $skillIds = SkillCertificationRequirement::where('certification_type_id', $certificationTypeId)
            ->where('requirement_level', 'required')
            ->where('is_active', true)
            ->pluck('skill_id');

        // Deactivate worker skills
        return WorkerSkill::where('worker_id', $worker->id)
            ->whereIn('skill_id', $skillIds)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Search skills by name.
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function searchSkills(string $query, int $limit = 20): Collection
    {
        return Skill::active()
            ->where('name', 'like', "%{$query}%")
            ->ordered()
            ->limit($limit)
            ->get();
    }
}
