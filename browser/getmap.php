<?php
require_once "../include.php";
require_once "display.php";

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

if (isset($_GET['path']))
    $path = $_GET["path"];
else {
    $path = "";
}
$path = safeDirectory($path);

$m_db          = new mediaDB();
if ($path != "")
    $id        = $m_db->getFolderID($path);
else
    $id        = 1;
$elements_list = getFolderGeolocalizedElements($id, $m_db);
?>
<!DOCTYPE html>
<html>
<head>
<?php echo "<title>".$gal_title."</title>\n"; ?> 
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 
  <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
  <link rel="stylesheet" href="<?php echo $BASE_URL?>/css/layout.css" type="text/css" media="screen" charset="utf-8" />
  <link rel="stylesheet" href="<?php echo $BASE_URL?>/css/toplevelmenu.css" type="text/css" media="screen" />
  <script src="http://maps.google.com/maps/api/js?sensor=false" type="text/javascript"></script>
  <script src="<?php echo $BASE_URL?>/js/infobox_packed.js" type="text/javascript"></script>
  <script src="<?php echo $BASE_URL?>/js/jquery-1.4.2.min.js" type="text/javascript"></script>		
  <script src="<?php echo $BASE_URL?>/js/navigation.js" type="text/javascript"></script>
  <script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
<?php
$element = new mediaObject();
echo "    var latlng = new google.maps.LatLng(0,0);\n";
?>
    var myOptions = {
        zoom: 18,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.SATELLITE
    };
    var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
<?php
// Build map bound
$map_bounds = getFolderGeolocalizedBounds($id, $m_db);
echo "     var latlng_ne = new google.maps.LatLng(".$map_bounds['ne_lat'].",".$map_bounds['ne_lon'].");\n";
echo "     var latlng_sw = new google.maps.LatLng(".$map_bounds['sw_lat'].",".$map_bounds['sw_lon'].");\n";
echo "     var latlng_bound = new google.maps.LatLngBounds(latlng_sw, latlng_ne);\n";
echo "     map.fitBounds(latlng_bound);\n";
// Build element markers/info
$infoid = 0;
foreach($elements_list as $element_id) {
    $m_db->loadMediaObject($element, $element_id);
    if ($element->type == 'video') continue; // Videos are not geotagged
    echo "var LatLng$infoid = new google.maps.LatLng($element->latitude, $element->longitude);\n";
    echo "var contentString$infoid = '<div id=\"map_info\">'+\n";
    echo "\t'<h2>".$element->title."</h2>'+\n";
    echo "\t'<a href=\"$BASE_URL/browser/getresized.php?id=$element_id\">'+\n";
    echo "\t'<img src=\"$BASE_URL/browser/getthumb.php?id=$element_id\" alt=\"".$element->filename."\"/>'+\n";
    echo "\t'<p>Alt: ".round($element->altitude)."m<br />".$element->getSubTitle(true)."</p>'+\n";
    echo "\t'</a></div>';\n";
    echo "var marker$infoid = new google.maps.Marker({ position: LatLng$infoid, map: map, title:'".$element->title."', icon:'".getElementIcon($element_id, $m_db)."' });\n";
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
echo "<div id=\"map_content\">\n"; 
echo "<h1><a href=\"$BASE_URL/index.php\">".htmlentities($gal_title)."</a></h1>\n"; 

// Path dirs and link
displayFolderHierarchy($id, $m_db, false);
?>
<div id="map_canvas"></div>
</div>
</body>
</html>
