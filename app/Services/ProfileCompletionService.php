<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerProfile;

/**
 * Service for calculating profile completion scores and providing optimization tips.
 * WKR-010: Enhanced Profile Marketing
 */
class ProfileCompletionService
{
    /**
     * Calculate profile completion percentage (0-100%).
     *
     * @param User $worker
     * @return array
     */
    public function calculateCompletion(User $worker): array
    {
        $profile = $worker->workerProfile;

        if (!$profile) {
            return [
                'score' => 0,
                'percentage' => 0,
                'sections' => [],
                'tips' => ['Please complete your worker profile to start receiving shift opportunities.'],
            ];
        }

        $sections = [
            'basic_info' => $this->checkBasicInfo($worker, $profile),
            'contact_details' => $this->checkContactDetails($profile),
            'location' => $this->checkLocation($profile),
            'experience' => $this->checkExperience($profile),
            'skills' => $this->checkSkills($worker),
            'certifications' => $this->checkCertifications($worker),
            'availability' => $this->checkAvailability($profile),
            'verification' => $this->checkVerification($profile),
            'media' => $this->checkMedia($profile),
        ];

        // Calculate total score
        $totalPoints = 0;
        $earnedPoints = 0;

        foreach ($sections as $section) {
            $totalPoints += $section['max_points'];
            $earnedPoints += $section['points'];
        }

        $percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;

        // Generate optimization tips
        $tips = $this->generateOptimizationTips($sections);

        return [
            'score' => $earnedPoints,
            'max_score' => $totalPoints,
            'percentage' => $percentage,
            'sections' => $sections,
            'tips' => $tips,
            'badges' => $this->calculateEligibleBadges($percentage, $profile),
        ];
    }

    /**
     * Check basic information section.
     *
     * @param User $worker
     * @param WorkerProfile $profile
     * @return array
     */
    protected function checkBasicInfo(User $worker, WorkerProfile $profile): array
    {
        $points = 0;
        $maxPoints = 15;
        $items = [];

        // Name (required)
        if ($worker->name) {
            $points += 2;
            $items[] = ['item' => 'Full name', 'completed' => true, 'points' => 2];
        } else {
            $items[] = ['item' => 'Full name', 'completed' => false, 'points' => 2];
        }

        // Email (required)
        if ($worker->email) {
            $points += 2;
            $items[] = ['item' => 'Email address', 'completed' => true, 'points' => 2];
        } else {
            $items[] = ['item' => 'Email address', 'completed' => false, 'points' => 2];
        }

        // Phone
        if ($profile->phone) {
            $points += 3;
            $items[] = ['item' => 'Phone number', 'completed' => true, 'points' => 3];
        } else {
            $items[] = ['item' => 'Phone number', 'completed' => false, 'points' => 3];
        }

        // Bio
        if ($profile->bio && strlen($profile->bio) >= 50) {
            $points += 5;
            $items[] = ['item' => 'Professional bio (50+ characters)', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'Professional bio (50+ characters)', 'completed' => false, 'points' => 5];
        }

        // Date of birth
        if ($profile->date_of_birth) {
            $points += 3;
            $items[] = ['item' => 'Date of birth', 'completed' => true, 'points' => 3];
        } else {
            $items[] = ['item' => 'Date of birth', 'completed' => false, 'points' => 3];
        }

        return [
            'name' => 'Basic Information',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check contact details section.
     *
     * @param WorkerProfile $profile
     * @return array
     */
    protected function checkContactDetails(WorkerProfile $profile): array
    {
        $points = 0;
        $maxPoints = 10;
        $items = [];

        // Address
        if ($profile->address) {
            $points += 3;
            $items[] = ['item' => 'Street address', 'completed' => true, 'points' => 3];
        } else {
            $items[] = ['item' => 'Street address', 'completed' => false, 'points' => 3];
        }

        // City & State
        if ($profile->city && $profile->state) {
            $points += 2;
            $items[] = ['item' => 'City and state', 'completed' => true, 'points' => 2];
        } else {
            $items[] = ['item' => 'City and state', 'completed' => false, 'points' => 2];
        }

        // Emergency contact
        if ($profile->emergency_contact_name && $profile->emergency_contact_phone) {
            $points += 5;
            $items[] = ['item' => 'Emergency contact', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'Emergency contact', 'completed' => false, 'points' => 5];
        }

        return [
            'name' => 'Contact Details',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check location section.
     *
     * @param WorkerProfile $profile
     * @return array
     */
    protected function checkLocation(WorkerProfile $profile): array
    {
        $points = 0;
        $maxPoints = 8;
        $items = [];

        // Location coordinates
        if ($profile->location_lat && $profile->location_lng) {
            $points += 3;
            $items[] = ['item' => 'Location coordinates', 'completed' => true, 'points' => 3];
        } else {
            $items[] = ['item' => 'Location coordinates', 'completed' => false, 'points' => 3];
        }

        // Commute distance
        if ($profile->max_commute_distance > 0) {
            $points += 2;
            $items[] = ['item' => 'Maximum commute distance', 'completed' => true, 'points' => 2];
        } else {
            $items[] = ['item' => 'Maximum commute distance', 'completed' => false, 'points' => 2];
        }

        // Transportation
        if ($profile->transportation) {
            $points += 3;
            $items[] = ['item' => 'Transportation method', 'completed' => true, 'points' => 3];
        } else {
            $items[] = ['item' => 'Transportation method', 'completed' => false, 'points' => 3];
        }

        return [
            'name' => 'Location & Travel',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check experience section.
     *
     * @param WorkerProfile $profile
     * @return array
     */
    protected function checkExperience(WorkerProfile $profile): array
    {
        $points = 0;
        $maxPoints = 12;
        $items = [];

        // Years of experience
        if ($profile->years_experience > 0) {
            $points += 4;
            $items[] = ['item' => 'Years of experience', 'completed' => true, 'points' => 4];
        } else {
            $items[] = ['item' => 'Years of experience', 'completed' => false, 'points' => 4];
        }

        // Industries
        if ($profile->industries && count($profile->industries) > 0) {
            $points += 4;
            $items[] = ['item' => 'Industry experience', 'completed' => true, 'points' => 4];
        } else {
            $items[] = ['item' => 'Industry experience', 'completed' => false, 'points' => 4];
        }

        // Hourly rate range
        if ($profile->hourly_rate_min && $profile->hourly_rate_max) {
            $points += 4;
            $items[] = ['item' => 'Hourly rate range', 'completed' => true, 'points' => 4];
        } else {
            $items[] = ['item' => 'Hourly rate range', 'completed' => false, 'points' => 4];
        }

        return [
            'name' => 'Experience',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check skills section.
     *
     * @param User $worker
     * @return array
     */
    protected function checkSkills(User $worker): array
    {
        $points = 0;
        $maxPoints = 15;
        $items = [];

        $skillCount = $worker->workerProfile->skills()->count();

        // At least 1 skill
        if ($skillCount >= 1) {
            $points += 5;
            $items[] = ['item' => 'At least 1 skill added', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'At least 1 skill added', 'completed' => false, 'points' => 5];
        }

        // At least 3 skills
        if ($skillCount >= 3) {
            $points += 5;
            $items[] = ['item' => 'At least 3 skills added', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'At least 3 skills added', 'completed' => false, 'points' => 5];
        }

        // Verified skills
        $verifiedSkills = $worker->workerProfile->skills()->wherePivot('verified', true)->count();
        if ($verifiedSkills > 0) {
            $points += 5;
            $items[] = ['item' => 'At least 1 verified skill', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'At least 1 verified skill', 'completed' => false, 'points' => 5];
        }

        return [
            'name' => 'Skills',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check certifications section.
     *
     * @param User $worker
     * @return array
     */
    protected function checkCertifications(User $worker): array
    {
        $points = 0;
        $maxPoints = 10;
        $items = [];

        $certCount = $worker->workerProfile->certifications()->count();
        $verifiedCerts = $worker->workerProfile->certifications()->wherePivot('verified', true)->count();

        // At least 1 certification
        if ($certCount >= 1) {
            $points += 5;
            $items[] = ['item' => 'At least 1 certification added', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'At least 1 certification added', 'completed' => false, 'points' => 5];
        }

        // Verified certification
        if ($verifiedCerts > 0) {
            $points += 5;
            $items[] = ['item' => 'At least 1 verified certification', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'At least 1 verified certification', 'completed' => false, 'points' => 5];
        }

        return [
            'name' => 'Certifications',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check availability section.
     *
     * @param WorkerProfile $profile
     * @return array
     */
    protected function checkAvailability(WorkerProfile $profile): array
    {
        $points = 0;
        $maxPoints = 10;
        $items = [];

        // Availability schedule set
        if ($profile->availability_schedule && count($profile->availability_schedule) > 0) {
            $points += 10;
            $items[] = ['item' => 'Availability schedule configured', 'completed' => true, 'points' => 10];
        } else {
            $items[] = ['item' => 'Availability schedule configured', 'completed' => false, 'points' => 10];
        }

        return [
            'name' => 'Availability',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check verification section.
     *
     * @param WorkerProfile $profile
     * @return array
     */
    protected function checkVerification(WorkerProfile $profile): array
    {
        $points = 0;
        $maxPoints = 10;
        $items = [];

        // Identity verified
        if ($profile->identity_verified) {
            $points += 5;
            $items[] = ['item' => 'Identity verified', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'Identity verified', 'completed' => false, 'points' => 5];
        }

        // Background check
        if ($profile->background_check_status === 'approved') {
            $points += 5;
            $items[] = ['item' => 'Background check approved', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'Background check approved', 'completed' => false, 'points' => 5];
        }

        return [
            'name' => 'Verification',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Check media section.
     *
     * @param WorkerProfile $profile
     * @return array
     */
    protected function checkMedia(WorkerProfile $profile): array
    {
        $points = 0;
        $maxPoints = 10;
        $items = [];

        // Profile photo
        if ($profile->profile_photo_url) {
            $points += 5;
            $items[] = ['item' => 'Profile photo uploaded', 'completed' => true, 'points' => 5];
        } else {
            $items[] = ['item' => 'Profile photo uploaded', 'completed' => false, 'points' => 5];
        }

        // Resume/CV
        if ($profile->resume_url) {
            $points += 3;
            $items[] = ['item' => 'Resume/CV uploaded', 'completed' => true, 'points' => 3];
        } else {
            $items[] = ['item' => 'Resume/CV uploaded', 'completed' => false, 'points' => 3];
        }

        // LinkedIn
        if ($profile->linkedin_url) {
            $points += 2;
            $items[] = ['item' => 'LinkedIn profile linked', 'completed' => true, 'points' => 2];
        } else {
            $items[] = ['item' => 'LinkedIn profile linked', 'completed' => false, 'points' => 2];
        }

        return [
            'name' => 'Media & Links',
            'points' => $points,
            'max_points' => $maxPoints,
            'percentage' => $maxPoints > 0 ? round(($points / $maxPoints) * 100) : 0,
            'items' => $items,
        ];
    }

    /**
     * Generate optimization tips based on missing sections.
     *
     * @param array $sections
     * @return array
     */
    protected function generateOptimizationTips(array $sections): array
    {
        $tips = [];

        // Sort sections by completion percentage (lowest first)
        uasort($sections, function ($a, $b) {
            return $a['percentage'] <=> $b['percentage'];
        });

        // Get top 5 incomplete items
        $count = 0;
        foreach ($sections as $section) {
            if ($count >= 5) {
                break;
            }

            foreach ($section['items'] as $item) {
                if (!$item['completed'] && $count < 5) {
                    $tips[] = "Complete: {$item['item']} (+{$item['points']} points)";
                    $count++;
                }
            }
        }

        // Add general tips if profile is incomplete
        if (empty($tips)) {
            $tips[] = "Your profile is complete! Keep it updated to maximize your shift opportunities.";
        }

        return $tips;
    }

    /**
     * Calculate eligible badges based on profile completion.
     *
     * @param int $percentage
     * @param WorkerProfile $profile
     * @return array
     */
    protected function calculateEligibleBadges(int $percentage, WorkerProfile $profile): array
    {
        $badges = [];

        // Profile completion badges
        if ($percentage >= 100) {
            $badges[] = 'profile_complete';
        } elseif ($percentage >= 80) {
            $badges[] = 'profile_advanced';
        } elseif ($percentage >= 50) {
            $badges[] = 'profile_intermediate';
        }

        // Reliability badges
        if ($profile->reliability_score >= 95) {
            $badges[] = 'top_pro';
        } elseif ($profile->reliability_score >= 85) {
            $badges[] = 'reliable_pro';
        }

        // Experience badges
        if ($profile->total_shifts_completed >= 100) {
            $badges[] = 'veteran_worker';
        } elseif ($profile->total_shifts_completed >= 50) {
            $badges[] = 'experienced_worker';
        } elseif ($profile->total_shifts_completed >= 10) {
            $badges[] = 'active_worker';
        }

        // Verification badges
        if ($profile->identity_verified) {
            $badges[] = 'verified_identity';
        }

        if ($profile->background_check_status === 'approved') {
            $badges[] = 'background_checked';
        }

        // Rating badges
        if ($profile->rating_average >= 4.8 && $profile->total_shifts_completed >= 10) {
            $badges[] = 'five_star_pro';
        } elseif ($profile->rating_average >= 4.5 && $profile->total_shifts_completed >= 5) {
            $badges[] = 'highly_rated';
        }

        return $badges;
    }
}
