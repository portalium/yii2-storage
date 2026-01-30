<?php

namespace portalium\storage\helpers;

use Yii;
use portalium\storage\models\StorageShare;

/**
 * Helper class for storage share operations
 */
class StorageShareHelper
{
    /**
     * Share a user's full storage with another user and workspace with MANAGE permission
     * 
     * This is typically used when creating temporary/system users (like device users)
     * and you want to grant the creator full access to that user's storage.
     * 
     * @param int $storageOwnerId The ID of the user whose storage will be shared (e.g., device user)
     * @param int $sharedWithUserId The ID of the user to share with (e.g., creator user)
     * @param int|null $sharedWithWorkspaceId The ID of the workspace to share with (optional)
     * @return array Returns ['success' => bool, 'errors' => array] with operation results
     */
    public static function shareFullStorageWithCreator($storageOwnerId, $sharedWithUserId, $sharedWithWorkspaceId = null)
    {
        $errors = [];
        
        // Share with user
        if ($sharedWithUserId) {
            $userShare = new StorageShare();
            $userShare->id_user_owner = $storageOwnerId;
            $userShare->shared_with_type = StorageShare::TYPE_USER;
            $userShare->id_shared_with = $sharedWithUserId;
            $userShare->permission_level = StorageShare::PERMISSION_MANAGE;
            $userShare->is_active = 1;
            
            if (!$userShare->save()) {
                $errors['user_share'] = $userShare->errors;
                Yii::warning('Failed to share storage with user: ' . json_encode($userShare->errors), 'storage');
            }
        }
        
        // Share with workspace
        if ($sharedWithWorkspaceId) {
            $workspaceShare = new StorageShare();
            $workspaceShare->id_user_owner = $storageOwnerId;
            $workspaceShare->shared_with_type = StorageShare::TYPE_WORKSPACE;
            $workspaceShare->id_shared_with = $sharedWithWorkspaceId;
            $workspaceShare->permission_level = StorageShare::PERMISSION_MANAGE;
            $workspaceShare->is_active = 1;
            
            if (!$workspaceShare->save()) {
                $errors['workspace_share'] = $workspaceShare->errors;
                Yii::warning('Failed to share storage with workspace: ' . json_encode($workspaceShare->errors), 'storage');
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Share a user's full storage with current logged-in user and their workspace
     * 
     * Convenience method that uses Yii::$app->user->id and Yii::$app->workspace->id
     * 
     * @param int $storageOwnerId The ID of the user whose storage will be shared
     * @return array Returns ['success' => bool, 'errors' => array] with operation results
     */
    public static function shareFullStorageWithCurrentUser($storageOwnerId)
    {
        $currentUserId = Yii::$app->user->id;
        $currentWorkspaceId = Yii::$app->workspace->id ?? null;
        
        return self::shareFullStorageWithCreator($storageOwnerId, $currentUserId, $currentWorkspaceId);
    }
}
