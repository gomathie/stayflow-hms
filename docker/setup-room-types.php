<?php
/**
 * Creates the hotel's real room types from the owner's first room list, and
 * renames the three matching demo room types instead of leaving them as
 * separate stale entries. Run by docker/entrypoint.sh as www-data whenever
 * ROOM_TYPES_VERSION changes. Safe to run repeatedly: a step is skipped once
 * its room type already carries the new name, so edits made afterwards in
 * the back office are not overwritten (rule 22).
 *
 * Lives outside the web root (see Dockerfile); it must never be reachable by URL.
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require '/var/www/html/config/config.inc.php';

$failed = false;

function sb_log($message)
{
    echo '[setup-room-types] '.$message."\n";
}

// Bed type ids (qlo_htl_bed_type, seeded by the installer: 1 Twin, 3 Full/Double, 4 Queen, 5 King)
define('SB_BED_FULL', 3);
define('SB_BED_QUEEN', 4);
define('SB_BED_KING', 5);
define('SB_BED_TWIN', 1);

function sb_set_bed_types($idProduct, array $bedTypeIds)
{
    Db::getInstance()->execute(
        'DELETE FROM `'._DB_PREFIX_.'htl_room_type_bed_type` WHERE `id_product` = '.(int) $idProduct
    );
    foreach ($bedTypeIds as $idBedType) {
        Db::getInstance()->insert('htl_room_type_bed_type', array(
            'id_product' => (int) $idProduct,
            'id_bed_type' => (int) $idBedType,
        ));
    }
}

function sb_description_html(array $amenities)
{
    $html = '<ul>';
    foreach ($amenities as $amenity) {
        $html .= '<li>'.htmlspecialchars($amenity, ENT_QUOTES, 'UTF-8').'</li>';
    }
    $html .= '</ul>';

    return $html;
}

function sb_apply_lang_fields(Product $product, $name, array $amenities)
{
    $description = sb_description_html($amenities);
    $shortDescription = sb_description_html($amenities);
    foreach (Language::getLanguages(false) as $language) {
        $idLang = (int) $language['id_lang'];
        $product->name[$idLang] = $name;
        $product->description[$idLang] = $description;
        $product->description_short[$idLang] = $shortDescription;
        $product->link_rewrite[$idLang] = Tools::str2url($name);
    }
}

/**
 * Renames and reprices an existing demo room type product in place.
 */
function sb_update_existing_room($idProduct, $name, $price, array $bedTypeIds, array $amenities)
{
    $product = new Product((int) $idProduct);
    if (!Validate::isLoadedObject($product)) {
        sb_log("ERROR: product $idProduct not found");

        return false;
    }

    // Idempotent: skip once this product already carries the new name,
    // so a later edit made by hand in the back office is not undone.
    $currentName = $product->name[(int) Configuration::get('PS_LANG_DEFAULT')];
    if ($currentName === $name) {
        sb_log("$name: already in place, skipped");

        return true;
    }

    sb_apply_lang_fields($product, $name, $amenities);
    $product->price = (float) $price;
    $product->active = 1;
    if (!$product->update()) {
        sb_log("ERROR: could not update product $idProduct to $name");

        return false;
    }

    sb_set_bed_types($product->id, $bedTypeIds);
    sb_log("$name: renamed from \"$currentName\" (was product $idProduct), price GHS $price");

    return true;
}

/**
 * Creates a new room type by duplicating an existing one (same approach as
 * the back office's own "Duplicate" action on a room type, so the hotel
 * assignment, tax rules group and location categories all come out right),
 * then applying the new name, price, bed types and description. No
 * individual rooms (room numbers/floors) are copied: the owner adds those
 * once the real room numbers are known (Admin Guide, section 2).
 */
function sb_create_room_from_template($idTemplateProduct, $name, $price, array $bedTypeIds, array $amenities)
{
    // Idempotent: skip if a room type with this name already exists.
    $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');
    $existing = (int) Db::getInstance()->getValue(
        'SELECT pl.`id_product` FROM `'._DB_PREFIX_.'product_lang` pl
        WHERE pl.`id_lang` = '.$idLangDefault.' AND pl.`name` = \''.pSQL($name).'\''
    );
    if ($existing) {
        sb_log("$name: already exists (product $existing), skipped");

        return true;
    }

    $template = new Product((int) $idTemplateProduct);
    if (!Validate::isLoadedObject($template)) {
        sb_log("ERROR: template product $idTemplateProduct not found");

        return false;
    }

    $objHotelRoomType = new HotelRoomType();
    $roomTypeInfo = $objHotelRoomType->getRoomTypeInfoByIdProduct($idTemplateProduct);
    if (!$roomTypeInfo) {
        sb_log("ERROR: template product $idTemplateProduct has no room type information");

        return false;
    }

    $product = new Product((int) $idTemplateProduct);
    unset($product->id);
    unset($product->id_product);
    $product->indexed = 0;
    $product->active = 1;

    $idHotel = (int) $roomTypeInfo['id_hotel'];
    $objHotelBranchInformation = new HotelBranchInformation($idHotel);
    $product->id_category_default = $objHotelBranchInformation->id_category;

    sb_apply_lang_fields($product, $name, $amenities);
    $product->price = (float) $price;

    if (!$product->add()
        || !Product::duplicateSuppliers($idTemplateProduct, $product->id)
        || (($combinationImages = Product::duplicateAttributes($idTemplateProduct, $product->id)) === false)
        || !GroupReduction::duplicateReduction($idTemplateProduct, $product->id)
        || !Product::duplicateAccessories($idTemplateProduct, $product->id)
        || !Product::duplicateFeatures($idTemplateProduct, $product->id)
        || !Pack::duplicate($idTemplateProduct, $product->id)
        || !Product::duplicateCustomizationFields($idTemplateProduct, $product->id)
        || !Product::duplicateTags($idTemplateProduct, $product->id)
        || !Product::duplicateDownload($idTemplateProduct, $product->id)
    ) {
        sb_log("ERROR: could not create product for $name");

        return false;
    }

    if ($product->hasAttributes()) {
        Product::updateDefaultAttribute($product->id);
    } else {
        Product::duplicateSpecificPrices($idTemplateProduct, $product->id);
    }

    $idRoomTypeNew = HotelRoomType::duplicateRoomType($idTemplateProduct, $product->id, $idHotel, true);
    if (!$idRoomTypeNew) {
        sb_log("ERROR: could not create room type information for $name");

        return false;
    }

    sb_set_bed_types($product->id, $bedTypeIds);

    Hook::exec('actionProductAdd', array('id_product' => (int) $product->id, 'product' => $product));
    if (in_array($product->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION')) {
        Search::indexation(false, $product->id);
    }

    sb_log("$name: created as product {$product->id}, from template $idTemplateProduct, price GHS $price");

    return true;
}

// Standard Room 1, Deluxe Room 1 and Executive Room 1 reuse the installer's
// demo room types (General Rooms, Delux Rooms, Executive Rooms) instead of
// leaving them as unrelated stale entries next to the real room types.
$ok = sb_update_existing_room(1, 'Standard Room 1', 200.00, array(SB_BED_FULL), array('Fan', 'TV'))
    && sb_update_existing_room(2, 'Deluxe Room 1', 300.00, array(SB_BED_FULL), array('Fan', 'Air Conditioning', 'Breakfast included', 'Wi-Fi', 'TV'))
    && sb_update_existing_room(3, 'Executive Room 1', 450.00, array(SB_BED_KING), array('Fan', 'Air Conditioning', 'Breakfast included', 'Water heater', 'Wi-Fi', 'Snacks'))
    && sb_create_room_from_template(1, 'Standard Room 2', 250.00, array(SB_BED_FULL), array('Fan', 'Breakfast included', 'Wi-Fi', 'TV'))
    && sb_create_room_from_template(1, 'Standard Room 3', 250.00, array(SB_BED_TWIN, SB_BED_TWIN), array('Twin room (2 single beds)', 'Fan', 'Breakfast included', 'Wi-Fi', 'TV'))
    && sb_create_room_from_template(2, 'Deluxe Room 2', 350.00, array(SB_BED_QUEEN), array('Fan', 'Air Conditioning', 'Breakfast included', 'Water heater', 'Wi-Fi'));

if (!$ok) {
    $failed = true;
}

exit($failed ? 1 : 0);
