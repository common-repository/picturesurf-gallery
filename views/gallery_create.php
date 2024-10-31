<?
	session_start();
	include str_replace('views', '', dirname(__FILE__)) . '/includes/config.php';
	$path = $_GET['path'];
	$blogpath = $_GET['blogpath'];
	$defstr = array_key_exists('GalleryDefaults', $_SESSION)?stripslashes($_SESSION["GalleryDefaults"]):'';
	$datastr = array_key_exists('GalleryDataString', $_SESSION)?stripslashes($_SESSION["GalleryDataString"]):'';
	$html = file_get_contents(PICTURESURF_URL_GAL . 'ig_html.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<link media="all" type="text/css" rel="stylesheet" href="<?=PICTURESURF_URL_GAL?>css/gallery-all.css"/>
	<link media="all" type="text/css" rel="stylesheet" href="<?=PICTURESURF_URL_GAL?>css/gallery-main.css"/>
	<link media="all" type="text/css" rel="stylesheet" href="<?=PICTURESURF_URL_GAL?>css/gallery-blocks.css"/>
</head>
<body>
<script type="text/javascript">
<?
echo 'var G_BlogPath = "'.$blogpath.'";'."\r\n";
echo 'var G_GalleryDefaults = "'.rawurlencode($defstr).'";'."\r\n";
echo 'var G_GallerySession = "'.rawurlencode($datastr).'";'."\r\n";
?>
</script>

<?=$html?>

</body>
</html>