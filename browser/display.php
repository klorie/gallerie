<?php

function getStringHTML($str)
{
    if (mb_detect_encoding($str, 'UTF-8', true) == TRUE) {
        return htmlentities($str, ENT_COMPAT| ENT_XHTML, 'UTF-8');
    } else {
        return htmlentities($str, ENT_COMPAT| ENT_XHTML, 'ISO-8859-15');
    }
}

function getElementTooltip(mediaObject &$element)
{
    $tooltip = "";
    if (count($element->tags) > 0) {
        $tooltip .= "<i>";
        $tagline = "";
        foreach($element->tags as $tag) {
            if ($tagline != "")
                $tagline .= ", ";
            $tagline .= getStringHTML($tag);
        }
        $tooltip .= $tagline."</i><br />";
    } else if (($element->type == 'movie') && ($element->duration != ''))
        $tooltip .= "<br /><i>".$element->duration."</i>";

    return $tooltip;
}

function displayTagSelector(mediaDB &$db)
{
    $tag_list = $db->getAvailableTags();

    if (count($tag_list) > 0) {
        echo "<h2>S&eacute;lectionnez un ou plusieurs mots-clefs:</h2>\n";
        echo "<form action=\"index.php\" method=\"get\">\n";
        echo '<select name="param[]" data-placeholder="Choisissez une ou plusieurs cat&eacute;gories..." multiple class="chosen" style="width: 50%">'."\n";
        foreach($tag_list as $current_tag) {
            echo "\t<option value=\"$current_tag\">$current_tag</option>\n";
        }
        echo "</select>\n";
        echo "<input type=\"hidden\" name=\"browse\" value=\"tags\" />\n";
        echo "<button onclick=\"submit();\" style=\"padding: 0; border: 0; background: 0;\"><img style=\"height: 3em; align: bottom;\" src=\"images/apply.jpg\"/></button>\n";
        echo "</form>\n";
    }
}

function displayTagElements($tag_array, mediaDB &$db)
{
    if (count($tag_array) == 0)
        return false;

    $element_list = $db->getElementsByTags($tag_array);

    foreach($element_list as $current_id) {
        $element = new mediaObject();
        $db->loadMediaObject($element, $current_id);
        if ($element->type == 'picture') {
            echo "<a href=\"".getResizedPath($current_id)."\" data-ngthumb=\"".getThumbnailPath($current_id)."\" ";
            echo "data-ngdesc=\"".getElementTooltip($element).getStringHTML($element->getSubTitle())."\" >";
            echo getStringHTML($element->title)."</a>\n";
        }
    }
}

function displayElementList($id, mediaDB &$db)
{
    global $thumbs_per_page;

    setlocale(LC_ALL, "fr_FR.utf8");

    $element_list = $db->getFolderElements($id);

    foreach($element_list as $current_id) {
        $element = new mediaObject();
        $db->loadMediaObject($element, $current_id);
        if ($element->type == 'picture') {
            echo "<a href=\"".getResizedPath($current_id)."\" data-ngthumb=\"".getThumbnailPath($current_id)."\" ";
            echo "data-ngdesc=\"".getElementTooltip($element).getStringHTML($element->getSubTitle())."\" >";
            echo getStringHTML($element->title)."</a>\n";
        }
    }
}

function displaySubFolderList($id, mediaDB &$db)
{ 
    $subfolder_list = $db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = getStringHTML($db->getFolderTitle($subfolder));
            echo "<a href=\"\" data-ngthumb=\"".getThumbnailPath($subfolder, true)."\" ";
            echo "data-ngkind=\"album\" ";
            echo "data-ngdesc=\"".$db->getFolderDate($subfolder)."<br />".$db->getFolderElementsCount($subfolder, true)." images\" ";
            echo "data-ngdest=\"index.php?path=".urlencode($db->getFolderPath($subfolder))."\">";
            echo $subfolder_title."</a>\n";
        }
    }
}

function displayYearFolderList(mediaDB &$db)
{
    $yearfolder_list = $db->getFolderYears();
    foreach($yearfolder_list as $yearfolder) {
        echo "<a href=\"\" data-ngthumb=\"images/blackthumb.jpg\" ";
        echo "data-ngkind=\"album\" ";
        echo "data-ngdesc=\"".$db->getYearElementsCount($yearfolder)." images\" ";
        echo "data-ngdest=\"index.php?browse=date&year=".$yearfolder."\" style=\"font-size:200%;\">";
        echo "$yearfolder</a>\n";
    }

}

function displayYearFolders($year, mediaDB &$db)
{
    $folder_list = $db->getFoldersForYear($year);
    foreach($folder_list as $folder) {
        $folder_title = getStringHTML($db->getFolderTitle($folder));
        echo "<a href=\"\" data-ngthumb=\"".getThumbnailPath($folder, true)."\" ";
        echo "data-ngkind=\"album\" ";
        echo "data-ngdesc=\"".$db->getFolderDate($folder)."<br />".$db->getFolderElementsCount($folder, true)." images\" ";
        echo "data-ngdest=\"index.php?path=".urlencode($db->getFolderPath($folder))."\">";
        echo $folder_title."</a>\n";
    }
}

function displayFolderHierarchy($id, mediaDB &$db, $show_slide_map_link = true)
{
    global $BASE_URL;
    global $disable_dynamic;

    echo "<h2>\n";
    if ($id != -1) {
        echo "<a href=\"$BASE_URL/index.php\">Accueil</a>";
        $folderhierarchy = $db->getFolderHierarchy($id);
        foreach($folderhierarchy as $fhier) {
            if ($fhier == -1) continue; // Discard top-level
            echo "/<a href=\"$BASE_URL/index.php?path=".urlencode($db->getFolderPath($fhier))."\">".getStringHTML($db->getFolderTitle($fhier))."</a>";
        }
    }
    $path = $db->getFolderPath($id);
    if ($db->getFolderElementsCount($id) > 0) {
        if ($disable_dynamic == false)
            echo " <a href=\"$BASE_URL/browser/download.php?id=$id\"><img src=\"$BASE_URL/images/save.png\" title=\"T&eacute;l&eacute;charger les images\" alt=\"T&eacute;l&eacute;charger les images\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    }
    if (($show_slide_map_link == true) and (getFolderGeolocalizedCount($id, $db) > 0))
        echo " <a href=\"$BASE_URL/browser/getmap.php?path=$path\"><img src=\"$BASE_URL/images/googlemaps.png\" title=\"Carte\" alt=\"Carte\" height=\"32\" align=\"middle\" border=\"0\" /></a>\n";
    echo "</h2>\n";

}

/** Display a side menu containing folder-related elements
    - Latest modified folders
    - Neighbour folders (if any)
    - Filters
*/
function displaySideMenu($id, mediaDB &$db)
{
    global $BASE_URL;
    global $disable_dynamic;
    global $latest_album_count;

    // Display menu icon
    echo "<ul id=\"side_navigation\">\n"; 
    echo "  <li class=\"other_folders\">\n";
    // Side folder or Latest Folders
    if ($id != -1) {
        // Side folders
        $neighborlist = $db->getNeighborFolders($id);
        if (count($neighborlist) > 0) {
            echo "  <div><h3>Albums Voisins</h3>\n";
            foreach($neighborlist as $neighbor) {
                $neighbor_title = getStringHTML($db->getFolderTitle($neighbor));
                echo "    <a href=\"$BASE_URL/index.php?path=".urlencode($db->getFolderPath($neighbor))."\" title=\"$neighbor_title\" >$neighbor_title</a>\n";
            }
            echo "  </div>\n";
        }
    }

    // Sub folders (not for toplevel)
    if ($id != -1) {
        $subfolder_list = $db->getSubFolders($id);
        if (count($subfolder_list) > 0) {
            echo "  <div><h3>Sous-Albums</h3>\n";        
            foreach($subfolder_list as $subfolder) {
                $subfolder_title = getStringHTML($db->getFolderTitle($subfolder));
                echo "    <a href=\"$BASE_URL/index.php?path=".urlencode($db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >$subfolder_title</a>\n";
            }
            echo "  </div>\n";
        }
    }
    // Latest folders
    echo "  <div><h3>Nouveaut&eacute;s</h3>\n";
    $latestfolderlist = $db->getLatestUpdatedFolder($latest_album_count);
    foreach($latestfolderlist as $latestfolder) {
        $latestfolder_title = getStringHTML($db->getFolderTitle($latestfolder));
        echo "    <a href=\"$BASE_URL/index.php?path=".urlencode($db->getFolderPath($latestfolder))."\" title=\"$latestfolder_title\" >$latestfolder_title</a>\n";
    }
    echo "  </div>\n";
    // Browse by...
    if ($disable_dynamic == false) {
        echo "  <div><h3>Filtres</h3>\n";
        echo "     <a href=\"$BASE_URL/index.php?browse=tags\" title=\"Mots-Clefs\">Mots-Clefs</a>\n";
        echo "     <a href=\"$BASE_URL/index.php?browse=date\" title=\"Date\">Date</a>\n";
        echo "  </div>\n";
        echo "  </li>\n";
    }
    // Top level googlemap
    if ($id == -1) {
        echo "  <li class=\"googlemaps\">\n";
        echo "  <h3>Cartographie</h3>\n";
        echo "  <a href=\"$BASE_URL/browser/getmap.php\">Voir les photos sur une carte</a>\n";
        echo "  </li>\n";
    }
    echo "</ul>\n"; 
}

function displayFooter()
{
    global $gallery_release_tag;
    echo "<ul class=\"submenu\">\n";
    echo "<li>Gallerie v$gallery_release_tag - C. Laury</li>\n";
    echo "</ul>\n";
    echo "<br clear=\"all\" />\n";
}
?>
