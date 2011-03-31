<?php
require_once "common_mobile.php";

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
  <link rel="stylesheet" href="css/jquery.mobile-1.0a3.css" />
  <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
  <script type="text/javascript" src="js/jquery.mobile-1.0a3.min.js"></script>
</head> 
<body> 
<div data-role="page" data-theme="a" data-back-btn-text="Retour">

	<div data-role="header">
		<h1><?php echo "$gal_title"; ?></h1>
        <a href="mobile.php" data-icon="home" data-iconpos="notext" class="ui-btn-right" title="Accueil">Accueil</a>
	</div><!-- /header -->

	<div data-role="content">	
        <?php displaySubFolderList($m_folder_id, $m_db) ?>
        <?php displayElementList($m_folder_id, $m_db) ?>
	</div><!-- /content -->

	<div data-role="footer">
		<h4>Gallerie Mobile v2.2.0 - C. Laury</h4>
	</div><!-- /footer -->
</div><!-- /page -->

</body>
</html>
