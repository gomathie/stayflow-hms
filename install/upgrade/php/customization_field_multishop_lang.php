<?php


function customization_field_multishop_lang()
{
    $shops = Db::getInstance()->executeS('
		SELECT `id_shop`
		FROM `'._DB_PREFIX_.'shop`
		WHERE `id_shop` != 1
		');

    $customization_field_lang = Db::getInstance()->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'customization_field_lang`
		');

    foreach ($customization_field_lang as $value) {
        $data = array();
        $customization_lang = array(
            'id_customization_field' => $value['id_customization_field'],
            'id_lang' => $value['id_lang'],
            'name' => pSQL($value['name'])
            );
        foreach ($shops as $shop) {
            $customization_lang['id_shop'] = $shop['id_shop'];
            $data[] = $customization_lang;
        }
        Db::getInstance()->insert('customization_field_lang', $data);
    }
}
