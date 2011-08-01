<?php
require_once "include.php";
require_once "mobile/display.php";

if (isset($_GET['path']))
    $path = $_GET["path"];
else
    $path = "";
$path = safeDirectory($path);
$m_db        = new mediaDB();
$m_folder_id = $m_db->getFolderID($path);
if ($m_folder_id == -1)
    $m_folder_id = 1; // If path not found, go back to home page
?>
<!DOCTYPE html> 
<html> 
  <head> 
<?php echo "<title>".$gal_title."</title>\n"; ?> 
  <link rel="stylesheet" href="css/jquery.mobile-1.0b1.min.css" />
  <link rel="stylesheet" href="css/mobile_layout.css" />
  <script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
  <script type="text/javascript" src="js/jquery.mobile-1.0b1.min.js"></script>
</head> 
<body> 
<div data-role="page" data-theme="a" data-back-btn-text="Retour">

	<div data-role="header">
		<h1><?php echo "$gal_title"; ?></h1>
        <a href="mobile.php" data-icon="home" data-iconpos="notext" class="ui-btn-right" title="Accueil">Accueil</a>
	</div><!-- /header -->

	<div data-role="content">	
        <?php displaySubFolderList($m_folder_id, $m_db) ?>
        <?php if (getFolderGeolocalizedCount($m_folder_id, $m_db) > 0) {
                echo "<ul data-role=\"listview\" data-theme=\"a\">\n";        
                echo "<li>";
                echo "<a href=\"$BASE_URL/mobile/getmap.php?path=$path\" rel=\"external\" title=\"Carte\"><img src=\"$BASE_URL/images/googlemaps.png\" title=\"Carte\" alt=\"Carte\" class=\"ui-li-icon\" />";
                echo "Carte";
                echo "</a></li></ul>\n";
        }?>
        <?php displayElementList($m_folder_id, $m_db) ?>
	</div><!-- /content -->

	<div data-role="footer">
		<h1>Gallerie Mobile v2.3.0 - C. Laury</h1>
	</div><!-- /footer -->
</div><!-- /page -->

</body>
</html>
