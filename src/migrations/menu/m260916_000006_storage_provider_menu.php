<?php

use portalium\db\Migration;
use portalium\menu\models\Menu;
use portalium\menu\models\MenuItem;

class m260916_000006_storage_provider_menu extends Migration
{
    const SLUG_MAIN = 'storage-provider-manage';
    const SLUG_SIDE = 'storage-provider-side';

    public function up()
    {
        $this->addMainMenuItem();
        $this->addSideMenuItem();
    }

    public function down()
    {
        $ids = $this->db->createCommand(
            'SELECT id_item FROM menu_item WHERE slug IN (:main, :side)',
            [':main' => self::SLUG_MAIN, ':side' => self::SLUG_SIDE]
        )->queryColumn();

        if (!empty($ids)) {
            $this->delete('menu_item_child', ['id_child' => $ids]);
            $this->delete('menu_item', ['id_item' => $ids]);
        }
    }

    private function addMainMenuItem()
    {
        $menu = Menu::find()->where(['slug' => 'web-main-menu'])->one();

        if (!$menu || MenuItem::find()->where(['slug' => self::SLUG_MAIN])->exists()) {
            return;
        }

        $this->insert('menu_item', [
            'id_item' => null,
            'label' => 'Storage Providers',
            'slug' => self::SLUG_MAIN,
            'type' => MenuItem::TYPE['route'],
            'style' => '{"icon":"","color":"","iconSize":"","display":"","childDisplay":false}',
            'data' => '{"data":{"route":"\\/storage\\/provider\\/manage","module":null}}',
            'sort' => '12',
            'id_menu' => $menu->id_menu,
            'name_auth' => 'storageWebProviderManage',
            'id_user' => 1,
            'date_create' => date('Y-m-d H:i:s'),
            'date_update' => date('Y-m-d H:i:s'),
        ]);

        $newId = $this->db->getLastInsertID();
        $parent = MenuItem::find()->where(['slug' => 'storage'])->one();

        if ($parent && $newId) {
            $this->insert('menu_item_child', [
                'id_item' => $parent->id_item,
                'id_child' => $newId,
            ]);
        }
    }

    private function addSideMenuItem()
    {
        $menu = Menu::find()->where(['slug' => 'web-side-menu'])->one();

        if (!$menu || MenuItem::find()->where(['slug' => self::SLUG_SIDE])->exists()) {
            return;
        }

        $this->insert('menu_item', [
            'id_item' => null,
            'label' => 'Storage Providers',
            'slug' => self::SLUG_SIDE,
            'type' => MenuItem::TYPE['module'],
            'style' => '{"icon":"fa-hdd-o","color":"","iconSize":"","display":"3","childDisplay":"","placement":"1"}',
            'data' => '{"data":{"module":"storage","routeType":"action","route":"\\/storage\\/provider\\/index","model":"","menuRoute":null,"menuType":null}}',
            'sort' => 12,
            'id_menu' => $menu->id_menu,
            'name_auth' => 'storageWebProviderIndex',
            'id_user' => 1,
            'date_create' => date('Y-m-d H:i:s'),
            'date_update' => date('Y-m-d H:i:s'),
        ]);
    }
}
