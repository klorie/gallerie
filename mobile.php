<?php
require_once "include.php";
require_once "mobile/display.php";

if (isset($_GET['path'])) {
    $mode = 'folder';
    $path = $_GET["path"];
} elseif (isset($_GET['map'])) {
    $mode = 'map';
    $map  = $_GET["map"];
} else {
    $mode = 'folder';
    $path = "";
}

$m_db        = new mediaDB();
if ($mode == 'folder') {
    $path        = safeDirectory($path);
    $m_folder_id = $m_db->getFolderID($path);
}

global $gallery_release_tag;
?>
<!DOCTYPE html> 
<html> 
  <head> 
<?php echo "<title>".$gal_title."</title>"; ?> 
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.css" />
  <link rel="stylesheet" href="css/jquery.mobile.custom.css" />
  <script src="http://maps.google.com/maps/api/js?sensor=true" type="text/javascript"></script>
  <script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
  <script src="http://code.jquery.com/mobile/1.4.2/jquery.mobile-1.4.2.min.js"></script>
  <script src="js/jquery.ui.map.full.min.js" type="text/javascript"></script>
  <script type="text/javascript">
  </script>
</head> 
<body> 
<div data-role="page">
    <?php if ($mode == 'folder') displaySideMenu($m_folder_id, $m_db); ?>
	<div data-role="header" data-position="fixed">
        <?php if ($mode == 'folder') 
                echo "<a href=\"#nav-panel\" data-icon=\"bars\" data-iconpos=\"notext\" title=\"Menu\">Menu</a>\n";
              else
                echo "<a href=\"mobile.php?path=".urlencode($m_db->getFolderPath($map))."\" data-icon=\"back\" data-iconpos=\"notext\" title=\"Retour\">Retour</a>\n";
        ?>
		<h1><?php echo "$gal_title"; ?></h1>
        <a href="mobile.php" data-icon="home" data-iconpos="notext" title="Accueil">Accueil</a>
	</div><!-- /header -->

	<div data-role="content">
        <?php if ($mode == 'folder') {    
                displaySubFolderList($m_folder_id, $m_db);
                displayElementList($m_folder_id, $m_db);
              } else {
                displayMap($map, $m_db);
              } ?>
	</div><!-- /content -->

	<div data-role="footer">
		<h1>Gallerie Mobile v<?php echo $gallery_release_tag; ?> - C. Laury</h1>
	</div><!-- /footer -->
</div><!-- /page -->

</body>
</html>
