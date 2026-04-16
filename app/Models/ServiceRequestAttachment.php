<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ServiceRequestAttachment extends Model
{
    protected $fillable = [
        'service_request_id',
        'original_name',
        'stored_path',
        'mime_type',
        'size',
    ];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $attachment) {
            Storage::disk('local')->delete($attachment->stored_path);
        });
    }
}
