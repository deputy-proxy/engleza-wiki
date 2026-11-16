<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['resource_id', 'filename', 'path', 'mime_type', 'size', 'download_count'];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
