<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\AddSkillRequest;
use App\Http\Requests\Worker\UpdateSkillRequest;
use App\Models\Skill;
use App\Services\SkillsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * STAFF-REG-007: Worker Skills Controller
 *
 * Handles skill management for workers via API.
 */
class SkillsController extends Controller
{
    protected SkillsService $skillsService;

    public function __construct(SkillsService $skillsService)
    {
        $this->skillsService = $skillsService;
        $this->middleware('auth');
    }

    /**
     * Show skills management page (web route).
     *
     * GET /worker/skills
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $worker = auth()->user();
        $skills = $this->skillsService->getWorkerSkills($worker);
        $availableSkills = $this->skillsService->getAvailableSkills();
        return view('worker.skills', compact('skills', 'availableSkills'));
    }

    /**
     * Get all available skills grouped by industry.
     *
     * GET /api/worker/skills/available
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableSkills(Request $request): JsonResponse
    {
        $industry = $request->query('industry');

        $skills = $this->skillsService->getAvailableSkills($industry);

        return response()->json([
            'success' => true,
            'data' => [
                'skills' => $skills,
                'industries' => Skill::getIndustryOptions(),
                'experience_levels' => Skill::getExperienceLevelOptions(),
            ],
        ]);
    }

    /**
     * Get skills by category within an industry.
     *
     * GET /api/worker/skills/category
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSkillsByCategory(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|string',
            'category' => 'nullable|string',
        ]);

        $skills = $this->skillsService->getSkillsByCategory(
            $request->industry,
            $request->category
        );

        return response()->json([
            'success' => true,
            'data' => $skills,
        ]);
    }

    /**
     * Get categories for an industry.
     *
     * GET /api/worker/skills/categories
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategories(Request $request): JsonResponse
    {
        $request->validate([
            'industry' => 'required|string',
        ]);

        $categories = $this->skillsService->getCategoriesForIndustry($request->industry);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get the worker's current skills (API route).
     *
     * GET /api/worker/skills
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSkills(Request $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can access skills.',
            ], 403);
        }

        $activeOnly = $request->boolean('active_only', false);
        $skills = $this->skillsService->getWorkerSkills($worker, $activeOnly);

        $summary = $this->skillsService->updateSkillSummary($worker);

        return response()->json([
            'success' => true,
            'data' => [
                'skills' => $skills,
                'summary' => $summary,
            ],
        ]);
    }

    /**
     * Add a skill to the worker's profile.
     *
     * POST /api/worker/skills
     *
     * @param AddSkillRequest $request
     * @return JsonResponse
     */
    public function store(AddSkillRequest $request): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can add skills.',
            ], 403);
        }

        $result = $this->skillsService->addWorkerSkill(
            $worker,
            $request->skill_id,
            $request->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skill added successfully.',
            'data' => $result['skill'],
            'certification_requirements' => $result['certification_requirements'],
        ], 201);
    }

    /**
     * Update a worker's skill.
     *
     * PUT /api/worker/skills/{id}
     *
     * @param UpdateSkillRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateSkillRequest $request, int $id): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can update skills.',
            ], 403);
        }

        $result = $this->skillsService->updateWorkerSkill(
            $worker,
            $id,
            $request->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skill updated successfully.',
            'data' => $result['skill'],
            'certification_requirements' => $result['certification_requirements'],
        ]);
    }

    /**
     * Remove a skill from the worker's profile.
     *
     * DELETE /api/worker/skills/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $worker = $request->user();

        if (!$worker->isWorker()) {
            return response()->json([
                'success' => false,
                'message' => 'Only workers can remove skills.',
            ], 403);
        }

        $result = $this->skillsService->removeWorkerSkill($worker, $id);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['error'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skill removed successfully.',
        ]);
    }

    /**
     * Search for skills.
     *
     * GET /api/worker/skills/search
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $skills = $this->skillsService->searchSkills($request->q);

        return response()->json([
            'success' => true,
            'data' => $skills,
        ]);
    }

    /**
     * Get required certifications for a skill.
     *
     * GET /api/worker/skills/{id}/certifications
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getRequiredCertifications(Request $request, int $id): JsonResponse
    {
        $skill = Skill::active()->find($id);

        if (!$skill) {
            return response()->json([
                'success' => false,
                'message' => 'Skill not found.',
            ], 404);
        }

        $country = $request->query('country');
        $state = $request->query('state');

        $certifications = $this->skillsService->getRequiredCertifications($skill, $country, $state);

        return response()->json([
            'success' => true,
            'data' => $certifications,
        ]);
    }

    /**
     * Check certification requirements for a skill.
     *
     * GET /api/worker/skills/{id}/check-requirements
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function checkCertificationRequirements(Request $request, int $id): JsonResponse
    {
        $worker = $request->user();
        $skill = Skill::active()->find($id);

        if (!$skill) {
            return response()->json([
                'success' => false,
                'message' => 'Skill not found.',
            ], 404);
        }

        $country = $request->query('country');
        $state = $request->query('state');

        $requirements = $this->skillsService->checkCertificationRequirements(
            $worker,
            $skill,
            $country,
            $state
        );

        return response()->json([
            'success' => true,
            'data' => $requirements,
        ]);
    }
}
