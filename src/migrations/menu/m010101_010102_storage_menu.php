<?php

use portalium\db\Migration;
use portalium\menu\models\Menu;
use portalium\menu\models\MenuItem;

class m010101_010102_storage_menu extends Migration
{

    public function up()
    {

        $id_menu = Menu::find()->where(['slug' => 'web-main-menu'])->one()->id_menu;
        $id_item = MenuItem::find()->where(['slug' => 'site'])->one();

        if (!$id_item) {
            $this->insert('menu_item', [
                'id_item' => NULL,
                'label' => 'Site',
                'slug' => 'site',
                'type' => '3',
                'style' => '{"icon":"fa-cog","color":"","iconSize":"","display":"1","childDisplay":"3"}',
                'data' => '{"data":{"url":"#"}}',
                'sort' => '1',
                'id_menu' => $id_menu,
                'name_auth' => 'admin',
                'id_user' => '1',
                'date_create' => '2022-06-13 15:32:26',
                'date_update' => '2022-06-13 15:32:26',
            ]);
        } else {
            $id_item = MenuItem::find()->where(['slug' => 'site'])->one()->id_item;
        }

        $id_item = MenuItem::find()->where(['slug' => 'site'])->one()->id_item;

        $this->batchInsert('menu_item', ['id_item', 'label', 'slug', 'type', 'style', 'data', 'sort', 'id_menu', 'name_auth', 'id_user', 'date_create', 'date_update'], [
            [NULL, 'Storage', 'storage', '1', '{"icon":"","color":"","iconSize":"","display":"","childDisplay":false}', '{"data":{"route":"\\/storage\\/default\\/index","module":null}}', '14', $id_menu, 'storageWebDefaultIndex', 1, '2022-06-13 15:32:26', '2022-06-13 15:32:26'],
        ]);

        $ids = $this->db->createCommand('SELECT id_item FROM menu_item WHERE slug in (\'storage\')')->queryColumn();


        foreach ($ids as $id) {
            $this->insert('menu_item_child', [
                'id_item' => $id_item,
                'id_child' => $id
            ]);
        }

        $id_menu_side = Menu::find()->where(['slug' => 'web-side-menu'])->one()->id_menu;

        if ($id_menu_side) {
            $this->batchInsert('menu_item', ['id_item', 'label', 'slug', 'type', 'style', 'data', 'sort', 'id_menu', 'name_auth', 'id_user', 'date_create', 'date_update'], [
                [NULL, 'Storage', 'storage-side', '2', '{"icon":"fa-archive","color":"","iconSize":"","display":"3","childDisplay":"","placement":"1"}', '{"data":{"module":"storage","routeType":"action","route":"\\/storage\\/default\\/index","model":"","menuRoute":null,"menuType":null}}', 5, $id_menu_side, 'user', 1, '2024-02-08 11:13:57', '2024-02-08 11:15:06'],
            ]);
        }
    }

    public function down()
    {
        $ids = $this->db->createCommand('SELECT id_item FROM menu_item WHERE slug in (\'storage\')')->queryColumn();

        $this->delete('menu_item', ['id_item' => $ids]);
    }
}
