<?php

function displaySubFolderList($id, mediaDB &$db)
{ 
    setlocale(LC_ALL, "fr_FR.utf8");

    $subfolder_list = $db->getSubFolders($id);

    if (count($subfolder_list) > 0) {
        echo "<ul data-role=\"listview\" data-theme=\"a\">\n";        
        foreach($subfolder_list as $subfolder) {
            $subfolder_title = htmlentities($db->getFolderTitle($subfolder));
            echo "<li><a href=\"mobile.php?path=".urlencode($db->getFolderPath($subfolder))."\" title=\"$subfolder_title\" >";
            echo "<img src=\"".getThumbnailPath($subfolder, true)."\" title=\"".$subfolder_title."\" />";
            echo "<h3>$subfolder_title</h3>";
            echo "<p>".$db->getFolderDate($subfolder)."</p>";
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
            echo "<ul data-role=\"listview\" data-theme=\"a\" id=\"gallery\">\n";
        }
        foreach($element_list as $current_id) {
            $element = new mediaObject();
            $db->loadMediaObject($element, $current_id);
            if ($element->type == 'picture') {
                echo "<li>";
                echo "<a href=\"".getResizedPath($current_id)."\" rel=\"external\" title=\"".htmlentities($element->title)."\">";
                echo "<img src=\"".getThumbnailPath($current_id)."\" title=\"".htmlentities($element->title)."\" alt=\"".htmlentities($element->title)."\" />";
                echo "<h3>".htmlentities($element->title)."</h3>";
                echo "<p>".htmlentities($element->getSubTitle(true))."</p>";
                echo "<p>".strftime('%e %B %Y %Hh%M', strtotime($element->originaldate))."</p>";
                echo "</a></li>\n";
            }
        }
        echo "</div>\n";
    }
}

