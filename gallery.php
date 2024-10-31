<?
/*
Plugin Name: Picturesurf Gallery
Plugin URI: http://www.picturesurf.org/gallery/#get/wordpress
Description: <strong>PLEASE GO TO <a href="http://www.picturesurf.org/gallery/#get/wordpress">http://www.picturesurf.org/gallery</a> for the latest plugin!</strong> This plugin is switched off and is no longer supported by us!
Version: 1.2
Author: AlanEdge, dzenkovich
*/
/*
  Copyright 2008 Picturesurf.org

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA


*/
ob_start();
$GalleryInstance = new PicturesurfGallery();
/**
 * WP Gallery plugin class
 *
 * @package	Gallery WP plug-in
 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
 * @copyright Copyright (c) 2008, Picturesurf.org
 */
class PicturesurfGallery
{
	var $version = "1.2";
	var $wpdb;
	var $includesDir;
	var $viewsDir;
	var $dataDir;
	var $uploadDir;
	var $backupDir;
	var $pluginUrl;
	var $actionsClass;
	var $thumbModes = array('ST' , 'SQ'); //Warning! First should be the grid thumbnail mode!
	var $settings;
	/*
	 * Gallery plugin constructor class
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function PicturesurfGallery ()
	{
		return; //this plugin should not work!
				
		/* in case of slow connections attempt to extend script time for copy function
		   in PicturesurfGalleryActions->registerImage will not work for safe_mode=on though */
		set_time_limit(120);
		ini_set('pcre.backtrack_limit', 10000000);
		ini_set('allow_url_fopen', 1);
		global $wpdb, $wp_the_query;
		$this->wpdb = &$wpdb;
		$this->includesDir = dirname(__FILE__) . "/includes/";
		$this->viewsDir = dirname(__FILE__) . "/views/";		
		$this->blogUrl = get_option('siteurl');
		$dir = array_pop(explode('/wp-content/', str_replace('\\', '/', dirname(__FILE__))));
		$this->pluginUrl = get_option('siteurl') . '/wp-content/' . $dir . '/';
		$this->dataDir = dirname(__FILE__) . "/data/";
		require_once ($this->includesDir . 'GalleryActions.php');
		require_once ($this->includesDir . 'config.php');
		require_once($this->includesDir . 'json.php');
		
		//fill in settings
		$sets = get_option('GallerySettings');
		if(is_array($sets))
		{
			$sets = array_pop($sets);
		}
		if($sets)
		{
			$this->settings = unserialize($sets);
		}
		else
		{
			$this->settings = array(
				'IsImagePages' => LOCAL_IMAGEPAGES,
				'SaveMode' => LOCAL_SAVE_MODE,
				'SaveUploadsFolder' => LOCAL_SAVE_IMG_FOLDER,
				'IsShowEmail' => LOCAL_SHOW_EMAIL_SHARE,
				'IsShowFaceB' => LOCAL_SHOW_FACEB_SHARE
			);
		}
		
		if($this->settings['SaveMode']=='WP')
		{
			$uploadPath = wp_upload_dir();
			$this->dataDir = $uploadPath['basedir'] . '/' . $this->settings['SaveUploadsFolder'] . '/';
		}
		if(!strstr($_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php'))
		{
			if(!is_dir($this->dataDir))
			{
				$message = '<strong>Oops!</strong> Our plugin cannot find the folder that holds your gallery images.';
				$message.= '<p style="font-weigth:bold">Please verify that this folder exists:</p>';
				$message.= '<p style="border:1px solid #CCCCCC; background-color: #FFFFFF; padding:5px">' . $this->dataDir . '</p>';
				$this->error($message);
				return;
			}
			$testfile = $this->dataDir.'test1.txt';
			$fp = @fopen($testfile, 'w');
			if(!$fp)
			{
				$message = '<strong>Oops!</strong> A permission setting is preventing our plugin from putting images into the gallery folder.';
				$message.= '<p style="font-weigth:bold">Please enable write access for this folder:</p>';
				$message.= '<p style="border:1px solid #CCCCCC; background-color: #FFFFFF; padding:5px">' . $this->dataDir . '</p>';
				$message.= '<strong>...or let your server administrator resolve this issue by sending them the following message:</strong>';
				$mail = 'Hello,<br/><br/>' . 
						'Please enable write access for the folder:<br/>' . $this->dataDir . '<br/><br/>' . 
						'I need this setting changed in order to run a WordPress plugin for creating image galleries.<br/><br/>' . 
						'Thanks!';
				$this->error($message, $mail);
				return;
			}
			fclose($fp);
			@unlink($testfile);
			$this->backupDir = $this->dataDir . '_backup/';
			if(!is_dir($this->backupDir))
			{
				if(!@mkdir($this->backupDir, 0777))
				{
					$message = '<strong>Oops!</strong> Our plugin cannot find the folder that holds backups of your gallery data.';
					$message.= '<p style="font-weigth:bold">Please verify that this folder exists:</p>';
					$message.= '<p style="border:1px solid #CCCCCC; background-color: #FFFFFF; padding:5px">' . $this->backupDir . '</p>';
					$this->error($message);
					return;
				}
			}
			$testfile = $this->backupDir.'test2.txt';
			$fp = @fopen($testfile, 'w');
			if(!$fp)
			{
				$message = '<strong>Oops!</strong> A permission setting is preventing our plugin from backing up your gallery data.';
				$message.= '<p style="font-weigth:bold">Please enable write access for this folder:</p>';
				$message.= '<p style="border:1px solid #CCCCCC; background-color: #FFFFFF; padding:5px">' . $this->backupDir . '</p>';
				$message.= '<strong>...or let your server administrator resolve this issue by sending them the following message:</strong>';
				$mail = 'Hello,<br/><br/>' . 
						'Please enable write access for the folder:<br/>' . $this->backupDir . '<br/><br/>' . 
						'I need this setting changed in order to run a WordPress plugin for creating image galleries.<br/><br/>' . 
						'Thanks!';
				$this->error($message, $mail);
				return;
			}
			fclose($fp);
			@unlink($testfile);
		}
		$this->actionsClass = new PicturesurfGalleryActions($this);
		add_action('init', array($this , 'updateRewriter'));
		add_action('admin_menu', array($this , 'addCreatePanel'));
		add_action('edit_post', array($this , 'clearSessionAddMeta'));
		add_action('save_post', array($this , 'clearSessionAddMeta'));
		add_action('publish_post', array($this , 'clearSessionAddMeta'));
		add_filter('the_posts', array($this , 'replaceWithImage'));
		add_filter('the_excerpt_rss', array($this , 'embedGallery'));
		add_filter('the_excerpt', array($this , 'embedGallery'));
		add_filter('the_content', array($this , 'embedGallery'));
		//assigning ajax actions for gallery create
		add_action('wp_ajax_saveSession', array($this->actionsClass , 'saveSession'));
		add_action('wp_ajax_saveDefaults', array($this->actionsClass , 'saveDefaults'));
		add_action('wp_ajax_saveGallery', array($this->actionsClass , 'saveGallery'));
		add_action('wp_ajax_PSapiProxy', array($this->actionsClass , 'apiProxy'));
		add_action('wp_ajax_registerImage', array($this->actionsClass , 'registerImage'));
		add_action('wp_ajax_loadOriginal', array($this->actionsClass , 'loadOriginal'));
		add_action('wp_ajax_deleteImage', array($this->actionsClass , 'deleteImage'));
		add_action('wp_ajax_saveGallery', array($this->actionsClass , 'saveGallery'));
		add_action('wp_ajax_saveGalleryFile', array($this->actionsClass , 'saveGalleryFile'));
		add_action('wp_ajax_saveSettings', array($this->actionsClass , 'saveSettings'));
		register_activation_hook(__FILE__, array($this , 'install'));
		register_deactivation_hook(__FILE__, array($this , 'uninstall'));
	}
	/*
	 * install plugin DB tables and etcs on activation
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function install ()
	{
		$this->patch();
		//creating galleries table
		$tblName = $this->wpdb->prefix . "psgalleries";
		if ($this->wpdb->get_var("SHOW TABLES LIKE '$tblName'") != $tblName)
		{
			$sql = 'CREATE TABLE `' . $tblName . '` (
				`id` int(11) NOT NULL auto_increment,
				`ps_id` int(11) NOT NULL,
				`code` varchar(100) default NULL,
				PRIMARY KEY  (`id`),
				INDEX (`ps_id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;';
			$this->wpdb->query($sql);
		}
		//creating imagepages table
		$tblName = $this->wpdb->prefix . "psimagepages";
		if ($this->wpdb->get_var("SHOW TABLES LIKE '$tblName'") != $tblName)
		{
			$sql = 'CREATE TABLE `' . $tblName . '` (
				`ps_id` int(11) NOT NULL,
				`sequence` int(6) NOT NULL,
				`title` varchar(100) default NULL,
				`description` text default NULL,
				`preview_url` text default NULL,
				`views` int(11) default 0,
				INDEX  (`ps_id`),
				INDEX (`sequence`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;';
			$this->wpdb->query($sql);
		}
		delete_option("GalleryVersion");
		add_option("GalleryVersion", $this->version);
	}
	/*
	 * patch previous version of plugin, or data left from it to current one
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function patch ()
	{
		$oldVer = get_option("GalleryVersion");
		if ($oldVer == '1.0') //Deprecated, we shouldn't have any user having this one
		{
			$this->wpdb->query('DROP TABLE ' . $this->wpdb->prefix . 'psgalleries;');
		}
	}
	/*
	 * show initialization error message in formatted div
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function error($message, $mail = null)
	{
		echo '<div style="width:656px; margin:10px auto; font-size: 13px; padding:17px; border:1px solid #DD3C10; background-color: #FFEBE8">' . 
				'<div>' . $message . '</div>' . ($mail?'<p style="border:1px solid #CCCCCC; background-color: #FFFFFF; padding:7px">' . $mail . '</p>':'') . 
				'<div>Still need help? <a href="http://getsatisfaction.com/picturesurf/products/picturesurf_picturesurf_gallery_plugin_for_wordpressorg"' .
				' target="_blank">Post on our support forum</a> or ' . 
				'<a href="mailto:contact@picturesurf.org">contact us directly</a> so we can help you resolve the issue quickly ;)</div>' . 
			'</div>';
	}
	/*
	 * clean up plugin DB tables and etcs on deactivation
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function uninstall ()
	{ //TODO should we drop psgalleries table? Galleries will be lost
	}
	/*
	 * Add image-page into url parameters
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function updateRewriter ()
	{
		global $wp_rewrite;
		$wp_rewrite->add_endpoint('image-page', EP_PERMALINK);
		//TODO code so that rules are not flushed each time
		$wp_rewrite->flush_rules();
	}
	/*
	 * add Gallery panel into WP admin action hooks
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function addCreatePanel ($a)
	{
		if (function_exists('add_meta_box')) // 2.5 style
		{
			$head = 'Picturesurf Gallery: <span style="font-weight:normal">Add an image gallery to your blog post in seconds</span>';
			add_meta_box('gallerydiv', $head, array($this , 'drawCreatePanel'), 'post', 'normal');
			//add_meta_box('gallerydiv', $head, array($this , 'drawCreatePanel'), 'page', 'normal');
		} else
		//TODO
		// 2.3
		{
			//add_action('dbx_post_normal', array($this, 'drawCreatePanel'));
		//add_action('dbx_page_normal', array($this, 'drawCreatePanel'));
		}
	}
	/*
	 * draw preview block, draw create iframe, check for existing post's Gallery, 
	 * load it's data or clear session of previous post
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function drawCreatePanel ()
	{
		session_start();
		$JSON = new PSGalleryServicesJSON();
		$matches = array();
		preg_match('@gid: ..(\d*)..@', $_SESSION['GalleryDataString'], $matches);
		$gidS = $matches[1];
		global $post;
		if ($post && $post->ID)
		{
			$meta = get_post_meta($post->ID, '_Gallery');
			if (is_array($meta) && count($meta) > 0)
			{
				$gidP = array_pop($meta);
				if ($gidS != $gidP)
				{
					$row = $this->wpdb->get_row("SELECT code FROM " . $this->wpdb->prefix . "psgalleries WHERE ps_id = '" . $gidP . "';");
					$data_filename = $row->code . '.json';
					$data = @file_get_contents(PICTURESURF_URL_GAL_DATA . $data_filename); //avoid warning if no file
					$_SESSION['GalleryDataString'] = ($data ? $data : '');
				}
			}
		} else if ($gidS > 0)
		{
			$row = $this->wpdb->get_row('SELECT post_id FROM ' . $this->wpdb->prefix . "postmeta WHERE meta_key = '_Gallery' AND meta_value = '" . $gidS . "';");
			if ($row && $row->post_id > 0)
			{
				$_SESSION['GalleryDataString'] = '';
			}
		}		
		$_SESSION['GalleryDefaults'] = get_option("GalleryDefaults");
		$_SESSION['GallerySettingsJSON'] = $JSON->encode($this->settings);  
		$path = $this->pluginUrl;
		if(!@file_get_contents(PICTURESURF_URL_GAL . 'ig_logreg_html.php'))
		{
			$message = '<srong>Oops!</srong> A server configuration is preventing you from creating an image gallery.';
			if(!ini_get('allow_url_fopen'))
			{
				$message.= '<p style="font-weight:bold">To fix this problem, turn on the PHP configuration option "allow_url_fopen" in the server settings file "php.ini" ';
				$message.= 'or let your server administrator resolve this issue by sending them the following message:</p>';
				$mail = 'Hello,<br/><br/>' . 
						'Please turn on the PHP configuration option "allow_url_fopen" in the server settings file "php.ini"<br/><br/>' . 
						'I need this setting changed in order to run a WordPress plugin for creating image galleries.<br/><br/>' . 
						'Thanks!';
			}
			else 
			{
				$message.= '<p style="font-weight:bold">To fix this problem, verify that the PHP function "file_get_contents" is able to load external URLs or let your ';
				$message.= 'server administrator resolve this issue by sending them the following message:</p>';
				$mail = 'Hello,<br/><br/>' . 
						'Please verify that the PHP function "file_get_contents" is able to load external URLs.<br/><br/>' . 
						'I need this setting changed in order to run a WordPress plugin for creating image galleries.<br/><br/>' . 
						'Thanks!';
			}
			$this->error($message, $mail);
			return;
		}
		echo '<iframe id="pgw_logreg_frame" width="100%" height="0" frameborder="0" src="' . $path . 'views/gallery_logreg.php?path=' . urlencode($path) . '&blogpath=' . urlencode($this->blogUrl) . '"></iframe>';
		require ($this->viewsDir . 'gallery_preview.php');
		echo '<style type="text/css">.wrap, .updated{_width:983px;}</style>'; //IE6 WP page size fix
		echo '<iframe id="pgw_create_frame" width="692" height="532" frameborder="0" src="' . $path . 'views/gallery_create.php?path=' . urlencode($path) . '&blogpath=' . urlencode($this->blogUrl) . '"></iframe>';
	}
	/*
	 * clear Gallery session and add record in post meta when user saves the post where Gallery is inserted
	 * 
	 * @param $newId
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function clearSessionAddMeta ($newId)
	{
		session_start();
		global $post;
		$mainId = $_POST["ID"] ? $_POST["ID"] : $post->ID;
		$matches = array();
		preg_match('@gid:\s*..(\d*)..@s', $_SESSION['GalleryDataString'], $matches);
		$gid = $matches[1];
		if ($mainId && $gid)
		{
			add_post_meta($mainId, '_Gallery', $gid, true) ? "" : update_post_meta($mainId, '_Gallery', $gid);
			if ($newId)
			{
				add_post_meta($newId, '_Gallery', $gid, true) ? "" : update_post_meta($newId, '_Gallery', $gid);
			}
		}
	}
	/*
	 * find [myGallery/Gallery=id] embed codes and substitute those with real widget embed
	 * 
	 * @param $content
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function embedGallery ($content)
	{
		global $wp_the_query;
		$matches = array();
		preg_match_all('@\[PSGallery=([^\]]+)\]?@', $content, $matches, PREG_SET_ORDER);
		$patterns = array();
		$replacements = array();
		foreach ($matches as $Gallery)
		{
			list ($tag, $code) = $Gallery;
			if (isset($code))
			{
				$embed = $this->_getGalleryEmbed($code, $wp_the_query->is_feed);
				if (isset($embed))
				{
					$patterns[] = $tag;
					$replacements[] = $embed;
				}
			}
		}
		$content = str_replace($patterns, $replacements, $content);
		return $content;
	}
	/*
	 * output embed code for Galleries that reside on Picturesurf.org
	 * 
	 * @param $code
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function _getGalleryEmbed ($code, $is_feed = false)
	{
		$JSON = new PSGalleryServicesJSON();
		if(@file_get_contents(PICTURESURF_URL_GAL . 'ig_logreg_html.php'))
		{
			$datastr = file_get_contents(PICTURESURF_URL_GAL_DATA . $code . '.json');
		}
		else
		{
			$datastr = file_get_contents($this->backupDir . $code . '.json');
			return $this->_getGalleryInHTML($datastr);
		}
		if ($is_feed)
		{
			return $this->_getGalleryInHTML($datastr);
		}
		$row = $this->wpdb->get_row('SELECT ps_id FROM ' . $this->wpdb->prefix . 'psgalleries WHERE code="' . $code . '";');
		$row = $this->wpdb->get_row('SELECT * FROM ' . $this->wpdb->prefix . "postmeta WHERE meta_key='_Gallery' AND meta_value='" . $row->ps_id . "';");
		if ($row && $row->post_id > 0)
		{
			global $wp_rewrite;
			$postUrl = get_permalink($row->post_id);
			$prefSlash = (substr($postUrl, -1) != '/'?'/':'');
			$pageAdd = $wp_rewrite->permalink_structure ? $prefSlash . 'image-page/' : (strstr($postUrl, '?') ? '&' : '?') . 'image-page=';
			$html = '<script type="text/javascript">var G_PSGalleryPagePreurl = "' . $postUrl . $pageAdd . '";</script>';
		}
		$html .= '<script type="text/javascript">var G_PSGallerySettings = ' . $JSON->encode($this->settings) . ';</script>';
		$html .= '<script type="text/javascript" src="' . PICTURESURF_URL_GAL . '/widget/gallery.php?gallery=' . $code . '"></script>';
		$html .= '<noscript>' . $this->_getGalleryInHTML($datastr) . '</noscript>';
		return $html;
	}
	/*
	 * if this is an image post - adds return back to post link
	 * 
	 * @param $content
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function replaceWithImage ($posts)
	{
		global $wp_query;
		if(!is_array($wp_query->query))
		{
			return $posts;
		}
		$imgNum = intval($wp_query->query['image-page']);
		if (! $imgNum) //skip if not image page
		{
			$url = $_SERVER['REQUEST_URI'];
			if(strstr($url, 'image-page'))
			{
				$redirect = preg_replace('@image-page[/]@', '', $url);
				header('Location: ' . $redirect);
				exit();
			}
			return $posts;
		}
		if (count($posts) != 1) //skip if not single post
		{
			return $posts;
		}
		$post = $posts[0];
		$galId = array_pop(array_unique(get_post_meta($post->ID, '_Gallery')));
		if (! $galId)
		{
			$post->post_content = 'Gallery image not found.';
			$posts[0] = $post;
			return $posts;
		}
		$res = $this->wpdb->get_results('SELECT * FROM ' . $this->wpdb->prefix . 'psimagepages AS IP
			LEFT JOIN ' . $this->wpdb->prefix . 'psgalleries AS G ON G.ps_id = IP.ps_id
			WHERE IP.ps_id = "' . $galId . '" ORDER BY IP.sequence;', ARRAY_A);
		if (! $res || ! count($res))
		{
			$post->post_content = 'Gallery image not found.';
			$posts[0] = $post;
			return $posts;
		}
		global $wp_rewrite;
		$data['post_url'] = get_permalink($post->ID);
		$imageUrlSuf = ($wp_rewrite->permalink_structure ? 'image-page/' : (strstr($data['post_url'], '?') ? '&' : '?') . 'image-page=');
		$imgFound = false;
		$isNext = false;
		$isPrev = false;
		$NextUrl = '#';
		$PrevUrl = '#';
		$ImageClickUrl = '#';
		$IsEmail = $this->settings['IsShowEmail'];
		$IsFaceB = $this->settings['IsShowFaceB'];
		foreach ($res as $row)
		{
			if ($row['sequence'] == $imgNum - 1)
			{
				$isPrev = true;
				$PrevUrl = $data['post_url'] . $imageUrlSuf . $row['sequence'];
			}
			if ($row['sequence'] == ($imgNum + 1))
			{
				$isNext = true;
				$NextUrl = $data['post_url'] . $imageUrlSuf . $row['sequence'];
				$ImageClickUrl = $data['post_url'] . $imageUrlSuf . $row['sequence'];
			}
			if ($row['sequence'] == $imgNum)
			{
				$data = array_merge($data, $row);
				$imgFound = true;
			}
		}
		if ($isPrev && !$isNext)
		{
			$ImageClickUrl = $data['post_url'] . $imageUrlSuf . '1';
		}
		if (! $imgFound)
		{
			$post->post_content = 'Gallery image not found.';
			$posts[0] = $post;
			return $posts;
		}
		list ($data['width'], $data['height']) = @getimagesize($data['preview_url']);
		$data['orig_url'] = str_replace('custom_', '', $data['preview_url']);
		list ($data['orig_w'], $data['orig_h']) = @getimagesize($data['orig_url']);
		$data['total'] = count($res);
		$data['self_url'] = $data['post_url'] . $imageUrlSuf . $data['sequence'];
		$data['token'] =  array_shift(explode('.', array_pop(explode('-', $data['orig_url']))));
		ob_start();
		include ($this->viewsDir . 'gallery_image.php');
		$post->post_content = preg_replace("@[\r\n\t]@s", "", ob_get_clean());
		$post->custom_css_href = PICTURESURF_URL_GAL_WIDGET . 'igw_css.php?gallery=' . $data["code"] . '&divid=' . $data['code'] . '0;';
		$posts[0] = $post;
		add_action('the_content', array($this , '_addImagePageCSS'));
		return $posts;
	}
	/*
	 * hack into WP $query object to allow displaying image posts with type "GalleryImage"
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function _addImagePageCSS ($content)
	{
		global $post;
		if(@file_get_contents(PICTURESURF_URL_GAL . 'ig_logreg_html.php'))
		{
			$cssPath = PICTURESURF_URL_GAL . 'css/gallery-image.css';
		}
		else
		{
			$cssPath = $this->pluginUrl . 'views/gallery-image.css';
		}
		$precont = '<style media="all" rel="stylesheet" type="text/css" >';
		$precont .= '@import "' . $cssPath . '";';
		$precont .= '@import "' . $post->custom_css_href . '";</style>';
		return $precont . $content;
	}
	/*
	 * find [myGallery/Gallery=id] embed codes and substitute those with real widget images specificaly for feeds
	 * 
	 * @param $content
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function _getGalleryInHTML ($datastr, $PS = false)
	{
		ob_start();
		global $wp_rewrite, $post;
		$json = new PSGalleryServicesJSON();
		$posturl = get_permalink($post->ID);
		$imageUrlSuf = ($wp_rewrite->permalink_structure ? 'image-page/' : (strstr($posturl, '?') ? '&' : '?') . 'image-page=');
		$datastr = stripcslashes(urldecode(str_replace("Gallery = ", "", $datastr)));
		$data = $json->decode($datastr);
		foreach ($data->ims as $key => $image)
		{
			//get first available thumb from the list defined on top (should be SQ)
			foreach ($this->thumbModes as $mode)
			{
				if($image->thumbnails->{$mode})
				{
					$image->thumbnail = ($image->thumbnails->{$mode});
					break;
				}
			}
			$image->href = $posturl . $imageUrlSuf . ($key+1);
			if($PS)
			{
				$ImageUrl = PICTURESURF_URL_IMG . array_pop(explode('/', str_replace($mode . '_', '', $image->thumbnail)));
				$image->thumbnail = $ImageUrl;
				$image->href = $ImageUrl;
			}
			$data->ims[$key] == $image;
		}
		include ($this->viewsDir . 'gallery_feed.php');
		return ob_get_clean();
	}
}