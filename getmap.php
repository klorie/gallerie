<?php
require_once "googlemaps.php";

if (isset($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
} else {
	header('HTTP/1.1 400 Bad Request');
	die('id was not specified');
}

$m_db = new mediaDB();
$elements_list = getFolderGeolocalizedElements($id, $m_db);
echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "<meta name=\"viewport\" content=\"initial-scale=1.0, user-scalable=no\" />\n";
echo "<style type=\"text/css\">\n";
echo "  html { height: 100% }\n";
echo "  body { height: 100%; margin: 0px; padding: 0px }\n";
echo "  #map_canvas { height: 100% }\n";
echo "</style>\n";
echo "<script type=\"text/javascript\"\n";
echo "    src=\"http://maps.google.com/maps/api/js?sensor=false\">\n";
echo "</script>\n";
echo "<script type=\"text/javascript\">\n";
echo "  function initialize() {\n";
$element = new mediaObject();
$m_db->loadMediaObject($element, $elements_list[0]);
echo "    var latlng = new google.maps.LatLng($element->latitude, $element->longitude);\n";
echo "    var myOptions = {\n";
echo "      zoom: 14,\n";
echo "      center: latlng,\n";
echo "      mapTypeId: google.maps.MapTypeId.SATELLITE\n";
echo "    };\n";
echo "    var map = new google.maps.Map(document.getElementById(\"map_canvas\"), myOptions);\n";
$infoid = 0;
foreach($elements_list as $element_id) {
    $m_db->loadMediaObject($element, $element_id);
    echo "var LatLng$infoid = new google.maps.LatLng($element->latitude, $element->longitude);\n";
    echo "var contentString$infoid = '<div id=\"content\"><div id=\"siteNotice\"></div>'+\n";
    echo "\t'<h1 id=\"firstHeading\" class=\"firstHeading\">".$element->title."</h1>'+\n";
    echo "\t'<div id=\"bodyContent\">'+\n";
    echo "\t'<img src=\"./getthumb.php?id=$element_id\" alt=\"".$element->filename."\"/>'+\n";
    echo "\t'<p>".$element->getSubTitle(true)."</p>'+\n";
    echo "\t'</div></div>';\n";
    echo "var infowindow$infoid = new google.maps.InfoWindow({ content: contentString$infoid });\n";
    echo "var marker$infoid = new google.maps.Marker({ position: LatLng$infoid, map: map, title:\"".$element->title."\" });\n";
    echo "google.maps.event.addListener(marker$infoid, 'click', function() { infowindow$infoid.open(map, marker$infoid); });\n";
    $infoid++;
}
echo "  }\n";
echo "</script>\n";
echo "</head>\n";
echo "<body onload=\"initialize()\">\n";
echo "  <div id=\"map_canvas\" style=\"width:100%; height:100%\"></div>\n";
echo "</body>\n";
echo "</html>\n";
?>
