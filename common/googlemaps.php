<?php

function js_encode($str)
{
    $remove_chars = array("\r\n", "\n", "\r");
    $output_str = str_replace($remove_chars, '', $str);
    return addslashes(utf8_encode($str));
}

function getFolderGeolocalizedCount($id, mediaDB &$db = NULL)
{    
    $m_db = NULL;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $result = $m_db->query("SELECT COUNT(*) FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;");
    if ($result === false) throw new Exception($m_db->error); else $row = $result->fetch_row();
    $result->free();

    if ($db == NULL)
        $m_db->close();

    return $row[0];
}

function getFolderGeolocalizedElements($id, mediaDB &$db = NULL)
{    
    $m_db         = NULL;
    $element_list = array();

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $query = "";
    if ($id == 1) {
        $query = "SELECT id FROM media_folders WHERE parent_id != -1;";
        $results = $m_db->query($query);
        if ($results === FALSE) throw new Exception($m_db->error);
        while($row = $results->fetch_row()) {
            $folder_id = $row[0];
            $subquery = "SELECT id FROM media_objects WHERE longitude != 9999.0 AND folder_id = ".$folder_id." LIMIT 1;";
            $subresults = $m_db->query($subquery);
            if ($subresults === FALSE) throw new Exception($m_db->error);
            while($subrow = $subresults->fetch_row()) {
                $element_list[] = $subrow[0];
            }
            $subresults->free();
        }
        $results->free();
    } else {
        $query = "SELECT id FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;";
        $results = $m_db->query($query);
        if ($results === false) throw new Exception($m_db->error);
        while($row = $results->fetch_row()) {
            $element_list[] = $row[0];
        }
        $results->free();
    }

    if ($db == NULL)
        $m_db->close();

    return $element_list;
}

function getElementIcon($id, mediaDB &$db = NULL)
{
    global $BASE_URL;

    $result = '';

    $m_db = NULL;
    if ($db == NULL)
        $m_db = new mediaDB();
    else
        $m_db = $db;

    $tags = $m_db->query("SELECT name FROM media_tags WHERE media_id=$id;");
    if ($tags === false) throw new Exception($m_db->error);

    while($tag = $tags->fetch_assoc()) {
        if      (stristr($tag['name'], 'Paysage') != false) {
            // No break here as if there is another tag for this picture -> use it
            $result = "$BASE_URL/images/markers/paysage.png";
        } else if (stristr($tag['name'], 'Flore')   != false) {
            $result = "$BASE_URL/images/markers/flore.png";
            break;
        } else if (stristr($tag['name'], 'Faune')   != false) {
            $result = "$BASE_URL/images/markers/faune.png";
            break;
        } else if (stristr($tag['name'], 'Eglise')  != false) {
            $result = "$BASE_URL/images/markers/eglise.png";
            break;
        } else if (stristr($tag['name'], 'Pano')    != false) {
            $result = "$BASE_URL/images/markers/panorama.png";
            break;
        } else if (stristr($tag['name'], 'Portrait') != false) {
            $result = "$BASE_URL/images/markers/portrait.png";
            break;
        } else if (stristr($tag['name'], 'Macro') != false) {
            // No break here as if there is another tag for this picture -> use it
            $result = "$BASE_URL/images/markers/flore.png";
        }
    }
    $tags->free();

    if ($result == '')
        $result = "$BASE_URL/images/markers/picture.png";

    if ($db == NULL)
        $m_db->close();

    return $result;
}

function getFolderGeolocalizedBounds($id, mediaDB &$db = NULL)
{    
    $m_db          = NULL;
    $longitude_min =  180.0;
    $longitude_max = -180.0;
    $latitude_min  =   90.0;
    $latitude_max  =  -90.0;

    if ($db == NULL) $m_db = new mediaDB();
    else             $m_db = $db;

    $query = "";
    if ($id == 1)
        $query = "SELECT latitude, longitude FROM media_objects WHERE longitude != 9999.0;";
    else
        $query = "SELECT latitude, longitude FROM media_objects WHERE folder_id=$id AND longitude != 9999.0;";
    $results = $m_db->query($query);
    if ($results === false) throw new Exception($m_db->error);
    while($row = $results->fetch_assoc()) {
        if ($row['longitude'] < $longitude_min) $longitude_min = $row['longitude'];
        if ($row['longitude'] > $longitude_max) $longitude_max = $row['longitude'];
        if ($row['latitude']  < $latitude_min)  $latitude_min  = $row['latitude'];
        if ($row['latitude']  > $latitude_max)  $latitude_max  = $row['latitude'];
    }
    $results->free();

    if ($db == NULL)
        $m_db->close();

    return array('sw_lat' => $latitude_min, 'sw_lon' => $longitude_min, 'ne_lat' => $latitude_max, 'ne_lon' => $longitude_max);
}

?>
