<?php

function js_encode($str)
{
    $remove_chars = array("\r\n", "\n", "\r");
    $output_str = str_replace($remove_chars, '', $str);
    return addslashes(utf8_encode($str));
}

function getFolderGeolocalizedCount($id, mediaDB &$db)
{    
    $result = $db->query("SELECT COUNT(*) FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;");
    if ($result === false) throw new Exception($db->error); else $row = $result->fetch_row();
    $result->free();

    return $row[0];
}

function getFolderGeolocalizedElements($id, mediaDB &$db)
{    
    $element_list = array();

    if ($id == -1) {
        $folders = $db->getSubFolders($id, true);
        foreach($folders as $folder_id) {
            $query = "SELECT id FROM media_objects WHERE longitude != 9999.0 AND folder_id = ".$folder_id." LIMIT 1;";
            $results = $db->query($query);
            if ($results === FALSE) throw new Exception($db->error);
            while($row = $results->fetch_row()) {
                $element_list[] = $row[0];
            }
            $results->free();
        }
    } else {
        $query = "SELECT id FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;";
        $results = $db->query($query);
        if ($results === false) throw new Exception($db->error);
        while($row = $results->fetch_row()) {
            $element_list[] = $row[0];
        }
        $results->free();
    }

    return $element_list;
}

function getElementIcon($tags)
{
    global $BASE_URL;

    $result = '';

    foreach($tags as $tag) {
        if (stristr($tag, 'Paysage') != false) {
            // No break here as if there is another tag for this picture -> use it
            $result = "$BASE_URL/images/markers/paysage.png";
        } else if (stristr($tag, 'Flore')   != false) {
            $result = "$BASE_URL/images/markers/flore.png";
            break;
        } else if (stristr($tag, 'Faune')   != false) {
            $result = "$BASE_URL/images/markers/faune.png";
            break;
        } else if (stristr($tag, 'Eglise')  != false) {
            $result = "$BASE_URL/images/markers/eglise.png";
            break;
        } else if (stristr($tag, 'Pano')    != false) {
            $result = "$BASE_URL/images/markers/panorama.png";
            break;
        } else if (stristr($tag, 'Portrait') != false) {
            $result = "$BASE_URL/images/markers/portrait.png";
            break;
        } else if (stristr($tag, 'Macro') != false) {
            // No break here as if there is another tag for this picture -> use it
            $result = "$BASE_URL/images/markers/flore.png";
        }
    }

    if ($result == '')
        $result = "$BASE_URL/images/markers/picture.png";

    return $result;
}

function getFolderGeolocalizedBounds($id, mediaDB &$db)
{    
    $longitude_min =  180.0;
    $longitude_max = -180.0;
    $latitude_min  =   90.0;
    $latitude_max  =  -90.0;

    $query = "";
    if ($id == -1)
        $query = "SELECT latitude, longitude FROM media_objects WHERE longitude != 9999.0;";
    else
        $query = "SELECT latitude, longitude FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;";
    $results = $db->query($query);
    if ($results === false) throw new Exception($db->error);
    while($row = $results->fetch_assoc()) {
        if ($row['longitude'] < $longitude_min) $longitude_min = $row['longitude'];
        if ($row['longitude'] > $longitude_max) $longitude_max = $row['longitude'];
        if ($row['latitude']  < $latitude_min)  $latitude_min  = $row['latitude'];
        if ($row['latitude']  > $latitude_max)  $latitude_max  = $row['latitude'];
    }
    $results->free();

    return array('sw_lat' => $latitude_min, 'sw_lon' => $longitude_min, 'ne_lat' => $latitude_max, 'ne_lon' => $longitude_max);
}

?>
