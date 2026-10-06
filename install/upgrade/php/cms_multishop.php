<?php


function cms_multishop()
{
    $shops = Db::getInstance()->executeS('
		SELECT `id_shop`
		FROM `'._DB_PREFIX_.'shop`
		');

    $cms_lang = Db::getInstance()->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'cms_lang`
	');
    foreach ($cms_lang as $value) {
        $data = array();
        $cms = array(
            'id_cms' => $value['id_cms'],
            'id_lang' => $value['id_lang'],
            'content' => pSQL($value['content']),
            'link_rewrite' => pSQL($value['link_rewrite']),
            'meta_title' => pSQL($value['meta_title']),
            'meta_keywords' => pSQL($value['meta_keywords']),
            'meta_description' => pSQL($value['meta_description'])
            );
        foreach ($shops as $shop) {
            if ($shop['id_shop'] != 1) {
                $cms['id_shop'] = $shop['id_shop'];
                $data[] = $cms;
            }
        }
        Db::getInstance()->insert('cms_lang', $data);
    }

    $cms_category_lang = Db::getInstance()->executeS('
		SELECT *
		FROM `'._DB_PREFIX_.'cms_category_lang`
	');
    foreach ($cms_category_lang as $value) {
        $data = array();
        $data_bis = array();

        $cms_category_shop = array(
            'id_cms_category' => $value['id_cms_category'],
            );
        $cms_category = array(
            'id_cms_category' => $value['id_cms_category'],
            'id_lang' => $value['id_lang'],
            'name' => pSQL($value['name']),
            'description' => pSQL($value['description']),
            'link_rewrite' => pSQL($value['link_rewrite']),
            'meta_title' => pSQL($value['meta_title']),
            'meta_keywords' => pSQL($value['meta_keywords']),
            'meta_description' => pSQL($value['meta_description'])
            );
        foreach ($shops as $shop) {
            if ($shop['id_shop'] != 1) {
                $cms_category['id_shop'] = $shop['id_shop'];
                $data[] = $cms_category;
            }
            $cms_category_shop['id_shop'] = $shop['id_shop'];
            $data_bis[] = $cms_category_shop;
        }
        Db::getInstance()->insert('cms_category_lang', $data, false, true, Db::INSERT_IGNORE);
        Db::getInstance()->insert('cms_category_shop', $data_bis, false, true, Db::INSERT_IGNORE);
    }
}
