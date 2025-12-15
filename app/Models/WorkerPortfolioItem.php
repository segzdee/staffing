<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Worker Portfolio Item Model
 * WKR-010: Worker Portfolio & Showcase Features
 *
 * @property int $id
 * @property int $worker_id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property string $file_path
 * @property string|null $thumbnail_path
 * @property string|null $original_filename
 * @property string|null $mime_type
 * @property int $file_size
 * @property int $display_order
 * @property bool $is_featured
 * @property bool $is_visible
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $worker
 */
class WorkerPortfolioItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Maximum portfolio items allowed per worker.
     */
    public const MAX_ITEMS_PER_WORKER = 10;

    /**
     * Allowed portfolio item types.
     */
    public const TYPES = ['photo', 'video', 'document', 'certification'];

    /**
     * File size limits in bytes.
     */
    public const MAX_IMAGE_SIZE = 10 * 1024 * 1024;    // 10MB
    public const MAX_VIDEO_SIZE = 50 * 1024 * 1024;    // 50MB
    public const MAX_DOCUMENT_SIZE = 5 * 1024 * 1024;  // 5MB

    /**
     * Allowed MIME types by category.
     */
    public const ALLOWED_MIME_TYPES = [
        'photo' => ['image/jpeg', 'image/png', 'image/webp'],
        'video' => ['video/mp4', 'video/quicktime', 'video/webm'],
        'document' => ['application/pdf'],
        'certification' => ['application/pdf', 'image/jpeg', 'image/png'],
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'worker_id',
        'type',
        'title',
        'description',
        'file_path',
        'thumbnail_path',
        'original_filename',
        'mime_type',
        'file_size',
        'display_order',
        'is_featured',
        'is_visible',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the worker that owns this portfolio item.
     */
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }

    /**
     * Scope to get visible items only.
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    /**
     * Scope to get featured items.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the full URL for the file.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    /**
     * Get the full URL for the thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            // Return placeholder based on type
            return $this->getPlaceholderThumbnail();
        }

        return Storage::url($this->thumbnail_path);
    }

    /**
     * Get placeholder thumbnail URL based on type.
     */
    protected function getPlaceholderThumbnail(): string
    {
        return match ($this->type) {
            'video' => '/images/placeholders/video-thumbnail.png',
            'document' => '/images/placeholders/document-thumbnail.png',
            'certification' => '/images/placeholders/certification-thumbnail.png',
            default => '/images/placeholders/image-thumbnail.png',
        };
    }

    /**
     * Get human-readable file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Check if this is an image type.
     */
    public function isImage(): bool
    {
        return $this->type === 'photo' || str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Check if this is a video type.
     */
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    /**
     * Check if this is a document type.
     */
    public function isDocument(): bool
    {
        return in_array($this->type, ['document', 'certification']);
    }

    /**
     * Get video duration from metadata (if available).
     */
    public function getVideoDurationAttribute(): ?int
    {
        return $this->metadata['duration'] ?? null;
    }

    /**
     * Get formatted video duration.
     */
    public function getFormattedDurationAttribute(): ?string
    {
        $duration = $this->video_duration;

        if (!$duration) {
            return null;
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get image dimensions from metadata.
     */
    public function getDimensionsAttribute(): ?array
    {
        if (isset($this->metadata['width']) && isset($this->metadata['height'])) {
            return [
                'width' => $this->metadata['width'],
                'height' => $this->metadata['height'],
            ];
        }

        return null;
    }

    /**
     * Validate if the given MIME type is allowed for the item type.
     */
    public static function isAllowedMimeType(string $type, string $mimeType): bool
    {
        $allowedTypes = self::ALLOWED_MIME_TYPES[$type] ?? [];
        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Get the max file size for a given type.
     */
    public static function getMaxFileSize(string $type): int
    {
        return match ($type) {
            'video' => self::MAX_VIDEO_SIZE,
            'document', 'certification' => self::MAX_DOCUMENT_SIZE,
            default => self::MAX_IMAGE_SIZE,
        };
    }
}
