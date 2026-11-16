<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ListeningResource extends Model
{
    protected $fillable = ['resource_id', 'instructions', 'media_path', 'media_type', 'original_filename', 'mime_type', 'file_size', 'duration_seconds'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function mediaUrl(): ?string
    {
        return $this->media_path ? Storage::disk('public')->url($this->media_path) : null;
    }
}
