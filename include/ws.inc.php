<?php

// ---------------------------
//    WebService addFromServer
// ---------------------------
// Adaptation de la méthode Piwigo: /include/ws_functions.inc.php - Fonction: ws_images_addSimple

function ws_images_addFromServer($params, & $service) {
    
    global $conf;
    // Admin only
    if (!is_admin()) {
        return new PwgError(401, 'Access denied');
    }

    // Image path verification
    if (!is_file($params['image_path'])) {
        return new PwgError(WS_ERR_INVALID_PARAM, "Image path not specified or not valid");
    }

    // Image already known ?
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    $query = '
      SELECT *
      FROM '.IMAGES_TABLE.'
      WHERE md5sum = \''.md5_file($params['image_path']).'\'
      ;';
    $image_row = pwg_db_fetch_assoc(pwg_query($query));
    if ($image_row != null) {
        return new PwgError(WS_ERR_INVALID_PARAM, "Image already in database");
    }

    // Image_id verification
    $params['image_id'] = (int) $params['image_id'];
    if ($params['image_id'] > 0) {
        $query = '
        SELECT *
        FROM '.IMAGES_TABLE.'
        WHERE id = '.$params['image_id'].'
                             ;';
        $image_row = pwg_db_fetch_assoc(pwg_query($query));
        if ($image_row == null) {
            return new PwgError(404, "image_id not found");
        }
    }

    // Category
    $params['category'] = (int) $params['category'];
    if ($params['category'] <= 0 and $params['image_id'] <= 0) {
        return new PwgError(WS_ERR_INVALID_PARAM, "Invalid category_id");
    }

    // Copy original in temporary folder
    $original = $params['image_path'];
    $params['image_path'] = PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer/'.basename($original);
    copy($original, $params['image_path']);

    require_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');
    // Fonction add_uploaded_file du script /admin/include/functions_upload.inc.php
    $image_id = add_uploaded_file(
    $params['image_path'], basename($params['image_path']), $params['category'] > 0 ? array($params['category']) : null, isset($params['level']) ? $params['level'] : null, $params['image_id'] > 0 ? $params['image_id'] : null);

    $info_columns = array('name', 'author', 'comment', 'level', 'date_creation', );

    foreach($info_columns as $key) {
        if (isset($params[$key])) {
            $update[$key] = $params[$key];
        }
    }

    if (count(array_keys($update)) > 0) {
        $update['id'] = $image_id;

        include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
        mass_updates(
        IMAGES_TABLE, array('primary' = > array('id'), 'update' = > array_diff(array_keys($update), array('id'))), array($update));
    }

    // Add tags to the image if specified
    if (isset($params['tags']) and!empty($params['tags'])) {
        $tag_ids = array();
        $tag_names = explode(',', $params['tags']);
        foreach($tag_names as $tag_name) {
            $tag_id = tag_id_from_tag_name($tag_name);
            array_push($tag_ids, $tag_id);
        }

        add_tags($tag_ids, array($image_id));
    }

    $url_params = array('image_id' = > $image_id);

    if ($params['category'] > 0) {
        $query = '
        SELECT id, name, permalink
        FROM '.CATEGORIES_TABLE.'
        WHERE id = '.$params['category'].'
                             ;';
        $result = pwg_query($query);
        $category = pwg_db_fetch_assoc($result);

        $url_params['section'] = 'categories';
        $url_params['category'] = $category;
    }

    // update metadata from the uploaded file (exif/iptc), even if the sync
    // was already performed by add_uploaded_file().
    $query = '
      SELECT
      path
      FROM '.IMAGES_TABLE.'
      WHERE id = '.$image_id.'
      ;';
    list($file_path) = pwg_db_fetch_row(pwg_query($query));

    require_once(PHPWG_ROOT_PATH.'admin/include/functions_metadata.php');
    sync_metadata(array($image_id));

    //Symlink original picture if not resized
    $need_resize = ($conf['original_resize'] and need_resize($file_path, $conf['original_resize_maxwidth'], $conf['original_resize_maxheight']));
    if (!$conf['original_resize'] or!$need_resize) {
        //Replace HIGH picture by a symlink to the original
        $real_path = realpath($file_path);
        unlink($real_path);
        symlink($original, $real_path);
    }

    return array('image_id' = > $image_id, 'url' = > make_picture_url($url_params));
}

// ---------------------------
//    WebService existFromPath
// ---------------------------

function ws_images_existFromPath($params, & $service) {

    // Image path verification
    if (!is_file($params['image_path'])) {
        return new PwgError(WS_ERR_INVALID_PARAM, "Image path not specified or not valid");
    }

    $md5 = md5_file($params["image_path"]);

    return invoke("ws_images_exist", array('md5sum_list' = > $md5));
}

?>