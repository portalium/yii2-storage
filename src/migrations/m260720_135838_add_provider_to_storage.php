<?php

use yii\db\Migration;
use portalium\storage\Module;

class m260720_135838_add_provider_to_storage extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        // Force full table rebuild instead of INSTANT/INPLACE metadata patch.
        // Without this, the migration will allways fail on certain setups
        // the ibd table only have space for 1 collum left in current migrations and when trying to add 3
        // it will try to extend the file, which throws an error in current setup(a documented bug happening with docker machines on windows)
        // it will not lose or corrupt data, it is a safe operation
        // it will try to genarate copy of the table which can be slow if table has data (which can cause a time out if data amount is sufficently big)
        // which also again can be caught on same error (trying to expand ibd on certain setups) if data amount is high
        $this->execute('SET SESSION alter_algorithm=COPY;');

        $storageTable  = '{{%' . Module::$tablePrefix . 'storage}}';
        $providerTable = '{{%' . Module::$tablePrefix . 'storage_provider}}';

        $this->addColumn($storageTable, 'id_provider', $this->integer(11)->null()->after('id_directory'));
        $this->addColumn($storageTable, 'provider_file_id', $this->string(255)->null()->after('id_provider'));
        $this->addColumn($storageTable, 'provider_meta', $this->json()->null()->after('provider_file_id'));

          $this->addForeignKey(
            '{{%fk-' . Module::$tablePrefix . 'storage-id_provider}}',
            $storageTable,
            'id_provider',
            $providerTable,
            'id_provider',
            'SET NULL'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage-id_provider}}',
            $storageTable,
            'id_provider'
        );

        $this->createIndex(
            '{{%idx-' . Module::$tablePrefix . 'storage-provider_file_id}}',
            $storageTable,
            ['id_provider', 'provider_file_id']
        );
    }

    public function safeDown()
    {
        $storageTable = '{{%' . Module::$tablePrefix . 'storage}}';

        $this->dropIndex('{{%idx-' . Module::$tablePrefix . 'storage-provider_file_id}}', $storageTable);
        $this->dropIndex('{{%idx-' . Module::$tablePrefix . 'storage-id_provider}}', $storageTable);
        $this->dropForeignKey('{{%fk-' . Module::$tablePrefix . 'storage-id_provider}}', $storageTable);

        $this->dropColumn($storageTable, 'provider_meta');
        $this->dropColumn($storageTable, 'provider_file_id');
        $this->dropColumn($storageTable, 'id_provider');
    }
}