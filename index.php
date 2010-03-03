<?php
// Start PHP code
require_once "common.php";
require_once "config.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];
$cwd = dirname($_SERVER["PHP_SELF"]);

$path = $_GET["path"];
$path = safeDirectory($path);
$dirlist = getFileList($path);

$cache = "./cache/$path/index.html";

if ($dir_thumb_mode != "RANDOM" && file_exists($cache) && 
    filemtime($cache) > filemtime("./index.php") &&
    filemtime($cache) > filemtime("./common.php") && 
    filemtime($cache) > filemtime("./config.php") &&
    filemtime_r($cache) > filemtime("./gallery/$path/.") &&
    filemtime_r($cache) > filemtime("./thumbnails/$path/.")) {
  readfile($cache);
} else {
  ob_start();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html  xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head> 
<?php echo "<title>".$gal_title."</title>"; ?> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  <link rel="stylesheet" href="css/layout.css" type="text/css" media="screen" charset="utf-8" />
  <link rel="stylesheet" href="css/prettyPhoto.css" type="text/css" media="screen" charset="utf-8" /> 
  <script type="text/javascript" src="js/jquery-1.4.2.min.js"></script>		
  <script src="js/jquery.prettyPhoto.js" type="text/javascript" charset="utf-8"></script> 
  <script src="js/jquery.imageLoader.js" type="text/javascript" charset="utf-8"></script>
  <script src="http://lite.piclens.com/current/piclens.js" type="text/javascript"></script>
  <!--[if IE 6]>
    <script src="js/DD_belatedPNG_0.0.7a-min.js"></script>
    <script>
      DD_belatedPNG.fix('.pp_left,.pp_right,a.pp_close,a.pp_arrow_next,a.pp_arrow_previous,.pp_content,.pp_middle'); 
    </script>
  <![endif]--> 
  <script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
      $("a[rel^='prettyPhoto']").prettyPhoto({
        animationSpeed: 'fast', /* fast/slow/normal */
	padding: 40, /* padding for each side of the picture */
	opacity: 0.65, /* Value betwee 0 and 1 */
	showTitle: true, /* true/false */
	allowresize: true, /* true/false */
	counter_separator_label: '/', /* Separator for gallery counter 1 "of" 2 */
	theme: '<?php echo $gal_theme; ?>' /* light_rounded / dark_rounded / light_square / dark_square */
      });
    jQuery('.dynamic-thumbnail').loadImages();
    });
  </script>		

<?php
// Create thumbnail in current directory
if (!file_exists("./thumbnails/$path")) { mkdir("./thumbnails/$path"); }

// Issue Cooliris header
if (count($dirlist[file]) != 0) {  
   echo "  <link rel=\"alternate\" href=\"".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."\" type=\"application/rss+xml\" title=\"\" id=\"gallery_bis\" />\n";
}
echo "</head>\n";
echo "<body>\n";

echo "<div id=\"wrap\">\n";
echo "<div id=\"sidebar\">\n";
echo "<h3>Sous-Albums</h3>\n";

//output <ul tag only if something inside to be w3c compliant
if (count($dirlist[dir]) > 0) {
  echo "<ul class=\"menu\">\n";
  // Build menu with all sub-directories
  foreach($dirlist[dir] as $file) {
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".htmlentities($file['title'])."</a></li>\n";
  }
  echo "</ul>\n";
}
if ($path != "") {
  echo "<h3>Albums Voisins</h3>\n";
  if (dirname($path) === ".") 
    $neighborpath = "";
  else
    $neighborpath = dirname($path);
  $neighborlist = getFileList($neighborpath);
  if (count($neighborlist[dir]) > 0) {
    echo "<ul class=\"menu\">\n";
    foreach($neighborlist[dir] as $file) {
      if (strpos($file['fullname'], $path) !== false) continue;
      echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".htmlentities($file['title'])."</a></li>\n";
    }
  }
}
echo "</div>\n";
echo "<div id=\"content-container\">\n"; 
echo "<ul class=\"submenu\">\n"; 
echo "<li><a href=\"".$_SERVER["PHP_SELF"]."\"><b>Accueil</b></a></li>\n"; 
// Build menu with only top-level directories
$topdirlist = getFileList("");
foreach($topdirlist[dir] as $file) {
    echo "<li><a href=\"".$_SERVER["PHP_SELF"]."?path=".$file['fullname']."\" >".htmlentities($file['title'])."</a> </li>\n";
}
echo "</ul>\n"; 
echo "<div id=\"content\">\n"; 
echo "<h1>".htmlentities($gal_title)."</h1>\n"; 

// Path dirs and link
echo "<h3 id=\"gallery\">\n";
if ($path != "") {
  echo "<a href=\"".$_SERVER["PHP_SELF"]."\">Accueil</a>";
  $pathArr = explode("/", $path);
  for ($i = 0; $i < count($pathArr); $i++) {
    // Get directory title
    $dirPath  = implode("/", array_slice($pathArr, 0, $i+1));
    $dirList  = getFileList(dirname($dirPath));
    $dirTitle = "";
    foreach($dirList[dir] as $file) {
      if (strcmp($file['name'], $pathArr[$i]) == 0)
        $dirTitle = $file['title'];
    }
    $dirLink = getPathLink($dirPath);
    echo "/<a href=\"$dirLink\">".htmlentities($dirTitle)."</a>";
  }
}
if (count($dirlist[file]) != 0) {
  echo "      [ <a href=\"javascript:PicLensLite.start({feedUrl:'http://".$_SERVER["SERVER_NAME"].$cwd."/photos.rss.php?path=".$path."', delay:6});\">Diaporama</a> ]\n";
}

echo "</h3>\n";

// Show list of subfolders, output <ul tag only if something inside to be w3c compliant
if (count($dirlist[dir]) > 0) {
  echo "<ul class=\"galleryfolder clearfix\">\n";
  foreach($dirlist[dir] as $file) {
    echo "<li><div class=\"galleryfolderalign\"><a href=\"".$_SERVER["PHP_SELF"]."?path=".urlencode($file['fullname'])."\" title=\"".htmlentities($file['title'])."\" >";
    $thumb = GetThumbsForDir($file['fullname'], $dir_thumb_mode);  
    echo "<img src=\"".$thumb."\" class=\"galleryfolderimg\" alt=\"".$file['name']."\"/>";
    echo "<br />".htmlentities($file['title'])."</a></div></li>\n";
  }
  echo "</ul>\n";
  //Separate Directory list and Pictures
  echo "<h3></h3>\n";
}

// Show list of pictures
if (count($dirlist[file]) > 0) {
  echo "<ul class=\"gallery clearfix\">\n";
  foreach($dirlist[file] as $file) {
    // Don't show album thumbnails
    if (strpos($file['fullname'], "00ALBUM") !== false) continue;
    $tmp_fthumb = substr($file['name'], 0, strlen($file['name'])-3).$thumb_create; 
    if ($resize_preview === 1) 
      echo "<li><a href=\"./resize.php?src=./gallery/".htmlentities($file['fullname'])."&w=".$resize_width."&h=0\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($file['subtitle'])."T&eacute;l&eacute;charger: &lt;a href=./gallery/".$file['fullname']."&gt;".htmlentities($file['name'])."&lt;/a&gt;\">";
    else
      echo "<li><a href=\"./gallery/".htmlentities($file['fullname'])."\" rel=\"prettyPhoto[gallery]\" title=\"".htmlentities($file['subtitle'])."T&eacute;l&eacute;charger: &lt;a href=./gallery/".$file['fullname']."&gt;".htmlentities($file['name'])."&lt;/a&gt;\">";

      echo "<div class=\"dynamic-thumbnail\" src=\"./getthumb.php?dir=".$file['dir']."&file=".$file['name']."\" title=\"".htmlentities($file['title'])."\"></div></a></li>\n";
//      echo "<div class=\"dynamic-thumbnail\" src=\"./thumbnails/".$file['dir']."/".$tmp_fthumb."\" title=\"".htmlentities($file['title'])."\"></div></a></li>\n";
  }
  echo "</ul>\n";
  echo "<h3></h3>\n";
}
if ($path != "") {
       $pathArr = explode("/", $path, -1);
       $link = implode("/", $pathArr);
       if ($link == "") {
                // we're already in $baseDir, so skip the file
                $back_link = $_SERVER["PHP_SELF"];
        } else {
                $back_link = $_SERVER["PHP_SELF"]."?path=".$link;
        }
        echo "<br /><br /><a href=\"".$back_link."\">Niveau sup&eacute;rieur</a>";
}

?>

<div class="clearfix"></div> 
<br/>
<?php
$mtime = explode(' ', microtime());
$totaltime = $mtime[0] + $mtime[1] - $starttime;
printf('Page loaded in %.3f seconds.', $totaltime);
?>
</div>
<ul class="submenu">
<li>Gallerie v1.6 - H. Raffard &amp; C. Laury - 2010/03/03</li>
</ul>
<br clear="all" /> 
</div>
</div>
</body> 
</html>
<?php
  $page = ob_get_contents();
  ob_end_clean();

  if (!(file_exists("./cache/$path") && is_dir("./cache/$path")))
    mkdir("./cache/$path", 0777, true);

  file_put_contents($cache, $page);
  echo $page;
}
?>
