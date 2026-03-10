<?php

use portalium\db\Migration;
use portalium\storage\Module;

/**
 * Merge storage_directory into storage table.
 *
 * Steps:
 * 1. Add `type` column to storage table (default 'file').
 * 2. Drop the old FK from storage.id_directory → storage_directory.id_directory.
 * 3. Migrate directory records into storage table (as type = 'directory').
 * 4. Update storage records' id_directory values from old directory IDs to new storage IDs.
 * 5. Add new self-referencing FK from storage.id_directory → storage.id_storage.
 *
 * NOTE: The old storage_storage_directory table is NOT dropped — the user will drop it manually.
 */
class m260310_000000_merge_directory_into_storage extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $storageTable    = '{{%' . Module::$tablePrefix . 'storage}}';
        $directoryTable  = '{{%' . Module::$tablePrefix . 'storage_directory}}';
        $shareTable      = '{{%' . Module::$tablePrefix . 'storage_share}}';

        // ───────────────────────────────────────────────────────
        // 1. Add `type` column to storage table
        // ───────────────────────────────────────────────────────
        $this->addColumn($storageTable, 'type', $this->string(20)->notNull()->defaultValue('file')->after('id_storage'));

        // Mark all existing records as 'file'
        $this->update($storageTable, ['type' => 'file']);

        // ───────────────────────────────────────────────────────
        // 2. Drop old FK: storage.id_directory → storage_directory.id_directory
        // ───────────────────────────────────────────────────────
        $this->dropForeignKey('{{%fk-' . Module::$tablePrefix . 'storage-id_directory}}', $storageTable);

        // ───────────────────────────────────────────────────────
        // 3. Drop old FK: storage_share.id_directory → storage_directory.id_directory
        // ───────────────────────────────────────────────────────
        try {
            $this->dropForeignKey('fk_storage_share_directory', $shareTable);
        } catch (\Exception $e) {
            // FK may not exist
        }

        // ───────────────────────────────────────────────────────
        // 4. Transfer directory records into storage table
        //    We build a mapping: old id_directory → new id_storage
        // ───────────────────────────────────────────────────────
        // First pass: insert directories that have no parent (root folders)
        // Then iterate until all directories are migrated (handle tree depth)

        $allDirectories = $this->db->createCommand("SELECT * FROM {$directoryTable} ORDER BY id_parent ASC, id_directory ASC")->queryAll();

        // Build parent → children map and detect root nodes
        $directoryMap = []; // old id_directory → new id_storage
        $pending = $allDirectories;
        $maxIterations = 100; // safety guard for deeply nested trees
        $iteration = 0;

        while (!empty($pending) && $iteration < $maxIterations) {
            $iteration++;
            $remaining = [];

            foreach ($pending as $dir) {
                $oldParentId = $dir['id_parent'];

                // If parent is null (root) or parent already migrated, we can insert
                if ($oldParentId === null || isset($directoryMap[$oldParentId])) {
                    $newParentId = ($oldParentId === null) ? null : ($directoryMap[$oldParentId] ?? null);

                    $this->insert($storageTable, [
                        'type'         => 'directory',
                        'name'         => $dir['name'],    // directory name as storage name
                        'title'        => $dir['name'],    // directory name as title
                        'id_user'      => $dir['id_user'],
                        'mime_type'    => 0,               // N/A for directories
                        'id_directory' => $newParentId,    // parent directory (now a storage row)
                        'id_workspace' => $dir['id_workspace'] ?? null,
                        'access'       => 1,               // default access
                        'date_create'  => $dir['date_create'],
                        'date_update'  => $dir['date_update'],
                    ]);

                    $newId = $this->db->getLastInsertID();
                    $directoryMap[$dir['id_directory']] = $newId;
                } else {
                    // Parent not yet migrated, try later
                    $remaining[] = $dir;
                }
            }

            // If nothing was migrated in this iteration, there's a problem (orphaned records)
            if (count($remaining) === count($pending)) {
                // Orphaned directories — insert them with null parent
                foreach ($remaining as $dir) {
                    $this->insert($storageTable, [
                        'type'         => 'directory',
                        'name'         => $dir['name'],
                        'title'        => $dir['name'],
                        'id_user'      => $dir['id_user'],
                        'mime_type'    => 0,
                        'id_directory' => null,
                        'id_workspace' => $dir['id_workspace'] ?? null,
                        'access'       => 1,
                        'date_create'  => $dir['date_create'],
                        'date_update'  => $dir['date_update'],
                    ]);
                    $newId = $this->db->getLastInsertID();
                    $directoryMap[$dir['id_directory']] = $newId;
                }
                break;
            }

            $pending = $remaining;
        }

        // ───────────────────────────────────────────────────────
        // 5. Update file records: old id_directory → new id_storage
        // ───────────────────────────────────────────────────────
        foreach ($directoryMap as $oldDirId => $newStorageId) {
            $this->update(
                $storageTable,
                ['id_directory' => $newStorageId],
                ['id_directory' => $oldDirId, 'type' => 'file']
            );
        }

        // ───────────────────────────────────────────────────────
        // 6. Update share records: old id_directory → new id_storage reference
        //    The share table's id_directory column used to point to storage_directory.
        //    Now it should point to the new storage IDs.
        // ───────────────────────────────────────────────────────
        foreach ($directoryMap as $oldDirId => $newStorageId) {
            $this->update(
                $shareTable,
                ['id_directory' => $newStorageId],
                ['id_directory' => $oldDirId]
            );
        }

        // ───────────────────────────────────────────────────────
        // 7. Add self-referencing FK: storage.id_directory → storage.id_storage
        // ───────────────────────────────────────────────────────
        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_directory}}',
            $storageTable,
            'id_directory',
            $storageTable,
            'id_storage',
            'SET NULL'
        );

        // ───────────────────────────────────────────────────────
        // 8. Add FK: storage_share.id_directory → storage.id_storage
        // ───────────────────────────────────────────────────────
        $this->addForeignKey(
            'fk_storage_share_directory',
            $shareTable,
            'id_directory',
            $storageTable,
            'id_storage',
            'CASCADE',
            'CASCADE'
        );

        // ───────────────────────────────────────────────────────
        // 9. Add index on type for fast filtering
        // ───────────────────────────────────────────────────────
        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage-type}}',
            $storageTable,
            'type'
        );
    }

    public function safeDown()
    {
        $storageTable = '{{%' . Module::$tablePrefix . 'storage}}';
        $shareTable   = '{{%' . Module::$tablePrefix . 'storage_share}}';

        // This migration is not safely reversible because directory records
        // were merged into storage. Manual intervention is required.
        echo "This migration cannot be safely reversed.\n";
        echo "To reverse manually:\n";
        echo "1. Re-create storage_storage_directory table and re-insert records.\n";
        echo "2. Update storage.id_directory values back to old directory IDs.\n";
        echo "3. Drop the type column from storage table.\n";

        return false;
    }
}
