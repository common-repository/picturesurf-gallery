<?
	define('PICTURESURF_HOST', 'www.picturesurf.org');
	define('PICTURESURF_URL', 'http://' . PICTURESURF_HOST . '/');
	define('PICTURESURF_URL_IMG', PICTURESURF_URL . '/i/');
	define('PICTURESURF_URL_GAL', PICTURESURF_URL . 'gallery_data/');
	define('PICTURESURF_URL_GAL_DATA', PICTURESURF_URL_GAL . 'data/');
	define('PICTURESURF_URL_GAL_WIDGET', PICTURESURF_URL_GAL . 'widget/');
	/*
	 * Local settings
	 */
	define('LOCAL_IMAGEPAGES', true);
	define('LOCAL_SHOW_EMAIL_SHARE', true);
	define('LOCAL_SHOW_FACEB_SHARE', true);
	/*
	 * WP for "wordpress\wp-content\uploads"
	 * or
	 * PLUGIN for "wordpress\wp-content\plugins\PicturesurfGallery\data"
	 */ 
	define('LOCAL_SAVE_MODE', 'PLUGIN');
	define('LOCAL_SAVE_IMG_FOLDER', 'picturesurf');
?>