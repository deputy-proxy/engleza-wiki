<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property-read \App\Models\Resource|null $resource */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['resource_id', 'filename', 'path', 'mime_type', 'size', 'download_count', 'source_url', 'license', 'attribution', 'permission_reference'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
