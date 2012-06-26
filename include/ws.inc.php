<?php

  // ---------------------------
  //    WebService addFromServer
  // ---------------------------
  
  function ws_images_addFromServer($params, &$service)
  {
    global $conf;
    // Admin only
    if (!is_admin())
    {
      return new PwgError(401, 'Access denied');
    }
    
    // Image path verification
    if (!is_file($params['image_path']))
    {
      return new PwgError(WS_ERR_INVALID_PARAM, "Invalid image path");
    }
    // Image already known ?
    include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
    $query='
      SELECT *
      FROM '.IMAGES_TABLE.'
      WHERE md5sum = \''.md5_file($params['image_path']).'\'
      ;';
    $image_row = pwg_db_fetch_assoc(pwg_query($query));
    if ($image_row != null)
    {
      return new PwgError(WS_ERR_INVALID_PARAM, "Image already in database");
    }
    
    // Image_id verification
    $params['image_id'] = (int)$params['image_id'];
    if ($params['image_id'] > 0)
    {    
      $query='
        SELECT *
        FROM '.IMAGES_TABLE.'
        WHERE id = '.$params['image_id'].'
                             ;';    
      $image_row = pwg_db_fetch_assoc(pwg_query($query));
      if ($image_row == null)
      {
        return new PwgError(404, "image_id not found");
      }
    }
    
    // Category
    $params['category'] = (int)$params['category'];
    if ($params['category'] <= 0 and $params['image_id'] <= 0)
    {
      return new PwgError(WS_ERR_INVALID_PARAM, "Invalid category_id");
    }
    
    require_once(PHPWG_ROOT_PATH.'admin/include/functions_upload.inc.php');
    
	// If no HIGH image, auto-rotate the original
	$original = $params['image_path'];
	if ($conf['upload_form_websize_resize']
	    and !need_resize($original, $conf['upload_form_websize_maxwidth'], $conf['upload_form_websize_maxheight']))
	{
		//Rotate image if necessary
		$params['image_path'] = PHPWG_ROOT_PATH.PWG_LOCAL_DIR.'AddFromServer/'. basename($params['image_path']);
		$cmd = "convert -auto-orient '".$original."' '".$params['image_path']."'";
		exec($cmd);
	}
	
    $image_id = add_uploaded_file(
      $params['image_path'],
      basename($params['image_path']),
      $params['category'] > 0 ? array($params['category']) : null,
      isset($params['level']) ? $params['level'] : null,
      $params['image_id'] > 0 ? $params['image_id'] : null
    );
	
	//Clean-up
	if($original != $params['image_path'])
		unlink($params['image_path']);
	
    $info_columns = array(
      'name',
      'author',
      'comment',
      'level',
      'date_creation',
    );
    
    foreach ($info_columns as $key)
             {
               if (isset($params[$key]))
               {
                 $update[$key] = $params[$key];
               }
             }
    
    if (count(array_keys($update)) > 0)
    {
      $update['id'] = $image_id;
      
      include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
      mass_updates(
        IMAGES_TABLE,
        array(
          'primary' => array('id'),
          'update'  => array_diff(array_keys($update), array('id'))
        ),
        array($update)
      );
    }
    
    if (isset($params['tags']) and !empty($params['tags']))
    {
      $tag_ids = array();
      $tag_names = explode(',', $params['tags']);
      foreach ($tag_names as $tag_name)
               {
                 $tag_id = tag_id_from_tag_name($tag_name);
                 array_push($tag_ids, $tag_id);
               }
      
      add_tags($tag_ids, array($image_id));
    }
    
    $url_params = array('image_id' => $image_id);
    
    if ($params['category'] > 0)
    {
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
    update_metadata(array($image_id=>$file_path));
    
	//Symlink high picture if exists
	$query = '
      SELECT
      id,path,has_high
      FROM '.IMAGES_TABLE.'
      WHERE id = '.$image_id.'
      ;';
	$element_info = pwg_db_fetch_assoc(pwg_query($query));
	
	require_once(PHPWG_ROOT_PATH.'include/functions_picture.inc.php');
	$location = get_high_location($element_info);
	if($location != '') { // No HIGH image
		//Replace HIGH picture by a symlink to the original
		$location = realpath($location);
		unlink($location);
		symlink($original,$location);
	}
	
    return array(
      'image_id' => $image_id,
      'url' => make_picture_url($url_params),
    );
  }
  
  ?>