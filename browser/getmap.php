<?php
require_once "../include.php";
require_once "header.php";
require_once "display.php";

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
    $id        = -1;
$elements_list = getFolderGeolocalizedElements($id, $m_db);
?>
<!DOCTYPE html>
<html>
<head>
<?php displayHeader('map', NULL); ?>
  <script type="text/javascript" charset="utf-8">
    $(function() {
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
    echo "\tvar LatLng$infoid = new google.maps.LatLng($element->latitude, $element->longitude);\n";
    echo "\tvar contentString$infoid = '<div id=\"map_info\">'+\n";
    echo "\t\t'<h2>".js_encode($element->title)."</h2>'+\n";
    echo "\t\t'<img src=\"$BASE_URL/".getThumbnailPath($element_id)."\" alt=\"".$element->filename."\"/>'+\n";
    echo "\t\t'<p>Altitude: ".round($element->altitude)."m<br />".$element->getSubTitle(true)."</p>'+\n";
    echo "\t\t'</div>';\n";
    echo "\tvar marker$infoid = new google.maps.Marker({ position: LatLng$infoid, map: map, draggable: true, title:'".js_encode($element->title)."', icon:'".getElementIcon($element->tags)."' });\n";
    echo "\tvar infowindow$infoid = new google.maps.InfoWindow({ content: contentString$infoid });\n";
    echo "\tgoogle.maps.event.addListener(marker$infoid, 'click', function() { infowindow$infoid.open(map, marker$infoid); });\n";
    $infoid++;
}
?>
});
</script>
</head>
<body>
<?php
displaySideMenu($id, $m_db);
echo "<div id=\"map_content\">\n"; 
echo "<h1><a href=\"$BASE_URL/index.php\">".htmlentities($gal_title)."</a></h1>\n"; 

// Path dirs and link
displayFolderHierarchy($id, $m_db, false);
?>
<div id="map_canvas"></div>
</div>
</body>
</html>
