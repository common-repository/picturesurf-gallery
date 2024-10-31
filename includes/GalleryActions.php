<?
/**
 * Actions class for WP Gallery plugin
 * all Gallery related ajax calls should be handled by actions defined in this class
 *
 * @package	Gallery WP plug-in
 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
 * @copyright Copyright (c) 2008, Picturesurf.org
 */
class PicturesurfGalleryActions
{
	var $parent;
	var $imageUrl;
	/*
	 * class constructor (old style to continue WP php 4 style)
	 * 
	 * @param $GalleryPluginClass
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function PicturesurfGalleryActions (&$GalleryPluginClass)
	{
		if (isset($GalleryPluginClass))
		{
			$this->parent = $GalleryPluginClass;
			$this->imageUrl = $this->parent->pluginUrl . 'data/';
			if(LOCAL_SAVE_MODE=='WP')
			{
				$uploadPath = wp_upload_dir();
				$this->imageUrl = $uploadPath['baseurl'] . '/' . LOCAL_SAVE_IMG_FOLDER . '/';
			}
		} else
		{
			return;
		}
	}
	/*
	 * save Gallery info in PHP session
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function saveSession ()
	{
		$JSON = new PSGalleryServicesJSON();
		session_start();
		if (isset($_POST["datastr"]))
		{
			$_SESSION["GalleryDataString"] = $_POST["datastr"];
			echo "status = " . $JSON->encode(array("status" => "ok"));
		} else
		{
			echo "status = " . $JSON->encode(array("status" => "fail" , "message" => "No data provided"));
		}
		exit(); //need to exit because of the studip die('0') in WP's admin-ajax.php that breaks JSON
	}
	/*
	 * save Gallery default tabs navigation and customization ooptions in PHP session
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function saveDefaults ()
	{
		$JSON = new PSGalleryServicesJSON();
		session_start();
		if (isset($_POST["datastr"]))
		{
			$defaults = $_POST["datastr"];
			$_SESSION["GalleryDefaults"] = $defaults;
			add_option("GalleryDefaults", $defaults);
			echo "status = " . $JSON->encode(array("status" => "ok"));
		} else
		{
			echo "status = " . $JSON->encode(array("status" => "fail" , "message" => "No data provided"));
		}
		exit();
	}
	/*
	 * api calls proxy script 
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function apiProxy ($data, $doreturn = false)
	{
		if (! $data || ! $data['furl'])
		{
			$data = $_POST;
		}
		require_once ($this->parent->includesDir . 'APIProxy.php');
		$JSON = new PSGalleryServicesJSON();
		$key = $this->_getKey();
		$url = get_option('siteurl');
		$res = apiProxy($data, $key, $url);
		//catch API key if login/register and save it
		if (strstr($_POST['furl'], 'user/loginPlugin') || strstr($_POST['furl'], 'user/registerPlugin'))
		{
			$data = $JSON->decode($res);
			if ($data->status == 'ok')
			{
				if ($data->apiKey)
				{
					$this->_saveKey($data->apiKey);
					echo "status = " . $JSON->encode(array("status" => "ok"));
				} else
				{
					if (strstr($_POST['furl'], 'user/registerPlugin'))
					{
						echo "status = " . $JSON->encode(array("status" => "fail" , "message" => "API key generation error. Please contact administrator."));
					} else
					{
						echo "status = " . $JSON->encode(array("status" => "fail" , "message" => "No API key found. Please register plugin at Picturesurf.org."));
					}
				}
			} else
			{
				echo "status = " . $JSON->encode(array("status" => "fail" , "message" => $data->message));
			}
		} else
		{
			if ($doreturn)
			{
				return $res;
			}
			echo "status = " . $res;
		}
		exit();
	}
	/*
	 * load and process specified URL seeking for images
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function registerImage ()
	{
		$JSON = new PSGalleryServicesJSON();
		$fname = $_POST['fname'];
		$imgId = $_POST['id'];
		if (! isset($fname))
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'No valid file provided.'));
			exit();
		}
		$APIdata['furl'] = 'api/image';
		if (! $imgId)
		{
			$APIdata['ps_file_name'] = $fname;
			$APIdata['ps_api_op'] = 'create';
			$res = $this->apiProxy($APIdata, true);
			$data = $JSON->decode($res);
			if (! $data->result->succeeded)
			{
				echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => $data->result->error));
				exit();
			}
			$imgId = $data->result->id;
		} else
		//check that local thumbs exist
		{
			$localThumbs = $_POST['thumbnails'];
			foreach ($localThumbs as $thumb)
			{
				$path = str_replace($this->imageUrl, $this->parent->dataDir, $thumb);
				if (! file_exists($path))
				{
					echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'One of image thumbnails was lost.'));
					exit();
				}
			}
			echo "status = " . $JSON->encode(array('status' => 'ok' , 'iid' => $imgId , 'thumbnails' => $localThumbs));
			exit();
		}
		$APIdata['image_id'] = $imgId;
		$APIdata['modes'] = implode(',', $this->parent->thumbModes);
		$APIdata['ps_api_op'] = 'thumbnail';
		$res = $this->apiProxy($APIdata, true);
		$data = $JSON->decode($res);
		if (! $data->result->succeeded)
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => $data->result->error));
			exit();
		}
		//save images at WP setting will go here
		$thumbnails = $data->result->thumbnails;
		$localThumbs = array();
		foreach ($thumbnails as $mode => $thumb)
		{
			if (! @copy($thumb->path, $this->parent->dataDir . $thumb->name))
			{
				echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'Unable to save image file. Please verify that data folder is writable.'));
				exit();
			}
			$thumUrl = $this->imageUrl . $thumb->name;
			$localThumbs[$mode] = $thumUrl;
		}
		echo "status = " . $JSON->encode(array('status' => 'ok' , 'iid' => $imgId , 'thumbnails' => $localThumbs));
		exit();
	}
	/*
	 * load original image file from URL and create thumnail
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function loadOriginal ()
	{
		$JSON = new PSGalleryServicesJSON();
		$file = $_POST["url"];
		$source = $_POST["source"];
		$fname = array_pop(explode("/", $file));
		if (! @copy($file, $this->parent->dataDir . $fname))
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'Error copying remote file.'));
			exit();
		}
		$_POST['fname'] = $fname;
		$this->registerImage();
	}
	/*
	 * delete image file and record in DB
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function deleteImage ($id = null, $pid = null, $thumbs = null)
	{
		$JSON = new PSGalleryServicesJSON();
		$doexit = false;
		if (! isset($id))
		{
			$id = $_POST["id"];
			$pid = $_POST["pid"];
			$thumbs = $_POST["thumbnails"];
			$doexit = true;
		}
		if (! isset($id))
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'No image ID specified.'));
			exit();
		}
		//delete iamges
		if (is_array($thumbs))
		{
			foreach ($thumbs as $thumb)
			{
				$shortPath = str_replace($this->imageUrl, '', $thumb);
				if (file_exists($this->parent->dataDir . $shortPath))
				{
					unlink($this->parent->dataDir . $shortPath);
				}
			}
		}
		//delete posts record if no comments
		if ($pid)
		{
			$isCom = $this->parent->wpdb->get_row('SELECT comment_ID from ' . $this->parent->wpdb->prefix . 'comments WHERE comment_post_ID = "' . $pid . '";');
			if (! $isCom)
			{
				$this->parent->wpdb->query('DELETE from ' . $this->parent->wpdb->prefix . 'posts WHERE ID = "' . $pid . '";');
			}
		}
		if ($doexit)
		{
			echo 'status = ' . $JSON->encode(array('status' => 'ok'));
			exit();
		}
	}
	/*
	 * save Gallery into DB table
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function saveGallery ()
	{
		$JSON = new PSGalleryServicesJSON();
		$apiFurl = 'api/gallery';
		$gid = intval($_POST["gid"]);
		$title = mysql_escape_string(urldecode($_POST['title']));
		$descr = mysql_escape_string(urldecode($_POST['desc']));
		if (! isset($title))
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'Error saving Gallery. No title specified.'));
			exit();
		}
		if ($gid > 0)
		{
			//get old title of gallery
			$api = array('furl' => $apiFurl , 'ps_api_op' => 'get' , 'gallery_id' => $gid);
			$res = $this->apiProxy($api, true);
			$data = $JSON->decode($res);
			if (! $data->result->succeeded)
			{
				echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => $data->result->error));
				exit();
			}
			$oldTitle = $data->result->title;
			$apiOpt = 'update';
		} else
		{
			$apiOpt = 'new';
		}
		//combine images string to pass to API gallery save call
		$apiImsStr = '';
		if ($_POST['ims'])
		{
			$apiIms = array();
			foreach ($_POST['ims'] as $img)
			{
				$apiIms[] = $img['id'];
			}
			$apiImsStr = implode(',', $apiIms);
		}
		$api = array('furl' => $apiFurl , 'ps_api_op' => $apiOpt , 'gallery_id' => $gid , 'title' => $title , 'description' => $descr , 'images_string' => $apiImsStr);
		$res = $this->apiProxy($api, true);
		$data = $JSON->decode($res);
		if (! $data->result->succeeded)
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => $data->result->error));
			exit();
		}
		$gid = $data->result->id;
		$code = $data->result->gal_code;
		$created = $data->result->created;
		if ($apiOpt == 'new') //add local gallery record for newly created PS one
		{
			$this->parent->wpdb->query("INSERT INTO " . $this->parent->wpdb->prefix . "psgalleries SET ps_id='" . $gid . "', code='" . $code . "';");
		}
		if ($_POST['ims'])
		{
			$images = array();
			$pimgs = array();
			//clean psimagepages table
			$this->parent->wpdb->query("DELETE FROM " . $this->parent->wpdb->prefix . "psimagepages WHERE ps_id='" . $gid . "';");
			//clean up all deleted images
			if ($_POST['destrIms'])
			{
				foreach ($_POST['destrIms'] as $img)
				{
					$this->deleteImage($img['id'], $img['pid'], $img['thumbnails']);
				}
			}
			//rename directory if old exists and title changed
			if ($oldTitle)
			{
				$oldDir = $this->_getImageSavePath($oldTitle, $created, $gid, true);
				$newDir = $this->_getImageSavePath($title, $created, $gid);
				if ($oldDir['path'] != $newDir['path'] && is_dir($oldDir['path']))
				{
					rmdir($newDir['path']);
					if (! rename($oldDir['path'], $newDir['path']))
					{
						echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'Error updating images directory name.'));
						exit();
					}
					$renamed = true;
				}
			} else
			{
				$newDir = $this->_getImageSavePath($title, $created, $gid);
			}
			$i = 1;
			$images = array();
			foreach ($_POST['ims'] as $img)
			{
				$img['title'] = urldecode($img['title']);
				$img['descr'] = urldecode($img['descr']);
				$img['src'] = urldecode($img['src']);
				$origName = str_replace('SQ_', '', array_pop(explode('/', $img['src'])));
				$fileDir = str_replace(array('SQ_' . $origName , $origName), array('' , ''), str_replace($this->imageUrl, '', $img['src']));
				$orig = $this->parent->dataDir . $fileDir . $origName;
				if ($renamed && ! file_exists($orig))
				{
					$orig = $newDir['path'] . '/' . $origName;
				}
				$api = array('furl' => 'api/image' , 'ps_api_op' => 'thumbnail' , 'image_id' => $img['id'] , 'modes' => 'custom' , 'custom_width' => $_POST['imgWidth']);
				$res = $this->apiProxy($api, true);
				$data = $JSON->decode($res);
				if (! $data->result->succeeded)
				{
					echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => $data->result->error));
					exit();
				}
				$custom = $data->result->thumbnails->custom;
				if (! copy($custom->path, $this->parent->dataDir . $custom->name))
				{
					echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'Unable to save image file. Please verify that data folder is writable.'));
					exit();
				}
				//move new images thumbnails into folder
				$img['thumbnails']['custom'] = $this->imageUrl . $custom->name;
				if ($newDir)
				{
					$fileUri = $this->imageUrl . $newDir['name'] . '/';
					foreach ($img['thumbnails'] as $mode => $thumb)
					{
						$thumbName = array_pop(explode('/', $thumb));
						if (file_exists($this->parent->dataDir . $thumbName))
						{
							if (file_exists($newDir['path'] . '/' . $thumbName))
							{
								unlink($newDir['path'] . '/' . $thumbName);
							}
							rename($this->parent->dataDir . $thumbName, $newDir['path'] . '/' . $thumbName);
						}
						if (file_exists($newDir['path'] . '/' . $thumbName))
						{
							$img['thumbnails'][$mode] = $fileUri . $thumbName;
						}
					}
				}
				//add image page record into DB
				$this->parent->wpdb->query( $this->parent->wpdb->prepare(
					"INSERT INTO " . $this->parent->wpdb->prefix . "psimagepages SET 
	                ps_id=%d, sequence=%d, title=%s, description=%s, preview_url=%s;"
				, $gid, $i, $img['title'], $img['descr'], $img['thumbnails']['custom']));
				$images[] = array('id' => $img['id'] , 'pid' => $i , 'thumbnails' => $img['thumbnails']);
				$i ++;
			}
		}
		echo 'status = ' . $JSON->encode(array('status' => 'ok' , 'gid' => $gid , 'updated' => time() , 'code' => $code , 'images' => $images));
		exit();
	}
	/*
	 * create Gallery data file for widget
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function saveGalleryFile ()
	{
		$JSON = new PSGalleryServicesJSON();
		$datastr = $_POST['datastring'];
		$id = $_POST['gallery_id'];
		if(!$datastr || !$id)
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => 'No data passed for saving. Please try again.'));
			exit();
		}
		//save local backup
		$row = $this->parent->wpdb->get_row("SELECT code FROM " . $this->parent->wpdb->prefix . "psgalleries WHERE ps_id = '" . $id . "';");
		$data_filepath = $this->parent->backupDir . $row->code . '.json';		
		$fp = @fopen($data_filepath, "w");
		fwrite($fp, $datastr);
		fclose($fp);
		//save file at picturesurf
		$api = array('furl' => 'api/gallery' , 'ps_api_op' => 'update' , 'ps_api_sub_op' => 'exportfile' , 'gallery_id' => $id , 'datastring' => $datastr);
		$res = $this->apiProxy($api, true);
		$data = $JSON->decode($res);
		if (! $data->result->succeeded)
		{
			echo 'status = ' . $JSON->encode(array('status' => 'fail' , 'message' => $data->result->error));
			exit();
		}
		echo 'status = ' . $JSON->encode(array('status' => 'ok'));
		exit();
	}
	/*
	 * get/create images directory assiciated with the gallery
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function _getImageSavePath ($title, $timestamp, $id = null, $dontCreate = false)
	{
		if (! date($timestamp))
		{
			return false;
		}
		$title = substr($title, 0, 50);
		$title = preg_replace(array('@[\\/\:\*"<>\|\']@' , '@\s+@'), array('' , '_'), stripslashes($title));
		$dirName = $title . '_' . date('m-d-Y', $timestamp) . ($id ? '_' . $id : '');
		$dir = $this->parent->dataDir . $dirName;
		if (! is_dir($dir) && ! $dontCreate)
		{
			if (! @mkdir($dir))
			{
				return false;
			}
		}
		return array('name' => $dirName , 'path' => $dir);
	}
	/*
	 * save API key in WP options list
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function _saveKey ($key)
	{
		if (get_option("PicturesurfAPIKey"))
			update_option("PicturesurfAPIKey", $key); else
			add_option("PicturesurfAPIKey", $key, '', 'no');
		$_SESSION["PicturesurfAPIKey"] = $key;
	}
	/*
	 * get PAI key from session or fill session from WP option
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function _getKey ()
	{
		session_start();
		if (! $_SESSION["PicturesurfAPIKey"])
			$_SESSION["PicturesurfAPIKey"] = get_option("PicturesurfAPIKey");
		return $_SESSION["PicturesurfAPIKey"];
	}
	/*
	 * save settings from the setting page into WP options table
	 * 
	 * @author Denis Zenkovich <denis.zenkovich@picturesurf.org>
	 */
	function saveSettings()
	{
		$JSON = new PSGalleryServicesJSON();
		$settings = array(
				'IsImagePages' => $_POST['IsImagePages']?($_POST['IsImagePages']=='true'):$this->parent->settings['IsImagePages'],
				'SaveMode' => $_POST['SaveMode']?$_POST['SaveMode']:$this->parent->settings['SaveMode'],
				'SaveUploadsFolder' => $_POST['SaveUploadsFolder']?$_POST['SaveUploadsFolder']:$this->parent->settings['SaveUploadsFolder'],
				'IsShowEmail' => $_POST['IsShowEmail']?($_POST['IsShowEmail']=='true'):$this->parent->settings['IsShowEmail'],
				'IsShowFaceB' => $_POST['IsShowFaceB']?($_POST['IsShowFaceB']=='true'):$this->parent->settings['IsShowFaceB'],
		);
		update_option('GallerySettings', serialize($settings));
		echo 'status = ' . $JSON->encode(array('status' => 'ok'));
		exit();
	}
}