<?php

namespace App\Http\Controllers\API;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class UserController extends BaseController
{
    public function getUser() {
        $authUser = Auth::user();
        $user = User::findOrFail($authUser->id);
        $user->avatar = $this->getS3Url($user->avatar);
        return $this->sendResponse($user, 'User');
    }
    
    public function uploadAvatar(Request $request) {
        // Validate the image
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);
        
        if (!$request->hasFile('image')) {
            return $this->sendError('No image file provided.', [], 400);
        }
        
        try {
            // Get authenticated user
            $authUser = Auth::user();
            $user = User::findOrFail($authUser->id);
            
            // Get file extension
            $extension = $request->file('image')->getClientOriginalExtension();
            
            // Create unique image name
            $image_name = 'avatar_' . $authUser->id . '_' . time() . '.' . $extension;
            
            // Path in S3
            $path = 'images/' . $image_name;
            
            // Upload file to S3
            $uploaded = Storage::disk('s3')->put(
                $path, 
                file_get_contents($request->file('image')->getRealPath())
            );
            
            if (!$uploaded) {
                return $this->sendError('Failed to upload file to S3', [], 500);
            }
            
            // Delete old avatar if exists
            if ($user->avatar) {
                Storage::disk('s3')->delete($user->avatar);
            }
            
            // Update user record
            $user->avatar = $path;
            $user->save();
            
            // Generate URL
            $url = Storage::disk('s3')->url($path);
            
            // Prepare response
            $success['avatar'] = $url;
            
            return $this->sendResponse($success, 'User profile avatar uploaded successfully!');
        } catch (\Exception $e) {
            // Log the error with full details
            \Log::error('Avatar upload error: ' . $e->getMessage());
            return $this->sendError('Failed to upload avatar: ' . $e->getMessage(), [], 500);
        }
    }
    
    public function removeAvatar() {
        try {
            // Get authenticated user
            $authUser = Auth::user();
            $user = User::findOrFail($authUser->id);
            
            // Delete avatar from S3 if exists
            if ($user->avatar) {
                Storage::disk('s3')->delete($user->avatar);
            }
            
            // Update user record
            $user->avatar = null;
            $user->save();
            
            // Prepare response with null avatar
            $success['avatar'] = null;
            
            return $this->sendResponse($success, 'User profile avatar removed successfully!');
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Avatar removal error: ' . $e->getMessage());
            return $this->sendError('Failed to remove avatar: ' . $e->getMessage(), [], 500);
        }
    }
}