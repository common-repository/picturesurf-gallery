<?
	session_start();
	include str_replace('views', '', dirname(__FILE__)) . '/includes/config.php';
	$path = $_GET['path'];
	$blogpath = $_GET['blogpath'];
	$blogkey = '';
	$html = file_get_contents(PICTURESURF_URL_GAL.'ig_logreg_html.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<link media="all" type="text/css" rel="stylesheet" href="<?=PICTURESURF_URL_GAL?>css/gallery-all.css"/>
	<link media="all" type="text/css" rel="stylesheet" href="<?=PICTURESURF_URL_GAL?>css/gallery-blocks.css"/>
	<link media="all" type="text/css" rel="stylesheet" href="<?=PICTURESURF_URL_GAL?>css/gallery-logreg.css"/>
	<script src="<?=PICTURESURF_URL_GAL?>js/mootools1.2.js" type="text/javascript"></script>
	<script src="<?=PICTURESURF_URL_GAL?>js/utils.js" type="text/javascript"></script>
	<script src="<?=PICTURESURF_URL_GAL?>js/ig_logreg.js" type="text/javascript"></script>
</head>
<body>
<script type="text/javascript">
<?
echo 'var G_BlogPath = "' . $blogpath . '";' . "\r\n";
echo 'var G_BlogSettings = ' . $_SESSION['GallerySettingsJSON'] . ';' . "\r\n";
?>
</script>

<?=$html?>

</body>
</html>