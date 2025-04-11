<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectManager;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\API\BaseController as BaseController;

class ProjectController extends BaseController
{
    public function index()
    {
        $projects = Project::with('projectManager')->get();
        return $this->sendResponse($projects, 'Projects retrieved successfully');
    }

    public function getProjectManagers()
    {
        $managers = ProjectManager::all();
        \Log::info('Project managers:', $managers->toArray());
        return $this->sendResponse($managers, 'Project managers retrieved successfully');
    }

    public function getGenres()
    {
        $genres = Genre::all();
        \Log::info('Genres:', $genres->toArray());
        return $this->sendResponse($genres, 'Genres retrieved successfully');
    }

    public function store(Request $request)
    {
        try {
            \Log::info('Project creation request received', $request->all());
            
            // Check all required fields manually
            $requiredFields = ['projectName', 'projectStartDate', 'projectBudget', 'managerId'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($request->$field)) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                \Log::error('Missing required fields', ['fields' => $missingFields]);
                return $this->sendError('Missing required fields: ' . implode(', ', $missingFields), [], 400);
            }
            
            if (!$request->hasFile('projectFile')) {
                \Log::error('No file was uploaded');
                return $this->sendError('No file was uploaded', [], 400);
            }
            
            // Match avatar upload exactly
            $file = $request->file('projectFile');
            
            \Log::info('File received', [
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getMimeType(),
                'size' => $file->getSize()
            ]);
            
            // Get file extension
            $extension = $file->getClientOriginalExtension();
            
            // Create unique image name - using the same pattern as avatars but with 'project_' prefix
            $image_name = 'project_' . $request->managerId . '_' . time() . '.' . $extension;
            
            // Path in S3 - use the same 'images/' folder as avatars
            $path = 'images/' . $image_name;
            
            // Upload file to S3 - exactly as in avatar upload
            $uploaded = Storage::disk('s3')->put(
                $path, 
                file_get_contents($file->getRealPath())
            );
            
            if (!$uploaded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload file to S3'
                ], 500);
            }
            
            // Generate URL
            $url = Storage::disk('s3')->url($path);
            
            \Log::info('File uploaded to S3', [
                'path' => $path,
                'url' => $url
            ]);
            
            $project = Project::create([
                'projectName' => $request->projectName,
                'projectStartDate' => $request->projectStartDate,
                'projectBudget' => $request->projectBudget,
                'managerId' => $request->managerId,
                'genreId' => $request->genreId,
                'projectFile' => $url
            ]);
            
            \Log::info('Project created successfully', [
                'projectId' => $project->projectId
            ]);
            
            return $this->sendResponse($project, 'Project created successfully');
        } catch (\Exception $e) {
            \Log::error('Error creating project: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('An error occurred while creating the project: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Update project
     */
    public function update(Request $request, $id)
    {
        try {
            \Log::info('Project update request received', ['id' => $id, 'data' => $request->except('projectFile')]);
            
            // Validate incoming data
            $validated = $request->validate([
                'projectName' => 'sometimes|required|string|max:255',
                'projectStartDate' => 'sometimes|required|date',
                'projectBudget' => 'sometimes|required|numeric',
                'managerId' => 'sometimes|required|integer|exists:project_managers,managerId',
                'projectFile' => 'sometimes|file|image|max:2048',
            ]);
            
            $project = Project::findOrFail($id);
            
            // Update text fields if they're present in the request
            if ($request->has('projectName')) {
                $project->projectName = $request->projectName;
            }
            
            if ($request->has('projectStartDate')) {
                $project->projectStartDate = $request->projectStartDate;
            }
            
            if ($request->has('projectBudget')) {
                $project->projectBudget = $request->projectBudget;
            }
            
            if ($request->has('managerId')) {
                $project->managerId = $request->managerId;
            }

            if ($request->has('genreId')) {
                $project->genreId = $request->genreId;
            }
            
            // Handle file upload if a new file is provided
            if ($request->hasFile('projectFile')) {
                $file = $request->file('projectFile');
                
                \Log::info('New project file received', [
                    'originalName' => $file->getClientOriginalName(),
                    'mimeType' => $file->getMimeType(),
                    'size' => $file->getSize()
                ]);
                
                // Get file extension
                $extension = $file->getClientOriginalExtension();
                
                // Create unique image name
                $image_name = 'project_' . $request->managerId . '_' . time() . '.' . $extension;
                
                // Path in S3
                $path = 'images/' . $image_name;
                
                // Upload file to S3
                $uploaded = Storage::disk('s3')->put(
                    $path, 
                    file_get_contents($file->getRealPath())
                );
                
                if (!$uploaded) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to upload file to S3'
                    ], 500);
                }
                
                // Delete old file if exists
                $this->deleteProjectFileFromS3($project);
                
                // Generate URL
                $url = Storage::disk('s3')->url($path);
                
                \Log::info('New file uploaded to S3', [
                    'path' => $path,
                    'url' => $url
                ]);
                
                $project->projectFile = $url;
            }
            
            $project->save();
            
            return $this->sendResponse($project->load('projectManager'), 'Project updated successfully');
            
        } catch (\Exception $e) {
            \Log::error('Error updating project: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('An error occurred while updating the project: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Delete a project
     */
    public function destroy($id)
    {
        try {
            $project = Project::findOrFail($id);
            
            // Delete file from S3
            $this->deleteProjectFileFromS3($project);
            
            // Delete the project
            $project->delete();
            
            return $this->sendResponse(null, 'Project deleted successfully');
            
        } catch (\Exception $e) {
            \Log::error('Error deleting project: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->sendError('An error occurred while deleting the project: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Remove project image
     */
    public function removeProjectImage($id)
    {
        try {
            $project = Project::findOrFail($id);
            
            // Delete file from S3
            $this->deleteProjectFileFromS3($project);
            
            // Clear the project file field
            $project->projectFile = null;
            $project->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Project image removed successfully',
                'data' => $project->load('projectManager')
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error removing project image: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing the project image: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper method to delete project file from S3
     */
    private function deleteProjectFileFromS3($project)
    {
        if ($project->projectFile) {
            // Extract the path from the URL
            $fullUrl = $project->projectFile;
            $path = parse_url($fullUrl, PHP_URL_PATH);
            
            // Remove the initial slash and any S3 domain prefix
            $path = ltrim($path, '/');
            $pathParts = explode('/', $path);
            
            // Get only the relevant part after the bucket name if present
            if (count($pathParts) > 1 && $pathParts[0] !== 'images') {
                array_shift($pathParts);
                $path = implode('/', $pathParts);
            }
            
            \Log::info('Attempting to delete project file from S3', [
                'fullUrl' => $fullUrl,
                'path' => $path
            ]);
            
            Storage::disk('s3')->delete($path);
            
            \Log::info('Project file deleted from S3');
        }
    }
} 