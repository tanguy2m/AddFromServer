<?php
/***************************************************************************
 *
 *             Encode Explorer
 *             Author / Autor : Marek Rei (marek ät siineiolekala dot net)
 *             Version / Versioon : 6.3
 *             Last change / Viimati muudetud: 23.09.2011
 *             Homepage / Koduleht: encode-explorer.siineiolekala.net
 *
 *             Modified by tanguy2m - Dec 2011, Mar/Apr 2012, June 2013
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This is free software and it's distributed under GPL Licence.
 *   
 *   The icon images are designed by Mark James (http://www.famfamfam.com) 
 *   and distributed under the Creative Commons Attribution 3.0 License.
 *
 ***************************************************************************/

// Initialising variables. Don't change these.
//

$_CONFIG = array();
$_ERROR = "";
$_START_TIME = microtime(TRUE);

/* 
 * GENERAL SETTINGS
 */

// Choose a language. See below in the language section for options.
// Default: $_CONFIG['lang'] = "en";
//
$_CONFIG['lang'] = "fr";

// Display thumbnails when hovering over image entries in the list.
// Common image types are supported (jpeg, png, gif).
// Pdf files are also supported but require ImageMagick to be installed.
// Default: $_CONFIG['thumbnails'] = true;
//
$_CONFIG['thumbnails'] = true;

// Maximum sizes of the thumbnails.
// Default: $_CONFIG['thumbnails_width'] = 200;
// Default: $_CONFIG['thumbnails_height'] = 200;
//
$_CONFIG['thumbnails_width'] = 300;
$_CONFIG['thumbnails_height'] = 300;

// Mobile interface enabled. true/false
// Default: $_CONFIG['mobile_enabled'] = true;
//
$_CONFIG['mobile_enabled'] = false;

// Mobile interface as the default setting. true/false
// Default: $_CONFIG['mobile_default'] = false;
//
$_CONFIG['mobile_default'] = false;

/*
 * USER INTERFACE
 */

// Will the files be opened in a new window? true/false 
// Default: $_CONFIG['open_in_new_window'] = false;
//
$_CONFIG['open_in_new_window'] = true;

// The time format for the "last changed" column.
// Default: $_CONFIG['time_format'] = "d.m.y H:i:s";
//
$_CONFIG['time_format'] = "d/m/y";

// Charset. Use the one that suits for you. 
// Default: $_CONFIG['charset'] = "UTF-8";
//
$_CONFIG['charset'] = "UTF-8";

/*
* PERMISSIONS
*/

// The array of folder names that will be hidden from the list.
// Default: $_CONFIG['hidden_dirs'] = array();
//
$_CONFIG['hidden_dirs'] = array();

// Filenames that will be hidden from the list.
// Default: $_CONFIG['hidden_files'] = array(".ftpquota", "index.php", "index.php~", ".htaccess", ".htpasswd");
//
$_CONFIG['hidden_files'] = array(".ftpquota", "index.php", "index.php~", ".htaccess", ".htpasswd");

/*
 * SYSTEM
 */

// The starting directory. Normally no need to change this.
// Use only relative subdirectories! 
// For example: $_CONFIG['starting_dir'] = "./mysubdir/";
// Default: $_CONFIG['starting_dir'] = ".";
//
$_CONFIG['starting_dir'] = "./photos/";

// Location in the server. Usually this does not have to be set manually.
// Default: $_CONFIG['basedir'] = "";
//
$_CONFIG['basedir'] = "";

// Big files. If you have some very big files (>4GB), enable this for correct
// file size calculation.
// Default: $_CONFIG['large_files'] = false;
//
$_CONFIG['large_files'] = false;

// ImageMagick available on server
// Default: $_CONFIG['image_magick'] = false;
$_CONFIG['image_magick'] = true;

/***************************************************************************/
/*   TRANSLATIONS.                                                         */
/***************************************************************************/

$_TRANSLATIONS = array();

// English
$_TRANSLATIONS["en"] = array(
  "file_name" => "File name",
  "size" => "Size",
  "last_changed" => "Last changed",
  "unable_to_read_dir" => "Unable to read directory",
  "location" => "Location",
  "root" => "Root",
  "mobile_version" => "Mobile view",
  "standard_version" => "Standard view",
  "site" => "Website",
  "suppr" => "Suppr"
);

// French
$_TRANSLATIONS["fr"] = array(
  "file_name" => "Nom de fichier",
  "size" => "Taille",
  "last_changed" => "Ajout&eacute;",
  "unable_to_read_dir" => "Impossible de lire le dossier",
  "location" => "Localisation",
  "root" => "Racine",
  "mobile_version" => "Version mobile",
  "standard_version" => "Version standard",
  "site" => "Site",
  "suppr" => "Suppr"
);

include_once('images.php');

/***************************************************************************/
/*   HERE COMES THE CODE.                                                  */
/*   DON'T CHANGE UNLESS YOU KNOW WHAT YOU ARE DOING ;)                    */
/***************************************************************************/

//
// The class that displays images (icons and thumbnails)
//
class ImageServer
{
  //
  // Checks if an image is requested and displays one if needed
  //
  public static function showImage()
  {
    global $_IMAGES;
    if(isset($_GET['img']))
    {
      if(strlen($_GET['img']) > 0)
      {
        $mtime = gmdate('r', filemtime($_SERVER['SCRIPT_FILENAME']));
        $etag = md5($mtime.$_SERVER['SCRIPT_FILENAME']);
        
        if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $mtime)
          || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag)) 
        {
          header('HTTP/1.1 304 Not Modified');
          return true;
        }
        else {
          header('ETag: "'.$etag.'"');
          header('Last-Modified: '.$mtime);
          header('Content-type: image/gif');
          if(isset($_IMAGES[$_GET['img']]))
            print base64_decode($_IMAGES[$_GET['img']]);
          else
            print base64_decode($_IMAGES["unknown"]);
        }
      }
      return true;
    }
    else if(isset($_GET['thumb']))
    {
      if(strlen($_GET['thumb']) > 0 && EncodeExplorer::getConfig('thumbnails') == true)
      {
        ImageServer::showThumbnail($_GET['thumb']);
      }
      return true;
    }
    return false;
  }
  
  public static function isEnabledPdf()
  {
    if(class_exists("Imagick"))
      return true;
    return false;
  }
  
  public static function openPdf($file)
  {
    if(!ImageServer::isEnabledPdf())
      return null;
      
    $im = new Imagick($file.'[0]');
    $im->setImageFormat( "png" );
    $str = $im->getImageBlob();
    $im2 = imagecreatefromstring($str);
    return $im2;
  }
  
  //
  // Creates and returns a thumbnail image object from an image file
  //
  public static function createThumbnail($file)
  {
    if(is_int(EncodeExplorer::getConfig('thumbnails_width')))
      $max_width = EncodeExplorer::getConfig('thumbnails_width');
    else
      $max_width = 200;
    
    if(is_int(EncodeExplorer::getConfig('thumbnails_height')))
      $max_height = EncodeExplorer::getConfig('thumbnails_height');
    else
      $max_height = 200;

    if(File::isPdfFile($file))
      $image = ImageServer::openPdf($file);
    else
      $image = ImageServer::openImage($file);
    if($image == null)
      return;
      
    imagealphablending($image, true);
    imagesavealpha($image, true);
      
    $width = imagesx($image);
    $height = imagesy($image);
      
    $new_width = $max_width;
    $new_height = $max_height;
    if(($width/$height) > ($new_width/$new_height))
      $new_height = $new_width * ($height / $width);
    else 
      $new_width = $new_height * ($width / $height);   
    
    if($new_width >= $width && $new_height >= $height)
    {
      $new_width = $width;
      $new_height = $height;
    }
    
    $new_image = ImageCreateTrueColor($new_width, $new_height);
    imagealphablending($new_image, true);
    imagesavealpha($new_image, true);
    $trans_colour = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
    imagefill($new_image, 0, 0, $trans_colour);
    
    imagecopyResampled ($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    return $new_image;
  }
  
  //
  // Function for displaying the thumbnail.
  // Includes attempts at cacheing it so that generation is minimised.
  //
  public static function showThumbnail($file)
  {
    if(filemtime($file) < filemtime($_SERVER['SCRIPT_FILENAME']))
      $mtime = gmdate('r', filemtime($_SERVER['SCRIPT_FILENAME']));
    else
      $mtime = gmdate('r', filemtime($file));
      
    $etag = md5($mtime.$file);
    
    if ((isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $mtime)
      || (isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag)) 
    {
      header('HTTP/1.1 304 Not Modified');
      return;
    }
    else
    {
      header('ETag: "'.$etag.'"');
      header('Last-Modified: '.$mtime);
      if(EncodeExplorer::getConfig("image_magick") == true){
        $size = max(EncodeExplorer::getConfig('thumbnails_width'), EncodeExplorer::getConfig('thumbnails_height'));
        $cmd = 'convert -define jpeg:size='.$size.'x'.$size.' "'.dirname(__file__).'/'.$file.'" -auto-orient -thumbnail '.$size.'x'.$size.' -unsharp 0x.5 JPG:-';
        header("Content-Type: image/jpeg" ); 
        passthru($cmd, $retval);  
      } else {
        header('Content-Type: image/png');
        $image = ImageServer::createThumbnail($file);
        imagepng($image);
      }
    }
  }
  
  //
  // A helping function for opening different types of image files
  //
  public static function openImage ($file) 
  {
      $size = getimagesize($file);
      switch($size["mime"])
      {
      case "image/jpeg":
        $im = imagecreatefromjpeg($file);
      break;
      case "image/gif":
        $im = imagecreatefromgif($file);
      break;
      case "image/png":
        $im = imagecreatefrompng($file);
      break;
      default:
        $im=null;
      break;
      }
      return $im;
  }
}

//
// Dir class holds the information about one directory in the list
//
class Dir
{
  var $name;
  var $location;

  //
  // Constructor
  // 
  function Dir($name, $location)
  {
    $this->name = $name;
    $this->location = $location;
  }

  function getName()
  {
    return $this->name;
  }

  function getNameHtml()
  {
    return htmlspecialchars($this->name);
  }

  function getNameEncoded()
  {
    return rawurlencode($this->name);
  }
}

//
// File class holds the information about one file in the list
//
class File
{
  var $name;
  var $location;
  var $size;
  //var $extension;
  var $type;
  var $modTime;

  //
  // Constructor
  // 
  function File($name, $location)
  {
    $this->name = $name;
    $this->location = $location;
    
    $this->type = File::getFileType($this->location->getDir(true, false, false, 0).$this->getName());
    $this->size = File::getFileSize($this->location->getDir(true, false, false, 0).$this->getName());
    $this->modTime = filemtime($this->location->getDir(true, false, false, 0).$this->getName());
  }

  function getName()
  {
    return $this->name;
  }

  function getNameEncoded()
  {
    return rawurlencode($this->name);
  }

  function getNameHtml()
  {
    return htmlspecialchars($this->name);
  }

  function getSize()
  {
    return $this->size;
  }

  function getType()
  {
    return $this->type;
  }
  
  function getModTime()
  {
    return $this->modTime;
  }

  //
  // Determine the size of a file
  // 
  public static function getFileSize($file)
  {
    $sizeInBytes = filesize($file);

    // If filesize() fails (with larger files), try to get the size from unix command line.
    if (EncodeExplorer::getConfig("large_files") == true || !$sizeInBytes || $sizeInBytes < 0) {
      $sizeInBytes=exec("ls -l '$file' | awk '{print $5}'");
    }
    return $sizeInBytes;
  }
  
  public static function getFileType($filepath)
  {
    /*
     * This extracts the information from the file contents.
     * Unfortunately it doesn't properly detect the difference between text-based file types.
     * 
    $mime_type = File::getMimeType($filepath);
    $mime_type_chunks = explode("/", $mime_type, 2);
    $type = $mime_type_chunks[1];
    */
    return File::getFileExtension($filepath);
  }
  
  public static function getFileMime($filepath)
  {
    $fhandle = finfo_open(FILEINFO_MIME);
    $mime_type = finfo_file($fhandle, $filepath);
    $mime_type_chunks = preg_split('/\s+/', $mime_type);
    $mime_type = $mime_type_chunks[0];
    $mime_type_chunks = explode(";", $mime_type);
    $mime_type = $mime_type_chunks[0];
    return $mime_type;
  }
  
  public static function getFileExtension($filepath)
  {
    return strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
  }
  
  function isImage()
  {
    $type = $this->getType();
    if($type == "png" || $type == "jpg" || $type == "gif" || $type == "jpeg")
      return true;
    return false;
  }
  
  function isPdf()
  {
    if(strtolower($this->getType()) == "pdf")
      return true;
    return false;    
  }
  
  public static function isPdfFile($file)
  {
    if(File::getFileType($file) == "pdf")
      return true;
    return false;
  }
  
  function isValidForThumb()
  {
    if($this->isImage() || ($this->isPdf() && ImageServer::isEnabledPdf()))
      return true;
    return false;
  }
}

class Location
{
  var $path;

  //
  // Split a file path into array elements
  // 
  public static function splitPath($dir)
  {
    $dir = stripslashes($dir);
    $path1 = preg_split("/[\\\\\/]+/", $dir);
    $path2 = array();
    for($i = 0; $i < count($path1); $i++)
    {
      if($path1[$i] == ".." || $path1[$i] == "." || $path1[$i] == "")
        continue;
      $path2[] = $path1[$i];
    }
    return $path2;
  }

  //
  // Get the current directory.
  // Options: Include the prefix ("./"); URL-encode the string; HTML-encode the string; return directory n-levels up
  // 
  function getDir($prefix, $encoded, $html, $up)
  {
    $dir = "";
    if($prefix == true)
      $dir .= "./";
    for($i = 0; $i < ((count($this->path) >= $up && $up > 0)?count($this->path)-$up:count($this->path)); $i++)
    {
      $temp = $this->path[$i];
      if($encoded)
        $temp = rawurlencode($temp);
      if($html)
        $temp = htmlspecialchars($temp);
      $dir .= $temp."/";
    }
    return $dir;
  }

  function getPathLink($i, $html)
  {
    if($html)
      return htmlspecialchars($this->path[$i]);
    else
      return $this->path[$i];
  }

  function getFullPath()
  {
    return (strlen(EncodeExplorer::getConfig('basedir')) > 0?EncodeExplorer::getConfig('basedir'):dirname($_SERVER['SCRIPT_FILENAME']))."/".$this->getDir(true, false, false, 0);
  }

  //
  // Set the current directory
  // 
  function init()
  {
    if(!isset($_GET['dir']) || strlen($_GET['dir']) == 0)
    {
      $this->path = $this->splitPath(EncodeExplorer::getConfig('starting_dir'));
    }
    else
    {
      $this->path = $this->splitPath($_GET['dir']);
    }
  }
  
  //
  // Checks if the current directory is below the input path
  //
  function isSubDir($checkPath)
  {
    for($i = 0; $i < count($this->path); $i++)
    {
      if(strcmp($this->getDir(true, false, false, $i), $checkPath) == 0)
        return true;
    }
    return false;
  }
}

class EncodeExplorer
{
  var $location;
  var $dirs;
  var $files;
  var $sort_by;
  var $sort_as;
  var $mobile;
  var $lang;
  
  //
  // Determine sorting, calculate space.
  // 
  function init()
  {
    $this->sort_by = "";
    $this->sort_as = "";
    if(isset($_GET["sort_by"]) && isset($_GET["sort_as"]))
    {
      if($_GET["sort_by"] == "name" || $_GET["sort_by"] == "size" || $_GET["sort_by"] == "mod")
        if($_GET["sort_as"] == "asc" || $_GET["sort_as"] == "desc")
        {
          $this->sort_by = $_GET["sort_by"];
          $this->sort_as = $_GET["sort_as"];
        }
    }
    if(strlen($this->sort_by) <= 0 || strlen($this->sort_as) <= 0)
    {
      $this->sort_by = "name";
      $this->sort_as = "desc";
    }
    
    
    global $_TRANSLATIONS;
    if(isset($_GET['lang']) && isset($_TRANSLATIONS[$_GET['lang']]))
      $this->lang = $_GET['lang'];
    else
      $this->lang = EncodeExplorer::getConfig("lang");
    
    $this->mobile = false;
    if(EncodeExplorer::getConfig("mobile_enabled") == true)
    {
      if((EncodeExplorer::getConfig("mobile_default") == true || isset($_GET['m'])) && !isset($_GET['s']))
        $this->mobile = true;
    }
  }

  //
  // Read the file list from the directory
  // 
  function readDir()
  {
    global $encodeExplorer;
    //
    // Reading the data of files and directories
    //
    if($open_dir = @opendir($this->location->getFullPath()))
    {
      $this->dirs = array();
      $this->files = array();
      while ($object = readdir($open_dir))
      {
        if($object != "." && $object != "..") 
        {
          if(is_dir($this->location->getDir(true, false, false, 0)."/".$object))
          {
            if(!in_array($object, EncodeExplorer::getConfig('hidden_dirs')))
              $this->dirs[] = new Dir($object, $this->location);
          }
          else if(!in_array($object, EncodeExplorer::getConfig('hidden_files')))
            $this->files[] = new File($object, $this->location);
        }
      }
      closedir($open_dir);
    }
    else
    {
      $encodeExplorer->setErrorString("unable_to_read_dir");;
    }
  }

  function sort()
  {
    if(is_array($this->files)){
      usort($this->files, "EncodeExplorer::cmp_".$this->sort_by);
      if($this->sort_as == "desc")
        $this->files = array_reverse($this->files);
    }
    
    if(is_array($this->dirs)){
      usort($this->dirs, "EncodeExplorer::cmp_name");
      if($this->sort_by == "name" && $this->sort_as == "desc")
        $this->dirs = array_reverse($this->dirs);
    }
  }

  function makeArrow($sort_by)
  {  
    if($this->sort_by == $sort_by && $this->sort_as == "asc")
    {
      $sort_as = "desc";
      $img = "arrow_up";
    }
    else
    {
      $sort_as = "asc";
      $img = "arrow_down";
    }

    if($sort_by == "name")
      $text = $this->getString("file_name");
    else if($sort_by == "size")
      $text = $this->getString("size");
    else if($sort_by == "mod")
      $text = $this->getString("last_changed");

    return "<a href=\"".$this->makeLink(false, false, $sort_by, $sort_as, null, $this->location->getDir(false, true, false, 0))."\">
      $text <img style=\"border:0;\" alt=\"".$sort_as."\" src=\"?img=".$img."\" /></a>";
  }
  
  function makeLink($switchVersion, $logout, $sort_by, $sort_as, $delete, $dir)
  {
    $link = "?";
    if($switchVersion == true && EncodeExplorer::getConfig("mobile_enabled") == true)
    {
      if($this->mobile == false)
        $link .= "m&amp;";
      else
        $link .= "s&amp;";
    }
    else if($this->mobile == true && EncodeExplorer::getConfig("mobile_enabled") == true && EncodeExplorer::getConfig("mobile_default") == false)
      $link .= "m&amp;";
    else if($this->mobile == false && EncodeExplorer::getConfig("mobile_enabled") == true && EncodeExplorer::getConfig("mobile_default") == true)
      $link .= "s&amp;";
      
    if($logout == true)
    {
      $link .= "logout";
      return $link;
    }
      
    if(isset($this->lang) && $this->lang != EncodeExplorer::getConfig("lang"))
      $link .= "lang=".$this->lang."&amp;";
      
    if($sort_by != null && strlen($sort_by) > 0)
      $link .= "sort_by=".$sort_by."&amp;";
      
    if($sort_as != null && strlen($sort_as) > 0)
      $link .= "sort_as=".$sort_as."&amp;";
    
    $link .= "dir=".$dir;
    if($delete != null)
      $link .= "&amp;del=".$delete;
    return $link;
  }

  function makeIcon($l)
  {
    $l = strtolower($l);
    return "?img=".$l;
  }

  function formatModTime($time)
  {
    $timeformat = "d.m.y H:i:s";
    if(EncodeExplorer::getConfig("time_format") != null && strlen(EncodeExplorer::getConfig("time_format")) > 0)
      $timeformat = EncodeExplorer::getConfig("time_format");
    return date($timeformat, $time);
  }

  function formatSize($size) 
  {
    $sizes = Array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB');
    $y = $sizes[0];
    for ($i = 1; (($i < count($sizes)) && ($size >= 1024)); $i++) 
    {
      $size = $size / 1024;
      $y  = $sizes[$i];
    }
    return round($size, 2)." ".$y;
  }
  
  //
  // Comparison functions for sorting.
  //
  
  public static function cmp_name($b, $a)
  {
    return strcasecmp($a->name, $b->name);
  }
  
  public static function cmp_size($a, $b)
  {
    return ($a->size - $b->size);
  }
  
  public static function cmp_mod($b, $a)
  {
    return ($a->modTime - $b->modTime);
  }
  
  //
  // The function for getting a translated string.
  // Falls back to english if the correct language is missing something.
  // 
  function getString($stringName)
  {
    $lang = $this->lang;
    global $_TRANSLATIONS;
    
    if(isset($_TRANSLATIONS[$lang]) && is_array($_TRANSLATIONS[$lang]) 
      && isset($_TRANSLATIONS[$lang][$stringName]))
      return $_TRANSLATIONS[$lang][$stringName];
    else if(isset($_TRANSLATIONS["en"]))// && is_array($_TRANSLATIONS["en"]) 
      //&& isset($_TRANSLATIONS["en"][$stringName]))
      return $_TRANSLATIONS["en"][$stringName];
    else
      return "Translation error";
  }
  
  //
  // The function for getting configuration values
  //
  public static function getConfig($name)
  {
    global $_CONFIG;
    if(isset($_CONFIG) && isset($_CONFIG[$name]))
      return $_CONFIG[$name];
    return null;
  }
  
  public static function setError($message)
  {
    global $_ERROR;
    if(isset($_ERROR) && strlen($_ERROR) > 0)
      ;// keep the first error and discard the rest
    else
      $_ERROR = $message;
  }
  
  function setErrorString($stringName)
  {
    EncodeExplorer::setError($this->getString($stringName));
  }

  //
  // Main function, activating tasks
  // 
  function run($location)
  {
    $this->location = $location;
    $this->readDir();
    $this->sort();
    $this->outputHtml();
  }

  //
  // Printing the actual page
  // 
  function outputHtml()
  {
    global $_ERROR;
    global $_START_TIME;
?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $this->getConfig('lang'); ?>" lang="<?php print $this->getConfig('lang'); ?>">
<head>
<meta name="viewport" content="width=device-width" />
<meta http-equiv="Content-Type" content="text/html; charset=<?php print $this->getConfig('charset'); ?>">
<link rel="stylesheet" type="text/css" href="browse.css" />
<!-- <meta charset="<?php print $this->getConfig('charset'); ?>" /> -->
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<?php
if(($this->getConfig('thumbnails') != null && $this->getConfig('thumbnails') == true && $this->mobile == false))
{ 
?>
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
<?php
  if(EncodeExplorer::getConfig("thumbnails") == true && $this->mobile == false)
  {
?>   
    $("a.thumb").hover(function(e){ //Hover-in
	  parent.displayThumb($(this).attr("href"),e);
    },
    function(){ //Hover-out
      parent.removeThumb();
    });

    $("a.thumb").mousemove(function(e){
      parent.positionThumb(e);
    });

    $("a.thumb").click(function(e){parent.removeThumb(); return true;});
<?php 
  }
?>
  });
//]]>                
</script>
 <?php 
}
?> 
<!-- Script perso permettant de récupérer le chemin vers le dossier -->
<script type="text/javascript" src="js/jquery.ajaxq.js"></script>
<script type="text/javascript" src="js/browse.js"></script>
</head>
<body class="<?php print ($this->mobile == true?"mobile":"standard");?>">
<?php 
//
// Print the error (if there is something to print)
//
if(isset($_ERROR) && strlen($_ERROR) > 0)
{
  print "<div id=\"error\">".$_ERROR."</div>";
}
?>
<div id="frame">
<!-- START: List table -->
<table class="table">
<?php 
if($this->mobile == false)
{
?>
<tr id="<?php $this->location->getDir(false, false, true, 0);?>" class="row one header">
  <td class="icon"> </td>
  <td class="name"><?php print $this->makeArrow("name");?></td>
  <td class="size"><?php print $this->makeArrow("size"); ?></td>
  <td class="changed"><?php print $this->makeArrow("mod"); ?></td>
  <td class="site"><?php print $this->getString("site"); ?></td>
  <td class="suppr"><?php print $this->getString("suppr"); ?></td>
</tr>
<?php 
}
?>
<tr class="row two">
  <td class="icon"><img alt="dir" src="?img=directory" /></td>
  <td colspan="<?php print (($this->mobile == true?3:5)); ?>" class="long">
    <a class="item" href="<?php print $this->makeLink(false, false, null, null, null, $this->location->getDir(false, true, false, 1)); ?>">..</a>
  </td>
</tr>
<?php
//
// Ready to display folders and files.
//
$row = 1;

//
// Folders first
//
if($this->dirs)
{
  foreach ($this->dirs as $dir)
  {
    $row_style = ($row ? "one" : "two");
    print "<tr class=\"row ".$row_style."\">\n";
    print "<td class=\"icon\"><img alt=\"dir\" src=\"?img=directory\" /></td>\n";
    print "<td class=\"name\" colspan=\"".($this->mobile == true?3:5)."\">\n";
    print "<a href=\"".$this->makeLink(false, false, null, null, null, $this->location->getDir(false, true, false, 0).$dir->getNameEncoded())."\" class=\"item dir\">";
    print $dir->getNameHtml();
    print "</a>\n";
    print "</td>\n";
    print "</tr>\n";
    $row =! $row;
  }
}

//
// Now the files
//
if($this->files)
{
  $count = 0;
  foreach ($this->files as $file)
  {
    $row_style = ($row ? "one" : "two");
    print "<tr id=\"".$file->getNameHtml()."\" class=\"row ".$row_style.(++$count == count($this->files)?" last":"")."\">\n";
    print "<td class=\"icon\"><img alt=\"".$file->getType()."\" src=\"".$this->makeIcon($file->getType())."\" /></td>\n";
    print "<td class=\"name\">\n";
    print "\t\t<a href=\"".$this->location->getDir(false, true, false, 0).$file->getNameEncoded()."\"";
    if(EncodeExplorer::getConfig('open_in_new_window') == true)
      print "target=\"_blank\"";
    print " class=\"item file";
    if($file->isValidForThumb())
      print " thumb";
    print "\">";
    print $file->getNameHtml();
    if($this->mobile == true)
    {
      print "<span class =\"size\">".$this->formatSize($file->getSize())."</span>";
    }
    print "</a>\n";
    print "</td>\n";
    if($this->mobile != true)
    {
      print "<td class=\"size\">".$this->formatSize($file->getSize())."</td>\n";
      print "<td class=\"changed\">".$this->formatModTime($file->getModTime())."</td>\n";
    }
	print "<td".($file->isImage()?" class=\"site pending\"":"")."></td>\n";
	print "<td".($file->isImage()?" class=\"icon suppr\"><img title=\"Supprimer la photo\" src=\"".$this->makeIcon("del")."\" /":"")."></td>\n";
    print "</tr>\n";
    $row =! $row;
  }
}

//
// The files and folders have been displayed
//
?>

</table>
<!-- END: List table -->
</div>

<!-- START: Info area -->
<div id="info">
<?php

if(EncodeExplorer::getConfig("mobile_enabled") == true)
{
  print "<a href=\"".$this->makeLink(true, false, null, null, null, $this->location->getDir(false, true, false, 0))."\">\n";
  print ($this->mobile == true)?$this->getString("standard_version"):$this->getString("mobile_version")."\n";
  print "</a> | \n";
}
?> 
</div>
<!-- END: Info area -->
</body>
</html>
  
<?php
  }
}

//
// This is where the system is activated. 
// We check if the user wants an image and show it. If not, we show the explorer.
//
$encodeExplorer = new EncodeExplorer();
$encodeExplorer->init();

if(!ImageServer::showImage())
{
  $location = new Location();
  $location->init();
	
  $encodeExplorer->run($location);
}
?>
