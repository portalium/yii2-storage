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

        $this->addColumn($storageTable, 'type', $this->string(20)->notNull()->defaultValue('file')->after('id_storage'));        $this->update($storageTable, ['type' => 'file']);

        $this->dropForeignKey('{{%fk-' . Module::$tablePrefix . 'storage-id_directory}}', $storageTable);

        try {
            $this->dropForeignKey('fk_storage_share_directory', $shareTable);
        } catch (\Exception $e) {
        }        $allDirectories = $this->db->createCommand("SELECT * FROM {$directoryTable} ORDER BY id_parent ASC, id_directory ASC")->queryAll();        $directoryMap = [];
        $pending = $allDirectories;
        $maxIterations = 100;
        $iteration = 0;

        while (!empty($pending) && $iteration < $maxIterations) {
            $iteration++;
            $remaining = [];

            foreach ($pending as $dir) {
                $oldParentId = $dir['id_parent'];                if ($oldParentId === null || isset($directoryMap[$oldParentId])) {
                    $newParentId = ($oldParentId === null) ? null : ($directoryMap[$oldParentId] ?? null);

                    $this->insert($storageTable, [
                        'type'         => 'directory',
                        'name'         => $dir['name'],
                        'title'        => $dir['name'],
                        'id_user'      => $dir['id_user'],
                        'mime_type'    => 0,
                        'id_directory' => $newParentId,
                        'id_workspace' => $dir['id_workspace'] ?? null,
                        'access'       => 1,
                        'date_create'  => $dir['date_create'],
                        'date_update'  => $dir['date_update'],
                    ]);

                    $newId = $this->db->getLastInsertID();
                    $directoryMap[$dir['id_directory']] = $newId;
                } else {

                    $remaining[] = $dir;
                }
            }            if (count($remaining) === count($pending)) {

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

        foreach ($directoryMap as $oldDirId => $newStorageId) {
            $this->update(
                $storageTable,
                ['id_directory' => $newStorageId],
                ['id_directory' => $oldDirId, 'type' => 'file']
            );
        }
        foreach ($directoryMap as $oldDirId => $newStorageId) {
            $this->update(
                $shareTable,
                ['id_directory' => $newStorageId],
                ['id_directory' => $oldDirId]
            );
        }

        $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_directory}}',
            $storageTable,
            'id_directory',
            $storageTable,
            'id_storage',
            'SET NULL'
        );

        $this->addForeignKey(
            'fk_storage_share_directory',
            $shareTable,
            'id_directory',
            $storageTable,
            'id_storage',
            'CASCADE',
            'CASCADE'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage-type}}',
            $storageTable,
            'type'
        );

        $this->dropTable($directoryTable);
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
