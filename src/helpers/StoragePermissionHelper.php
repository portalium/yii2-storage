<?php

namespace portalium\storage\helpers;

use Yii;
use portalium\storage\models\Storage;
use portalium\storage\models\StorageDirectory;
use portalium\storage\models\StorageShare;
use portalium\workspace\models\WorkspaceUser;

/**
 * Helper class for storage permission checks
 * Consolidates complex permission logic for storage operations
 */
class StoragePermissionHelper
{
    /**
     * Check if user can manage a specific share (update permission, revoke, etc.)
     * 
     * @param int $id_user User ID to check
     * @param StorageShare $share The share to check permissions for
     * @param string $globalPermission Global RBAC permission to check (optional)
     * @return bool
     */
    public static function canManageShare($id_user, $share, $globalPermission = null)
    {
        // Check global permission if provided
        if ($globalPermission && Yii::$app->user->can($globalPermission)) {
            return true;
        }
        
        // Check ownership or MANAGE permission through share
        if ($share->id_storage) {
            return self::canManageFileShare($id_user, $share);
        } elseif ($share->id_directory) {
            return self::canManageDirectoryShare($id_user, $share);
        } elseif ($share->id_user_owner) {
            return self::canManageFullStorageShare($id_user, $share);
        }
        
        return false;
    }
    
    /**
     * Check if user can manage a file share
     * 
     * @param int $id_user User ID
     * @param StorageShare $share Share record
     * @return bool
     */
    private static function canManageFileShare($id_user, $share)
    {
        $storage = Storage::findOne($share->id_storage);
        if (!$storage) {
            return false;
        }
        
        // Check if user is the file owner
        $isOwner = $storage->id_user == $id_user;
        
        // Check if user has EXACT MANAGE permission through share
        $hasManagePermission = self::hasExactManagePermissionForFile($id_user, $storage);
        
        return $isOwner || $hasManagePermission;
    }
    
    /**
     * Check if user can manage a directory share
     * 
     * @param int $id_user User ID
     * @param StorageShare $share Share record
     * @return bool
     */
    private static function canManageDirectoryShare($id_user, $share)
    {
        $directory = StorageDirectory::findOne($share->id_directory);
        if (!$directory) {
            return false;
        }
        
        // Check if user is the directory owner
        $isOwner = $directory->id_user == $id_user;
        
        // Check if user has EXACT MANAGE permission through share
        $hasManagePermission = self::hasExactManagePermissionForDirectory($id_user, $directory);
        
        return $isOwner || $hasManagePermission;
    }
    
    /**
     * Check if user can manage a full storage share
     * 
     * @param int $id_user User ID
     * @param StorageShare $share Share record
     * @return bool
     */
    private static function canManageFullStorageShare($id_user, $share)
    {
        // Only the owner of the full storage share can manage it
        return $share->id_user_owner == $id_user;
    }
    
    /**
     * Check if user has EXACT MANAGE permission for a file (not hierarchical)
     * Only checks direct file shares or directory shares, NOT full storage shares
     * 
     * @param int $id_user User ID
     * @param Storage $storage File model
     * @return bool
     */
    private static function hasExactManagePermissionForFile($id_user, $storage)
    {
        $userWorkspaceIds = self::getUserWorkspaceIds($id_user);
        
        return StorageShare::find()
            ->where(['is_active' => 1])
            ->andWhere(['OR',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')]
            ])
            ->andWhere(['OR',
                // Direct file share
                ['id_storage' => $storage->id_storage],
                // Directory share (if file is in a directory)
                ['AND',
                    ['IS NOT', 'id_directory', null],
                    ['id_directory' => $storage->id_directory]
                ]
            ])
            ->andWhere(['OR',
                ['shared_with_type' => StorageShare::TYPE_USER, 'id_shared_with' => $id_user],
                ['shared_with_type' => StorageShare::TYPE_WORKSPACE, 'id_shared_with' => $userWorkspaceIds]
            ])
            ->andWhere(['permission_level' => StorageShare::PERMISSION_MANAGE])
            ->exists();
    }
    
    /**
     * Check if user has EXACT MANAGE permission for a directory (not hierarchical)
     * Only checks direct directory shares, NOT full storage shares
     * 
     * @param int $id_user User ID
     * @param StorageDirectory $directory Directory model
     * @return bool
     */
    private static function hasExactManagePermissionForDirectory($id_user, $directory)
    {
        $userWorkspaceIds = self::getUserWorkspaceIds($id_user);
        $directoryIds = StorageShare::getParentDirectoryIds($directory->id_directory);
        
        return StorageShare::find()
            ->where(['is_active' => 1])
            ->andWhere(['OR',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')]
            ])
            ->andWhere(['id_directory' => $directoryIds])
            ->andWhere(['OR',
                ['shared_with_type' => StorageShare::TYPE_USER, 'id_shared_with' => $id_user],
                ['shared_with_type' => StorageShare::TYPE_WORKSPACE, 'id_shared_with' => $userWorkspaceIds]
            ])
            ->andWhere(['permission_level' => StorageShare::PERMISSION_MANAGE])
            ->exists();
    }
    
    /**
     * Get user's workspace IDs
     * 
     * @param int $id_user User ID
     * @return array
     */
    private static function getUserWorkspaceIds($id_user)
    {
        return WorkspaceUser::find()
            ->select('id_workspace')
            ->where(['id_user' => $id_user])
            ->column();
    }
    
    /**
     * Check if user can create a share for a file
     * 
     * @param int $id_user User ID
     * @param Storage $storage File model
     * @param string $globalPermissionOwn Global RBAC Own permission
     * @param string $workspacePermission Workspace permission
     * @return bool
     */
    public static function canShareFile($id_user, $storage, $globalPermissionOwn = null, $workspacePermission = null)
    {
        // Check global permission with ownership
        if ($globalPermissionOwn && Yii::$app->user->can($globalPermissionOwn, ['model' => $storage])) {
            return true;
        }
        
        // Check workspace permission
        if ($workspacePermission && Yii::$app->workspace->can('storage', $workspacePermission, ['model' => $storage])) {
            return true;
        }
        
        // Check if user has MANAGE permission through share
        $hasManageSharePermission = StorageShare::hasAccess($id_user, $storage, null, StorageShare::PERMISSION_MANAGE);
        
        return $hasManageSharePermission;
    }
    
    /**
     * Check if user can create a share for a directory
     * 
     * @param int $id_user User ID
     * @param StorageDirectory $directory Directory model
     * @param string $globalPermissionOwn Global RBAC Own permission
     * @param string $workspacePermission Workspace permission
     * @return bool
     */
    public static function canShareDirectory($id_user, $directory, $globalPermissionOwn = null, $workspacePermission = null)
    {
        // Check global permission with ownership
        if ($globalPermissionOwn && Yii::$app->user->can($globalPermissionOwn, ['model' => $directory])) {
            return true;
        }
        
        // Check workspace permission
        if ($workspacePermission && Yii::$app->workspace->can('storage', $workspacePermission, ['model' => $directory])) {
            return true;
        }
        
        // Check if user has MANAGE permission through share
        $hasManageSharePermission = StorageShare::hasAccess($id_user, null, $directory, StorageShare::PERMISSION_MANAGE);
        
        return $hasManageSharePermission;
    }
    
    /**
     * Check if user can view shares for a file
     * 
     * @param int $id_user User ID
     * @param Storage $storage File model
     * @param string $globalPermission Global RBAC permission
     * @param string $globalPermissionOwn Global RBAC Own permission
     * @param string $workspacePermission Workspace permission
     * @return bool
     */
    public static function canViewFileShares($id_user, $storage, $globalPermission = null, $globalPermissionOwn = null, $workspacePermission = null)
    {
        // Check global permission
        if ($globalPermission && Yii::$app->user->can($globalPermission)) {
            return true;
        }
        
        // Check global permission with ownership
        if ($globalPermissionOwn && Yii::$app->user->can($globalPermissionOwn, ['model' => $storage])) {
            return true;
        }
        
        // Check workspace permission
        if ($workspacePermission && Yii::$app->workspace->can('storage', $workspacePermission, ['model' => $storage])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if user can view shares for a directory
     * 
     * @param int $id_user User ID
     * @param StorageDirectory $directory Directory model
     * @param string $globalPermission Global RBAC permission
     * @param string $globalPermissionOwn Global RBAC Own permission
     * @param string $workspacePermission Workspace permission
     * @return bool
     */
    public static function canViewDirectoryShares($id_user, $directory, $globalPermission = null, $globalPermissionOwn = null, $workspacePermission = null)
    {
        // Check global permission
        if ($globalPermission && Yii::$app->user->can($globalPermission)) {
            return true;
        }
        
        // Check global permission with ownership
        if ($globalPermissionOwn && Yii::$app->user->can($globalPermissionOwn, ['model' => $directory])) {
            return true;
        }
        
        // Check workspace permission
        if ($workspacePermission && Yii::$app->workspace->can('storage', $workspacePermission, ['model' => $directory])) {
            return true;
        }
        
        return false;
    }
}
