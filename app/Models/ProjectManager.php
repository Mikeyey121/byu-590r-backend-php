<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectManager extends Model
{
    protected $primaryKey = 'managerId';
    
    protected $fillable = [
        'managerName'
    ];

    public function project()
    {
        return $this->hasOne(Project::class, 'managerId', 'managerId');
    }
} 