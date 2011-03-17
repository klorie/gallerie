<?php
require_once "common_browser.php";
require_once "googlemaps.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

if (isset($_GET['path']))
    $path = $_GET["path"];
else {
	header('HTTP/1.1 400 Bad Request');
	die('path was not specified');
    
}
$path = safeDirectory($path);

$m_db          = new mediaDB();
$id            = $m_db->getFolderID($path);
$elements_list = getFolderGeolocalizedElements($id, $m_db);
?>
<!DOCTYPE html>
<html>
<head>
<?php echo "<title>".$gal_title."</title>\n"; ?> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="css/layout.css" type="text/css" media="screen" charset="utf-8" />
  <script src="http://maps.google.com/maps/api/js?sensor=false" type="text/javascript"></script>
  <script src="js/infobox_packed.js" type="text/javascript"></script>
  <script src="js/jquery-1.4.2.min.js" type="text/javascript"></script>		
  <script src="js/jquery.tools-1.2.1.min.js" type="text/javascript"></script>
  <script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
<?php
$element = new mediaObject();
$m_db->loadMediaObject($element, $elements_list[0]);
echo "    var latlng = new google.maps.LatLng($element->latitude, $element->longitude);\n";
?>
    var myOptions = {
        zoom: 14,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.SATELLITE
    };
    var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
<?php
$infoid = 0;
foreach($elements_list as $element_id) {
    $m_db->loadMediaObject($element, $element_id);
    echo "var LatLng$infoid = new google.maps.LatLng($element->latitude, $element->longitude);\n";
    echo "var contentString$infoid = '<div id=\"map_info\">'+\n";
    echo "\t'<h2>".$element->title."</h2>'+\n";
    echo "\t'<a href=\"./getresized.php?id=$element_id\">'+\n";
    echo "\t'<img src=\"./getthumb.php?id=$element_id\" alt=\"".$element->filename."\"/>'+\n";
    echo "\t'<p>Alt: ".round($element->altitude)."m<br />".$element->getSubTitle(true)."</p>'+\n";
    echo "\t'</a></div>';\n";
    echo "var marker$infoid = new google.maps.Marker({ position: LatLng$infoid, map: map, title:'".$element->title."', icon:'".getElementIcon($element)."' });\n";
    echo "var infooptions$infoid = { content: contentString$infoid, alignBottom: true, pixelOffset: new google.maps.Size(0, -35), boxClass: \"map_info\", closeBoxMargin: \"5px\" };\n";
    echo "var infobox$infoid = new InfoBox(infooptions$infoid);\n";
    echo "google.maps.event.addListener(marker$infoid, 'click', function() { infobox$infoid.open(map, marker$infoid); });\n";
    $infoid++;
}
?>
    });
</script>
</head>
<body>
<?php
displayTopFoldersMenu($m_db);
echo "<div id=\"content\">\n"; 
echo "<h1>".htmlentities($gal_title)."</h1>\n"; 

// Path dirs and link
displayFolderHierarchy($id, $m_db, false);
?>
<div id="map_canvas"></div>
<div class="clearfix"></div> 
<br/>
<?php
$mtime = explode(' ', microtime());
$totaltime = $mtime[0] + $mtime[1] - $starttime;
$today = date('Y/m/d \a\t H:i:s');
printf('Page generated in %.3f seconds on %s', $totaltime, $today);
?>
</div>
<ul class="submenu">
<li>Gallerie v2.0.0 - H. Raffard &amp; C. Laury - 2011/03/15</li>
</ul>
<br clear="all" /> 
</body>
</html>
