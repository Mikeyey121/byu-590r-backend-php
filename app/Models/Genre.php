<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Genre extends Model
{
    protected $primaryKey = 'genreId';
    
    protected $fillable = [
        'genreName'
    ];

    public function project()
    {
        return $this->hasOne(Project::class, 'genreId', 'genreId');
    }
} 