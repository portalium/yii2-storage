<?php

namespace portalium\storage\helpers;

use yii\db\ActiveQuery;
use portalium\storage\models\StorageShare;

/**
 * Service class that provides reusable query-building methods for storage listings.
 *
 * Centralizes the share-aware filtering logic so that actionIndex, actionPickerModal,
 * actionSearch and any future listing action use exactly the same conditions.
 */
class StorageQueryService
{
    /**
     * Return the IDs of every workspace the given user belongs to.
     *
     * @param int $userId
     * @return int[]
     */
    public static function getUserWorkspaceIds($userId)
    {
        return \portalium\workspace\models\WorkspaceUser::find()
            ->select('id_workspace')
            ->where(['id_user' => $userId])
            ->column();
    }

    /**
     * Normalize a fileExtensions parameter that may arrive as a comma-separated
     * string, an array, or null into a clean array of extension strings.
     *
     * @param mixed $fileExtensions
     * @return string[]
     */
    public static function normalizeFileExtensions($fileExtensions)
    {
        if (is_string($fileExtensions) && !empty($fileExtensions)) {
            $fileExtensions = explode(',', $fileExtensions);
        }

        if (!is_array($fileExtensions)) {
            $fileExtensions = [];
        }

        return array_filter($fileExtensions, function ($ext) {
            return !empty(trim($ext));
        });
    }

    /**
     * Append a WHERE condition to $query that limits files to a set of extensions.
     *
     * @param ActiveQuery $query
     * @param string[] $fileExtensions  Already-normalized array of extensions
     * @return ActiveQuery  The same query (for chaining)
     */
    public static function applyFileExtensionFilter(ActiveQuery $query, array $fileExtensions)
    {
        if (empty($fileExtensions)) {
            return $query;
        }

        $orConditions = ['or'];
        foreach ($fileExtensions as $extension) {
            $cleanExtension = '.' . ltrim(trim($extension), '.');
            $orConditions[] = ['like', 'name', '%' . $cleanExtension, false];
        }

        if (count($orConditions) > 1) {
            $query->andWhere($orConditions);
        }

        return $query;
    }

    /**
     * Active share base query (is_active + not expired).
     *
     * @return ActiveQuery
     */
    private static function activeShareBase()
    {
        return StorageShare::find()
            ->where(['is_active' => 1])
            ->andWhere([
                'or',
                ['expires_at' => null],
                ['>', 'expires_at', date('Y-m-d H:i:s')],
            ]);
    }

    /**
     * IDs of storages shared directly with a user.
     */
    private static function storageIdsSharedWithUser($userId)
    {
        return self::activeShareBase()
            ->select('id_storage')
            ->andWhere(['shared_with_type' => StorageShare::TYPE_USER])
            ->andWhere(['id_shared_with' => $userId]);
    }

    /**
     * IDs of storages shared with any of the given workspaces.
     */
    private static function storageIdsSharedWithWorkspaces(array $workspaceIds)
    {
        return self::activeShareBase()
            ->select('id_storage')
            ->andWhere(['shared_with_type' => StorageShare::TYPE_WORKSPACE])
            ->andWhere(['in', 'id_shared_with', $workspaceIds]);
    }

    /**
     * IDs of directories shared directly with a user.
     */
    private static function directoryIdsSharedWithUser($userId)
    {
        return self::activeShareBase()
            ->select('id_directory')
            ->andWhere(['shared_with_type' => StorageShare::TYPE_USER])
            ->andWhere(['id_shared_with' => $userId]);
    }

    /**
     * IDs of directories shared with any of the given workspaces.
     */
    private static function directoryIdsSharedWithWorkspaces(array $workspaceIds)
    {
        return self::activeShareBase()
            ->select('id_directory')
            ->andWhere(['shared_with_type' => StorageShare::TYPE_WORKSPACE])
            ->andWhere(['in', 'id_shared_with', $workspaceIds]);
    }

    /**
     * Owner user IDs that granted full-storage access to a user.
     */
    private static function fullStorageOwnerIdsForUser($userId)
    {
        return self::activeShareBase()
            ->select('id_user_owner')
            ->andWhere(['shared_with_type' => StorageShare::TYPE_USER])
            ->andWhere(['id_shared_with' => $userId])
            ->andWhere(['id_storage' => null])
            ->andWhere(['id_directory' => null]);
    }

    /**
     * Owner user IDs that granted full-storage access to any of the given workspaces.
     */
    private static function fullStorageOwnerIdsForWorkspaces(array $workspaceIds)
    {
        return self::activeShareBase()
            ->select('id_user_owner')
            ->andWhere(['shared_with_type' => StorageShare::TYPE_WORKSPACE])
            ->andWhere(['in', 'id_shared_with', $workspaceIds])
            ->andWhere(['id_storage' => null])
            ->andWhere(['id_directory' => null]);
    }

    /**
     * Apply share-aware visibility conditions to a **file** query.
     *
     * The resulting WHERE covers:
     *  1. Own files
     *  2. Files shared directly with user
     *  3. Files shared with user's workspaces
     *  4. Files inside directories shared with user
     *  5. Files inside directories shared with user's workspaces
     *  6. Files from owners who granted full-storage access to user
     *  7. Files from owners who granted full-storage access to user's workspaces
     *
     * @param ActiveQuery $query          The file query to constrain
     * @param int         $userId         Current user id
     * @param int[]       $workspaceIds   Workspace IDs the user belongs to
     * @return ActiveQuery  The same query (for chaining)
     */
    public static function applyFileShareConditions(ActiveQuery $query, $userId, array $workspaceIds)
    {
        $query->andWhere([
            'or',
            ['{{%storage_storage}}.id_user' => $userId],
            ['in', '{{%storage_storage}}.id_storage', self::storageIdsSharedWithUser($userId)],
            ['in', '{{%storage_storage}}.id_storage', self::storageIdsSharedWithWorkspaces($workspaceIds)],
            ['in', '{{%storage_storage}}.id_directory', self::directoryIdsSharedWithUser($userId)],
            ['in', '{{%storage_storage}}.id_directory', self::directoryIdsSharedWithWorkspaces($workspaceIds)],
            ['in', '{{%storage_storage}}.id_user', self::fullStorageOwnerIdsForUser($userId)],
            ['in', '{{%storage_storage}}.id_user', self::fullStorageOwnerIdsForWorkspaces($workspaceIds)],
        ]);

        return $query;
    }

    /**
     * Apply share-aware visibility conditions to a **directory** query.
     *
     * The resulting WHERE covers:
     *  1. Own directories
     *  2. Directories shared directly with user
     *  3. Directories shared with user's workspaces
     *  4. Directories from owners who granted full-storage access to user
     *  5. Directories from owners who granted full-storage access to user's workspaces
     *
     * @param ActiveQuery $query          The directory query to constrain
     * @param int         $userId         Current user id
     * @param int[]       $workspaceIds   Workspace IDs the user belongs to
     * @return ActiveQuery  The same query (for chaining)
     */
    public static function applyDirectoryShareConditions(ActiveQuery $query, $userId, array $workspaceIds)
    {
        $query->andWhere([
            'or',
            ['{{%storage_storage_directory}}.id_user' => $userId],
            ['in', '{{%storage_storage_directory}}.id_directory', self::directoryIdsSharedWithUser($userId)],
            ['in', '{{%storage_storage_directory}}.id_directory', self::directoryIdsSharedWithWorkspaces($workspaceIds)],
            ['in', '{{%storage_storage_directory}}.id_user', self::fullStorageOwnerIdsForUser($userId)],
            ['in', '{{%storage_storage_directory}}.id_user', self::fullStorageOwnerIdsForWorkspaces($workspaceIds)],
        ]);

        return $query;
    }
}
