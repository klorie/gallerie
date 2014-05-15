<?php

function displaySideMenu($id, mediaDB &$db)
{
    global $latest_album_count;
    setlocale(LC_ALL, "fr_FR.utf8");
    
    echo "<div data-role=\"panel\" id=\"nav-panel\" data-position-fixed=\"true\" data-theme=\"a\">\n";
    $folder_list = $db->getSubFolders($id);
    echo "<ul data-role=\"listview\" data-theme=\"a\" data-inset=\"false\">\n";
    echo "<li data-icon=\"delete\"><a href=\"#\" data-rel=\"close\">Fermer</a></li>\n";
    if ($id != -1) {
        if (getFolderGeolocalizedCount($id, $db) > 0) {
            echo "<li data-role=\"list-divider\">Outils</li>\n";
            echo "<li><a href=\"mobile.php?map=$id\">Carte</a></li>\n";
        }
        $neighbor_list = $db->getNeighborFolders($id);
        if (count($neighbor_list) > 0) {
            echo "<li data-role=\"list-divider\">Albums Voisins</li>\n";
            foreach($neighbor_list as $neighbor_id)
                echo "<li><a href=\"mobile.php?path=".urlencode($db->getFolderPath($neighbor_id))."\">".htmlentities($db->getFolderTitle($neighbor_id))."</a></li>\n";
        }
    }
    if (count($folder_list) > 0) {
        echo "<li data-role=\"list-divider\">Sous-Ablums</li>\n";
        foreach($folder_list as $folder_id)
            echo "<li><a href=\"mobile.php?path=".urlencode($db->getFolderPath($folder_id))."\">".htmlentities($db->getFolderTitle($folder_id))."</a></li>\n";
    }
    echo "<li data-role=\"list-divider\">Nouveaut&eacute;s</li>\n";
    $latestfolderlist = $db->getLatestUpdatedFolder($latest_album_count);
    foreach($latestfolderlist as $latestfolder)
        echo "<li><a href=\"mobile.php?path=".urlencode($db->getFolderPath($latestfolder))."\">".htmlentities($db->getFolderTitle($latestfolder))."</a></li>\n";
    echo "</ul>\n";
    echo "</div><!-- /panel -->\n";
}

function displaySubFolderList($id, mediaDB &$db)
{ 
    setlocale(LC_ALL, "fr_FR.utf8");

    $subfolder_list = $db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<ul data-role=\"listview\" data-inset=\"true\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($db->getFolderTitle($subfolder));
            echo "<li><a href=\"mobile.php?path=".urlencode($db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >";
            echo "<img src=\"".getThumbnailPath($subfolder, true)."\" title=\"".$subfolder_title."\" class=\"ui-li-thumb\" />";
            echo "<h3>$subfolder_title</h3>";
            echo "<p class=\"ui-li-aside\">".$db->getFolderDate($subfolder)."</p>";
            echo "<span class=\"ui-li-count\">".$db->getFolderElementsCount($subfolder, true)."</span>";
            echo "</a></li>\n";        
        }
        // Separate Directory list and Pictures
        if ($db->getFolderElementsCount($id) > 0) {
            echo "<li data-role=\"list-divider\" role=\"heading\">Photos<span class=\"ui-li-count\">".$db->getFolderElementsCount($id)."</span></li>\n";
        } else {
          echo "</ul>\n";
        }
    }
}

function displayElementList($id, mediaDB &$db)
{
    setlocale(LC_ALL, "fr_FR.utf8");

    $element_list   = $db->getFolderElements($id);
    $subfolder_list = $db->getSubFolders($id);

    if (count($element_list) > 0) {
        if (count($subfolder_list) == 0) {
            echo "<ul data-role=\"listview\" data-inset=\"true\">\n";
        }
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $db->loadMediaObject($element, $current_id);
            if ($element->type == 'picture') {
                echo "<li><a href=\"".getResizedPath($current_id)."\" rel=\"external\" title=\"".htmlentities($element->title)."\">";
                echo "<img src=\"".getThumbnailPath($current_id)."\" title=\"".htmlentities($element->title)."\" alt=\"".htmlentities($element->title)."\" class=\"ui-li-thumb\" />";
                echo "<h3>".htmlentities($element->title)."</h3>";
                echo "<p class=\"ui-li-aside\">".htmlentities($element->getSubTitle(true))."</p>";
                echo "<p>".strftime('%e %B %Y %Hh%M', strtotime($element->originaldate))."</p>";
                echo "</a></li>\n";
            }
        }
        echo "</ul>\n";
    }
}

function displayMapHeader($id, mediaDB &$db)
{
    echo "$(function() {\n";
    echo "  var mapheight = $('#page-content');\n";
    echo "  $('#map_canvas').css('height', mapheight.innerHeight() * 0.95);\n";  //($(window).height() - 100);\n";
    echo "  var latlng = new google.maps.LatLng(0,0);\n";
    echo "  var myOptions = {\n";
    echo "    zoom: 18,\n";
    echo "    center: latlng,\n";
    echo "    mapTypeId: google.maps.MapTypeId.SATELLITE\n";
    echo "  };\n";
    echo "  $('#map_canvas').gmap(myOptions);\n";
    $map_bounds = getFolderGeolocalizedBounds($id, $db);
    echo "  var latlng_ne = new google.maps.LatLng(".$map_bounds['ne_lat'].",".$map_bounds['ne_lon'].");\n";
    echo "  $('#map_canvas').gmap('addBounds', latlng_ne);\n";
    echo "  var latlng_sw = new google.maps.LatLng(".$map_bounds['sw_lat'].",".$map_bounds['sw_lon'].");\n";
    echo "  $('#map_canvas').gmap('addBounds', latlng_sw);\n";
    $element = new mediaObject();
    $elements_list = getFolderGeolocalizedElements($id, $db);
    $infoid = 0;
    foreach($elements_list as $element_id) {
        $db->loadMediaObject($element, $element_id);
        if ($element->type == 'video') continue; // Videos are not geotagged
        echo "var contentString$infoid = '<div>'+\n";
        echo "\t'<h2>".js_encode($element->title)."</h2>'+\n";
        echo "\t'<a href=\"".getResizedPath($element_id)."\">'+\n";
        echo "\t'<img src=\"".getThumbnailPath($element_id)."\" alt=\"".$element->filename."\"/>'+\n";
        echo "\t'<p>Alt: ".round($element->altitude)."m<br />".$element->getSubTitle(true)."</p>'+\n";
        echo "\t'</a></div>';\n";
        // echo "var marker$infoid = new google.maps.Marker({ position: LatLng$infoid, map: map, draggable: true, title:'".js_encode($element->title)."', icon:'".getElementIcon($element->tags)."' });\n";
        echo "$('#map_canvas').gmap('addMarker', { 'position' : '$element->latitude,$element->longitude' }).click(function() {\n";
        echo "  $('#map_canvas').gmap('openInfoWindow', { 'maxWidth': 200, 'content' : contentString$infoid }, this);\n";
        echo "});\n";
        $infoid++;
    }
    echo "});\n";
}

function displayMap($id, mediaDB &$db)
{
    setlocale(LC_ALL, "fr_FR.utf8");
    
    echo "<div id=\"map_canvas\" style=\"width: 100%; height: 800px\"></div>\n";
}
