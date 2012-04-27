<?php

class mediaDB extends mysqli
{
    function init_database()
    {
        // Method which initialize the database with proper structure
        $query  = "CREATE TABLE IF NOT EXISTS media_objects (id INTEGER PRIMARY KEY AUTO_INCREMENT, folder_id INTEGER, type TEXT, ";
        $query .= "title TEXT, filesize INTEGER, duration TEXT, lastmod DATETIME, filename TEXT, download_path TEXT, thumbnail TEXT, resized TEXT, ";
        $query .= "camera TEXT, focal INTEGER, lens TEXT, fstop TEXT, shutter TEXT, iso INTEGER, originaldate DATETIME, ";
        $query .= "width INTEGER, height INTEGER, lens_is_zoom INTEGER, longitude REAL, latitude REAL, altitude REAL);";
        if ($this->query($query) === FALSE)
            die('-E- Failed query - '.$query.':'.$this->error);
        $query  = "CREATE TABLE IF NOT EXISTS media_folders (id INTEGER PRIMARY KEY AUTO_INCREMENT, parent_id INTEGER, title TEXT, ";
        $query .= "lastmod DATETIME, originaldate DATETIME, thumbnail TEXT, foldername TEXT);";
        if ($this->query($query) === FALSE)
            die('-E- Failed query - '.$query.':'.$this->error);
        if ($this->query('CREATE TABLE IF NOT EXISTS media_tags (id INTEGER PRIMARY KEY AUTO_INCREMENT, name TEXT, media_id INTEGER);') === FALSE) 
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_tag_name ON media_tags(name(255));') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error.$this->errno);
        if (($this->query('CREATE INDEX media_tag_media_id ON media_tags(media_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_folders_parent_id ON media_folders(parent_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_folders_foldername ON media_folders(foldername(255));') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_objects_folder_id ON media_objects(folder_id);') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
        if (($this->query('CREATE INDEX media_objects_filename ON media_objects(filename(255));') === FALSE) and ($this->errno != 1061))
            die('-E- Failed query:'.$this->error);
    }

    function __construct()
    {   
        global $database_host, $database_name, $database_user, $database_pwd;
    
        parent::__construct($database_host, $database_user, $database_pwd, $database_name);
        if (mysqli_connect_error()) {
            print $database_host.'/'.$database_user.'/'.$database_pwd.'/'.$database_name;
            die("-E-   Failed to open database : ".mysqli_connect_error());
        }
    }

    function findMediaObjectID(mediaObject &$media)
    {
        // Return ID in database if the same object is already present (same filename, same folder)
        // return -1 on failure
        $query = "SELECT id FROM media_objects WHERE folder_id = ";
        if ($media->folder != NULL)
            $query .= $media->folder->db_id;
        else
            $query .= "-1";
        $query .= " AND filename = '".$this->escape_string($media->filename)."'";

        $result = $this->query($query);

        if ($result == NULL)
            return -1;
        else
            return $result;
    }

    function storeMediaObject(mediaObject &$media, $update=false)
    {
        $store_id = -1;
        if ($update == true)
            $store_id = $this->findMediaObjectID($media);
        if ($store_id != -1)
            $query = 'REPLACE INTO media_objects (id, folder_id, type, title, filesize, lastmod, filename, thumbnail, resized, ';
        else
            $query = 'INSERT INTO media_objects (folder_id, type, title, filesize, lastmod, filename, thumbnail, resized, ';
        $query .= 'download_path, focal, lens, fstop, shutter, iso, originaldate, width, height, lens_is_zoom, ';
        $query .= 'camera, duration, longitude, latitude, altitude) VALUES (';
        if ($store_id != -1)
            $query .= $store_id. ', ';
        if ($media->folder != NULL) {
            $query .= $media->folder->db_id. ', ';
        } else
            $query .= ' -1, ';
        $query .= "'".$this->escape_string($media->type)         . "', ";
        $query .= "'".$this->escape_string($media->title)        . "', ";
        $query .= $media->filesize         . ', ';
        $query .= "'".$this->escape_string($media->lastmod)      . "', ";
        $query .= "'".$this->escape_string($media->filename)     . "', ";
        $query .= "'".$this->escape_string($media->thumbnail)    . "', ";
        $query .= "'".$this->escape_string($media->resized)      . "', ";
        $query .= "'".$this->escape_string($media->download_path). "', ";
        $query .= $media->focal            . ', ';
        $query .= "'".$this->escape_string($media->lens)         . "', ";
        $query .= "'".$this->escape_string($media->fstop)        . "', ";
        $query .= "'".$this->escape_string($media->shutter)      . "', ";
        $query .= $media->iso              . ', ';
        $query .= "'".$this->escape_string($media->originaldate) . "', ";
        $query .= $media->width            . ', ';
        $query .= $media->height           . ', ';
        $query .= $media->lens_is_zoom     . ', ';
        $query .= "'".$this->escape_string($media->camera)       . "', ";
        $query .= "'".$this->escape_string($media->duration)     . "', ";
        $query .= $media->longitude        . ', ';
        $query .= $media->latitude         . ', ';
        $query .= $media->altitude         . ');';
        if ($this->query($query) === FALSE) {
            print("-E- Failed query: $query:".$this->error);
        } else {
            $media->db_id = $this->insert_id;
        }
        // Process tags
        if ($store_id != -1) {
            // Clean old tags
            if ($this->query("DELETE FROM media_tags WHERE media_id=$store_id;") === FALSE)
                print $this->error;
        }
        foreach ($media->tags as $tag) {
            $query = "REPLACE INTO media_tags (media_id, name) VALUES ($media->db_id, '".$this->escape_string($tag)."');";
            if ($this->query($query) === FALSE) {
                print("-E-  Failed query was: $query:".$this->error);
            }
        }
    }

    function loadMediaObject(mediaObject &$media, $id)
    {
        $result = $this->query("SELECT * FROM media_objects WHERE id=$id;");

        if ($result === FALSE) {
            throw new Exception($this->error);
        } else {
            $row = $result->fetch_assoc();
            $result->free();
            $media->db_id         = $id;
            $media->folder_id     = $row['folder_id'];
            $media->title         = $row['title'];
            $media->type          = $row['type'];
            $media->camera        = $row['camera'];
            $media->filesize      = $row['filesize'];
            $media->lastmod       = $row['lastmod'];
            $media->filename      = $row['filename'];
            $media->thumbnail     = $row['thumbnail'];
            $media->resized       = $row['resized'];
            $media->download_path = $row['download_path'];
            $media->focal         = $row['focal'];
            $media->lens          = $row['lens'];
            $media->fstop         = $row['fstop'];
            $media->shutter       = $row['shutter'];
            $media->iso           = $row['iso'];
            $media->originaldate  = $row['originaldate'];
            $media->width         = $row['width'];
            $media->height        = $row['height'];
            $media->lens_is_zoom  = $row['lens_is_zoom'];
            $media->duration      = $row['duration'];
            $media->longitude     = $row['longitude'];
            $media->latitude      = $row['latitude'];
            $media->altitude      = $row['altitude'];
        }
        // Load corresponding tags
        $results = $this->query("SELECT name FROM media_tags WHERE media_id=$id;");
        if ($results === FALSE)
            throw new Exception($this->error);
        $media->tags = array();
        while($row = $results->fetch_assoc()) {
            $media->tags[] = $row['name'];
        }
        $results->free();
    }

    function findMediaFolderID(mediaFolder &$media)
    {
        // Return ID in database if the same object is already present (same name, same parent)
        // return -1 on failure
        $query = "SELECT id FROM media_folders WHERE parent_id = ";
        if ($media->parent != NULL)
            $query .= $media->parent->db_id;
        else
            $query .= "-1";
        $query .= " AND foldername = '".$this->escape_string($media->name)."'";

        $result = $this->query($query);

        if ($result == NULL)
            return -1;
        else
            return $result;        
    }

    function storeMediaFolder(mediaFolder &$media, $update=false)
    {
        @session_start();
        $_SESSION['nbfolders'] += 1;
        $_SESSION['progress'] = floor(($_SESSION['nbfolders'] / $_SESSION['totalfolders']) * 90) + 9;
        $_SESSION['status'] = "Storing ".$media->name." (".$_SESSION['progress']."%) ...";
        session_commit();
        $store_id = -1;
        if ($update == true)
            $store_id = $this->findMediaFolderID($media);

        if ($store_id != -1)
            $query = 'REPLACE INTO media_folders (id, parent_id, title, lastmod, originaldate, foldername, thumbnail) VALUES (';
        else
            $query = 'INSERT INTO media_folders (parent_id, title, lastmod, originaldate, foldername, thumbnail) VALUES (';
        if ($store_id != -1)
            $query .= $store_id.', ';
        if ($media->parent == NULL)
            $query .= "-1, ";
        else
            $query .= $media->parent->db_id.", ";
        $query .= "'".$this->escape_string($media->title)."', ";
        $query .= "'".$this->escape_string($media->lastmod)."', ";
        $query .= "'".$this->escape_string($media->originaldate)."', ";
        $query .= "'".$this->escape_string($media->name)."', ";
        $query .= "'".$this->escape_string($media->thumbnail)."');";
        if ($this->query($query) === FALSE) {
            print("-E-  Failed query: $query\n");
            throw new Exception($this->error);
        } else {
            $media->db_id = $this->insert_id;
        }
        if (count($media->subfolder) > 0) {
            foreach($media->subfolder as $subfolder)
                $this->storeMediaFolder($subfolder, $update);
        }
        if (count($media->element) > 0) {
            foreach($media->element as $element)
                $this->storeMediaObject($element, $update);
        }
    }

    function loadMediaFolder(mediaFolder &$media, $id, $depth = 1)
    {
        $result = $this->query("SELECT * FROM media_folders WHERE id=$id;");

        if ($result === FALSE) {
            throw new Exception($this->error);
        } else {
            $media->db_id        = $id;
            $media->title        = $result['title'];
            $media->lastmod      = $result['lastmod'];
            $media->originaldate = $result['originaldate'];
            $media->name         = $result['foldername'];
            $media->thumbnail    = $result['thumbnail'];
        }
        // Fetch subfolders if required
        if ($depth > 0) {
            $depth--;
            $results = $this->query("SELECT id FROM media_folders WHERE parent_id=$id;");
            if ($results === FALSE)
                throw new Exception($this->error);
            while($row = $results->fetch_assoc()) {
                if ($row['id'] != -1) {
                    $subfolder = new mediaFolder($media);
                    $this->loadMediaFolder($subfolder, $row['id'], $depth);
                    $media->subfolder[] = $subfolder;
                }
            }
            $results->free();
        }
        // Fetch elements
        $results = $this->query("SELECT id FROM media_objects WHERE folder_id=$id;");
        if ($results === FALSE) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            if ($row['id'] != -1) {
                $element = new mediaObject($media);
                $this->loadMediaObject($element, $row['id']);
                $media->element[] = $element;
            }
        }
        $results->free();
    }

    function getFolderID($path)
    {
        // Top folder get db_id = 1 by construction
        if ($path == "") return 1;
        // Find folder ID in DB according to the path given (relative to the gallery)
        $path_array = explode('/', $path);
        $parent_id  = 1;
        $folder_id  = 1;

        foreach($path_array as $current_path_level) {
            $parent_id = $folder_id;
            $result = $this->query("SELECT id FROM media_folders WHERE parent_id=$parent_id AND foldername='".$this->escape_string($current_path_level)."';");
            if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_row();
            $result->free();
            if ($row[0] != NULL)
                $folder_id = $row[0];
            else
                return -1;
        }
        return $folder_id;
    }

    function getFolderPath($id)
    {
        // Return folder (which id is given in arg) full path
        $parent_id   = -1;
        $folder_path = "";
        $result = $this->query("SELECT parent_id, foldername FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        $parent_id = $row['parent_id'];
        if ($row['foldername'] != "")
            $folder_path = $row['foldername'];
        while($parent_id != -1) {
            $result = $this->query("SELECT parent_id, foldername FROM media_folders WHERE id=$parent_id;");
            if ($result === false) throw new Exception($this->error); else $row = $result->fetch_assoc();
            $parent_id = $row['parent_id'];
            if ($row['foldername'] != "")
                $folder_path = $row['foldername'].'/'.$folder_path; 
        }
        return $folder_path;
    }

    function getNeighborFolders($id)
    {
        $neighbor_array = array();
        $result         = $this->query("SELECT parent_id FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_row();
        $result->free();
        $parent_id = $row[0];
        // Returns array of id of neighbor folders
        $results = $this->query("SELECT id FROM media_folders WHERE parent_id=$parent_id;");
        if ($results === FALSE) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            if ($row['id'] != $id)
                $neighbor_array[] = $row['id'];
        }
        $results->free();
        return $neighbor_array;
    }

    function getFolderTitle($id)
    {
        // Return folder (which id is given in arg) title
        $result = $this->query("SELECT title FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['title'];
    }

    function getFolderName($id)
    {
        // Return folder (which id is given in arg) name
        $result = $this->query("SELECT foldername FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return $row['foldername'];
    }

    function getFolderDate($id)
    {
        setlocale(LC_ALL, "fr_FR.utf8");
        // Return folder (which id is given in arg) date
        $result = $this->query("SELECT originaldate FROM media_folders WHERE id=$id;");
        if ($result === FALSE) throw new Exception($this->error); else $row = $result->fetch_assoc();
        return strftime("%e %B %Y", strtotime($row['originaldate']));
    }

    function getLatestUpdatedFolder($nb_latest)
    {
        $latest_array = array();
        // Return array of id of latest updated folders
        $results = $this->query("SELECT id FROM media_folders ORDER BY lastmod DESC;");
        if ($results === FALSE) throw new Exception($this->error);
        while(($row = $results->fetch_assoc()) && (count($latest_array) < $nb_latest)) {
            if ($row['id'] != 1)
                $latest_array[] = $row['id'];
        }
        $results->free();
        return $latest_array;
    }

    function getFolderHierarchy($id)
    {
        $folder_array[] = $id;
        // Return array of folders from top level up to id
        $result = $this->query("SELECT parent_id FROM media_folders WHERE id=$id;");
        if ($result === false) throw new Exception($this->error); else $row = $result->fetch_assoc();
        $parent_id = $row['parent_id'];
        while($parent_id != -1) {
            array_unshift($folder_array, $parent_id);
            $result = $this->query("SELECT parent_id FROM media_folders WHERE id=$parent_id;");
            if ($result === false) throw new Exception($this->error); else $row = $result->fetch_assoc();
            $parent_id = $row['parent_id'];
        }
        return $folder_array;
    }

    function getSubFolders($id)
    {
        global $reverse_subalbum_sort;

        $sub_array = array();
        // Returns array of id of neighbor folders
        $query = "SELECT id FROM media_folders WHERE parent_id=$id";
        if ($id == 1)                         $query .= " ORDER BY foldername ASC;";
        else if ($reverse_subalbum_sort != 0) $query .= " ORDER BY foldername DESC;";
        else                                  $query .= " ORDER BY foldername ASC;";
        $results = $this->query($query);
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $sub_array[] = $row['id'];
        }
        $results->free();
        return $sub_array;
    }

    function getFolderElements($id)
    {
        $element_list = array();
        // Returns array of id of folder elements
        $results = $this->query("SELECT id FROM media_objects WHERE folder_id=$id ORDER BY filename ASC;");
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $element_list[] = $row['id'];
        }
        $results->free();
        return $element_list;
    }

    function getFolderElementsCount($id, $recurse=false)
    {
        // Returns number of elements inside this folder (recursively if wanted)
        $results = $this->query("SELECT COUNT(*) FROM media_objects WHERE folder_id=$id;");
        if ($results === false) throw new Exception($this->error); else $row = $results->fetch_row();
        $count = $row[0];
        if ($recurse == true) {
            $subfolders = $this->getSubFolders($id);
            foreach($subfolders as $subfolder) {
                $count += $this->getFolderElementsCount($subfolder, true);
            }
        }
        return $count;
    }

    function getElementsByTags($tag_array)
    {
        $element_list = array();
        // Returns array of id of elements which have the given tag(s) in their description
        $query_str = "SELECT media_tags.media_id FROM media_tags ";
        if (count($tag_array) == 1) {
            $query_str .= "WHERE media_tags.name='".$tag_array[0]."'";
        } else {
            foreach (range(1, count($tag_array) - 1) as $tag_idx) {
                $query_str .= "LEFT JOIN media_tags AS media".$tag_idx." ON media_tags.media_id = media".$tag_idx.".media_id ";
            }
            $query_str .= "WHERE media_tags.name='".$tag_array[0]."' ";
            foreach (range(1, count($tag_array)- 1) as $tag_idx) {
                $query_str .= "AND media".$tag_idx.".name='".$tag_array[$tag_idx]."' ";
            }
        }
        $results = $this->query($query_str.";");
        // print($query_str);
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $element_list[] = $row['media_id'];
        }
        $results->free();
        return $element_list; 
    }

    function getAvailableTags()
    {
        $tag_list = array();

        $results = $this->query("SELECT DISTINCT name FROM media_tags ORDER BY name;");
        if ($results === false) throw new Exception($this->error);
        while($row = $results->fetch_assoc()) {
            $tag_list[] = $row['name'];
        }
        $results->free();
        return $tag_list;
    }
}

?>
