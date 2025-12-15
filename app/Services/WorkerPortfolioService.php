<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerPortfolioItem;
use App\Models\WorkerFeaturedStatus;
use App\Models\WorkerProfileView;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Worker Portfolio Service
 * WKR-010: Worker Portfolio & Showcase Features
 *
 * Handles portfolio item uploads, management, and public profile generation.
 */
class WorkerPortfolioService
{
    /**
     * Storage disk for portfolio files.
     */
    protected string $disk = 'public';

    /**
     * Base path for portfolio storage.
     */
    protected string $basePath = 'portfolios';

    /**
     * Thumbnail dimensions.
     */
    protected int $thumbnailWidth = 300;
    protected int $thumbnailHeight = 300;

    /**
     * Upload a portfolio item.
     *
     * @param User $worker
     * @param UploadedFile $file
     * @param string $type
     * @param string $title
     * @param string|null $description
     * @return WorkerPortfolioItem
     * @throws \Exception
     */
    public function uploadItem(
        User $worker,
        UploadedFile $file,
        string $type,
        string $title,
        ?string $description = null
    ): WorkerPortfolioItem {
        // Validate worker can add more items
        $currentCount = WorkerPortfolioItem::where('worker_id', $worker->id)->count();
        if ($currentCount >= WorkerPortfolioItem::MAX_ITEMS_PER_WORKER) {
            throw new \Exception('Maximum portfolio items limit reached (' . WorkerPortfolioItem::MAX_ITEMS_PER_WORKER . ')');
        }

        // Validate file type and size
        $mimeType = $file->getMimeType();
        if (!WorkerPortfolioItem::isAllowedMimeType($type, $mimeType)) {
            throw new \Exception('File type not allowed for ' . $type);
        }

        $maxSize = WorkerPortfolioItem::getMaxFileSize($type);
        if ($file->getSize() > $maxSize) {
            throw new \Exception('File size exceeds maximum allowed (' . $this->formatFileSize($maxSize) . ')');
        }

        // Generate storage paths
        $workerFolder = $this->basePath . '/' . $worker->id;
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = $workerFolder . '/' . $filename;

        // Store the file
        Storage::disk($this->disk)->putFileAs($workerFolder, $file, $filename);

        // Generate thumbnail for images and videos
        $thumbnailPath = null;
        $metadata = [];

        if ($type === 'photo' || str_starts_with($mimeType, 'image/')) {
            $thumbnailPath = $this->generateImageThumbnail($file, $workerFolder);
            $metadata = $this->getImageMetadata($file);
        } elseif ($type === 'video') {
            $thumbnailPath = $this->generateVideoThumbnail($filePath, $workerFolder);
            $metadata = $this->getVideoMetadata($file);
        }

        // Get the next display order
        $nextOrder = WorkerPortfolioItem::where('worker_id', $worker->id)
            ->max('display_order') + 1;

        // Create the portfolio item
        return WorkerPortfolioItem::create([
            'worker_id' => $worker->id,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'file_path' => $filePath,
            'thumbnail_path' => $thumbnailPath,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'display_order' => $nextOrder,
            'is_featured' => false,
            'is_visible' => true,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Update a portfolio item.
     *
     * @param WorkerPortfolioItem $item
     * @param array $data
     * @return WorkerPortfolioItem
     */
    public function updateItem(WorkerPortfolioItem $item, array $data): WorkerPortfolioItem
    {
        $allowedFields = ['title', 'description', 'is_visible'];

        $item->update(array_intersect_key($data, array_flip($allowedFields)));

        return $item->fresh();
    }

    /**
     * Delete a portfolio item.
     *
     * @param WorkerPortfolioItem $item
     * @return bool
     */
    public function deleteItem(WorkerPortfolioItem $item): bool
    {
        // Delete files from storage
        if ($item->file_path) {
            Storage::disk($this->disk)->delete($item->file_path);
        }

        if ($item->thumbnail_path) {
            Storage::disk($this->disk)->delete($item->thumbnail_path);
        }

        return $item->delete();
    }

    /**
     * Reorder portfolio items.
     *
     * @param User $worker
     * @param array $itemIds Array of item IDs in desired order
     * @return void
     */
    public function reorderItems(User $worker, array $itemIds): void
    {
        DB::transaction(function () use ($worker, $itemIds) {
            foreach ($itemIds as $order => $itemId) {
                WorkerPortfolioItem::where('id', $itemId)
                    ->where('worker_id', $worker->id)
                    ->update(['display_order' => $order]);
            }
        });
    }

    /**
     * Set a portfolio item as featured.
     *
     * @param User $worker
     * @param WorkerPortfolioItem $item
     * @return WorkerPortfolioItem
     */
    public function setFeaturedItem(User $worker, WorkerPortfolioItem $item): WorkerPortfolioItem
    {
        DB::transaction(function () use ($worker, $item) {
            // Remove featured status from all other items
            WorkerPortfolioItem::where('worker_id', $worker->id)
                ->where('id', '!=', $item->id)
                ->update(['is_featured' => false]);

            // Set this item as featured
            $item->update(['is_featured' => true]);
        });

        return $item->fresh();
    }

    /**
     * Remove featured status from an item.
     *
     * @param WorkerPortfolioItem $item
     * @return WorkerPortfolioItem
     */
    public function removeFeatured(WorkerPortfolioItem $item): WorkerPortfolioItem
    {
        $item->update(['is_featured' => false]);
        return $item->fresh();
    }

    /**
     * Get worker's portfolio items.
     *
     * @param User $worker
     * @param bool $visibleOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPortfolioItems(User $worker, bool $visibleOnly = false)
    {
        $query = WorkerPortfolioItem::where('worker_id', $worker->id)
            ->ordered();

        if ($visibleOnly) {
            $query->visible();
        }

        return $query->get();
    }

    /**
     * Get featured portfolio item for a worker.
     *
     * @param User $worker
     * @return WorkerPortfolioItem|null
     */
    public function getFeaturedItem(User $worker): ?WorkerPortfolioItem
    {
        return WorkerPortfolioItem::where('worker_id', $worker->id)
            ->featured()
            ->visible()
            ->first();
    }

    /**
     * Generate public profile data.
     *
     * @param User $worker
     * @return array
     */
    public function generatePublicProfile(User $worker): array
    {
        $profile = $worker->workerProfile;

        if (!$profile || !$profile->public_profile_enabled) {
            return ['enabled' => false];
        }

        // Get visible portfolio items
        $portfolioItems = $this->getPortfolioItems($worker, true);

        // Get active featured status
        $featuredStatus = WorkerFeaturedStatus::where('worker_id', $worker->id)
            ->active()
            ->first();

        // Get public endorsements
        $endorsements = $worker->endorsementsReceived()
            ->where('is_public', true)
            ->with(['business:id,name', 'skill:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Get skills
        $skills = $profile->skills()
            ->withPivot('proficiency_level', 'verified')
            ->get()
            ->map(function ($skill) {
                return [
                    'id' => $skill->id,
                    'name' => $skill->name,
                    'proficiency_level' => $skill->pivot->proficiency_level,
                    'verified' => $skill->pivot->verified,
                ];
            });

        // Get certifications (non-expired)
        $certifications = $profile->certifications()
            ->whereNull('expiry_date')
            ->orWhere('expiry_date', '>', now())
            ->withPivot('verified', 'expiry_date')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'name' => $cert->name,
                    'verified' => $cert->pivot->verified,
                    'expires_at' => $cert->pivot->expiry_date,
                ];
            });

        // Calculate public rating info
        $ratingsReceived = $worker->ratingsReceived()
            ->where('is_public', true)
            ->get();

        return [
            'enabled' => true,
            'slug' => $profile->public_profile_slug,
            'url' => route('profile.public', ['username' => $profile->public_profile_slug]),
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->name,
                'username' => $worker->username,
                'avatar' => $profile->profile_photo_url,
                'bio' => $profile->bio,
                'location' => $this->formatLocation($profile),
                'years_experience' => $profile->years_experience,
                'total_shifts_completed' => $profile->total_shifts_completed,
                'rating_average' => $profile->rating_average,
                'rating_count' => $ratingsReceived->count(),
                'reliability_score' => $profile->reliability_score,
                'identity_verified' => $profile->identity_verified,
                'background_check_approved' => $profile->background_check_status === 'approved',
            ],
            'portfolio' => $portfolioItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'title' => $item->title,
                    'description' => $item->description,
                    'file_url' => $item->file_url,
                    'thumbnail_url' => $item->thumbnail_url,
                    'is_featured' => $item->is_featured,
                ];
            }),
            'featured_item' => $this->getFeaturedItem($worker)?->only(['id', 'type', 'title', 'file_url', 'thumbnail_url']),
            'skills' => $skills,
            'certifications' => $certifications,
            'endorsements' => $endorsements->map(function ($endorsement) {
                return [
                    'id' => $endorsement->id,
                    'type' => $endorsement->endorsement_type,
                    'text' => $endorsement->endorsement_text,
                    'business_name' => $endorsement->business->name ?? 'Anonymous',
                    'skill_name' => $endorsement->skill->name ?? null,
                    'created_at' => $endorsement->created_at->format('M Y'),
                ];
            }),
            'featured_status' => $featuredStatus ? [
                'tier' => $featuredStatus->tier,
                'tier_name' => $featuredStatus->tier_config['name'],
                'badge_color' => $featuredStatus->badge_color,
                'days_remaining' => $featuredStatus->days_remaining,
            ] : null,
            'meta' => [
                'title' => $worker->name . ' - Professional Profile | OvertimeStaff',
                'description' => $this->generateMetaDescription($worker, $profile),
                'keywords' => $this->generateMetaKeywords($skills, $profile),
            ],
        ];
    }

    /**
     * Enable public profile for a worker.
     *
     * @param User $worker
     * @return string The public profile slug
     */
    public function enablePublicProfile(User $worker): string
    {
        $profile = $worker->workerProfile;

        if (!$profile->public_profile_slug) {
            $profile->public_profile_slug = $this->generateUniqueSlug($worker);
        }

        $profile->public_profile_enabled = true;
        $profile->public_profile_enabled_at = now();
        $profile->save();

        return $profile->public_profile_slug;
    }

    /**
     * Disable public profile.
     *
     * @param User $worker
     * @return void
     */
    public function disablePublicProfile(User $worker): void
    {
        $worker->workerProfile->update([
            'public_profile_enabled' => false,
        ]);
    }

    /**
     * Record a profile view.
     *
     * @param User $worker
     * @param User|null $viewer
     * @param string $source
     * @return WorkerProfileView
     */
    public function recordProfileView(User $worker, ?User $viewer, string $source = 'other'): WorkerProfileView
    {
        $viewerType = $viewer ? ($viewer->user_type ?? 'guest') : 'guest';

        return WorkerProfileView::recordView(
            $worker->id,
            $viewer?->id,
            $viewerType,
            $source,
            request()->ip(),
            request()->userAgent(),
            request()->header('referer')
        );
    }

    /**
     * Get profile view analytics for a worker.
     *
     * @param User $worker
     * @param int $days
     * @return array
     */
    public function getProfileAnalytics(User $worker, int $days = 30): array
    {
        return WorkerProfileView::getStatsForWorker($worker->id, $days);
    }

    /**
     * Purchase featured status for a worker.
     *
     * @param User $worker
     * @param string $tier
     * @param string|null $paymentReference
     * @return WorkerFeaturedStatus
     */
    public function purchaseFeaturedStatus(User $worker, string $tier, ?string $paymentReference = null): WorkerFeaturedStatus
    {
        // Check if worker has an active featured status
        $existingActive = WorkerFeaturedStatus::where('worker_id', $worker->id)
            ->active()
            ->first();

        if ($existingActive) {
            throw new \Exception('Worker already has an active featured status');
        }

        return WorkerFeaturedStatus::createForWorker($worker->id, $tier, $paymentReference);
    }

    /**
     * Get active featured status for a worker.
     *
     * @param User $worker
     * @return WorkerFeaturedStatus|null
     */
    public function getActiveFeaturedStatus(User $worker): ?WorkerFeaturedStatus
    {
        return WorkerFeaturedStatus::where('worker_id', $worker->id)
            ->active()
            ->first();
    }

    /**
     * Calculate featured score boost for search results.
     *
     * @param User $worker
     * @return float
     */
    public function getFeaturedSearchBoost(User $worker): float
    {
        $featuredStatus = $this->getActiveFeaturedStatus($worker);

        return $featuredStatus ? $featuredStatus->search_boost : 1.0;
    }

    /**
     * Generate image thumbnail.
     *
     * @param UploadedFile $file
     * @param string $folder
     * @return string|null
     */
    protected function generateImageThumbnail(UploadedFile $file, string $folder): ?string
    {
        try {
            $thumbnailFilename = 'thumb_' . Str::uuid() . '.jpg';
            $thumbnailPath = $folder . '/' . $thumbnailFilename;

            $image = Image::make($file);
            $image->fit($this->thumbnailWidth, $this->thumbnailHeight);
            $image->encode('jpg', 85);

            Storage::disk($this->disk)->put($thumbnailPath, (string) $image);

            return $thumbnailPath;
        } catch (\Exception $e) {
            // Log error but don't fail the upload
            \Log::warning('Failed to generate image thumbnail: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate video thumbnail (placeholder - requires FFmpeg).
     *
     * @param string $videoPath
     * @param string $folder
     * @return string|null
     */
    protected function generateVideoThumbnail(string $videoPath, string $folder): ?string
    {
        // Video thumbnail generation requires FFmpeg
        // For now, return null and use placeholder
        // TODO: Implement with Laravel FFmpeg package
        return null;
    }

    /**
     * Get image metadata.
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function getImageMetadata(UploadedFile $file): array
    {
        try {
            $image = Image::make($file);
            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get video metadata (placeholder).
     *
     * @param UploadedFile $file
     * @return array
     */
    protected function getVideoMetadata(UploadedFile $file): array
    {
        // TODO: Implement with FFmpeg for duration, dimensions, etc.
        return [];
    }

    /**
     * Generate a unique public profile slug.
     *
     * @param User $worker
     * @return string
     */
    protected function generateUniqueSlug(User $worker): string
    {
        $baseSlug = Str::slug($worker->username ?: $worker->name);

        if (empty($baseSlug)) {
            $baseSlug = 'worker';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (\App\Models\WorkerProfile::where('public_profile_slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Format location for display.
     *
     * @param \App\Models\WorkerProfile $profile
     * @return string|null
     */
    protected function formatLocation($profile): ?string
    {
        $parts = array_filter([
            $profile->city ?? $profile->location_city,
            $profile->state ?? $profile->location_state,
            $profile->country ?? $profile->location_country,
        ]);

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    /**
     * Generate meta description for SEO.
     *
     * @param User $worker
     * @param \App\Models\WorkerProfile $profile
     * @return string
     */
    protected function generateMetaDescription(User $worker, $profile): string
    {
        $desc = $worker->name;

        if ($profile->years_experience > 0) {
            $desc .= ' - ' . $profile->years_experience . ' years experience';
        }

        if ($profile->total_shifts_completed > 0) {
            $desc .= ', ' . $profile->total_shifts_completed . ' shifts completed';
        }

        if ($profile->rating_average > 0) {
            $desc .= ', ' . number_format($profile->rating_average, 1) . ' star rating';
        }

        $desc .= '. Available for temporary staffing and shift work on OvertimeStaff.';

        return Str::limit($desc, 160);
    }

    /**
     * Generate meta keywords for SEO.
     *
     * @param \Illuminate\Support\Collection $skills
     * @param \App\Models\WorkerProfile $profile
     * @return string
     */
    protected function generateMetaKeywords($skills, $profile): string
    {
        $keywords = ['temporary worker', 'shift worker', 'staffing'];

        foreach ($skills as $skill) {
            $keywords[] = $skill['name'];
        }

        if (!empty($profile->industries)) {
            $keywords = array_merge($keywords, (array) $profile->industries);
        }

        $location = $this->formatLocation($profile);
        if ($location) {
            $keywords[] = $location;
        }

        return implode(', ', array_unique($keywords));
    }

    /**
     * Format file size for display.
     *
     * @param int $bytes
     * @return string
     */
    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
