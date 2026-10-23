<?php

use portalium\db\Migration;
use portalium\site\Module as SiteModule;
use portalium\site\models\Form;

class m260916_000001_storage_provider_key extends Migration
{
    public function init()
    {
        $this->db = 'db';
        parent::init();
    }

    public function safeUp()
    {
        $table = SiteModule::$tablePrefix . 'setting';
        $exists = (new \yii\db\Query())
            ->select('name')
            ->from($table)
            ->where(['module' => 'storage', 'name' => 'storage::provider_key'])
            ->createCommand($this->db)
            ->queryScalar() !== false;
        if ($exists) {
            return true;
        }

        $key = Yii::$app->security->generateRandomString(32);

        $this->insert($table, [
            'module' => 'storage',
            'name' => 'storage::provider_key',
            'label' => 'Provider Encryption Key',
            'value' => $key,
            'type' => Form::TYPE_INPUTHIDDEN,
            'is_preference' => 0,
            'config' => json_encode([
                'hint' => 'Generated automatically. Changing this makes existing provider credentials unreadable.',
            ]),
        ]);
    }

    public function safeDown()
    {
        $this->delete(SiteModule::$tablePrefix . 'setting', [
            'module' => 'storage',
            'name' => 'storage::provider_key',
        ]);
    }
}
