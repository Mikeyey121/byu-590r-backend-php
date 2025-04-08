<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $primaryKey = 'projectId';
    
    protected $fillable = [
        'projectName',
        'projectStartDate',
        'projectBudget',
        'projectFile',
        'managerId'
    ];

    public function projectManager()
    {
        return $this->belongsTo(ProjectManager::class, 'managerId', 'managerId');
    }
} 