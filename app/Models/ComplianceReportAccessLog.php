<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceReportAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'compliance_report_id',
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
        'accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'accessed_at' => 'datetime',
    ];

    /**
     * Get the compliance report.
     */
    public function complianceReport()
    {
        return $this->belongsTo(ComplianceReport::class);
    }

    /**
     * Get the user who accessed the report.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
