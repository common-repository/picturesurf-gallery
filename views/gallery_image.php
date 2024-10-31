<div class="gallery-image-page-backlnk">
	<a rel="nofollow" href="<?=$data['post_url']?>">&laquo; return to original post</a>
</div>
<h2 class="gallery-image-page-header"><?=$data['title']?></h2>
<table cellpadding="0" cellspacing="0" class="ig-ip-imagetable" id="GalleryImage_<?=$data['code']?>">
<tr><td class="gallery-image-page">
	<div class="ig-ip-headctrls">
		<span>Image <span><?=$data['sequence']?></span> of <span><?=$data['total']?></span></span>
		&nbsp; | &nbsp;
		<a class="ig-fullsizer" href="#" data-orig="<?=urlencode($data['orig_url'])?>" data-w="<?=$data['orig_w']?>" data-h="<?=$data['orig_h']?>">View original</a>
		<div class="ig-ip-pager">
			<a class="ig-clink <?=($isPrev?"":"disabled")?>" href="<?=$PrevUrl?>">Previous</a>
			<a class="ig-clink <?=($isNext?"":"disabled")?>" href="<?=$NextUrl?>">Next</a>
		</div>
	</div>
	<div>
		<div class="ig-ip-imagebox">
			<a style="display:block; <?=($data['width']&&$data['height']?'width:'.($data['width']+2).'px; height:'.($data['height']+2).'px':'')?>" href="<?=$ImageClickUrl?>">
				<img title="<?=$data['title']?>" src="<?=$data['preview_url']?>"/>
			</a>
			<div class="ig-ip-imagelinkblock">
				<a class="snap_nopreview copylink" title="Copyrights managed by Picturesurf.org" href="http://www.picturesurf.org/">Picturesurf.org</a>
			</div>
		</div>
		<?if($data['description']):?>
		<div class="ig-ip-descr" style="width:<?=$data['width']?>px;"/><?=$data['description']?></div>
		<?endif;?>
		<div class="ig-ip-footer" style="display:<?=(!$IsEmail && !$IsFaceB?"none":"block")?>">
			<?if($IsEmail):?>
			<a class="ig-ip-email" href="mailto:?subject=<?=addslashes($data['title'])?>&body=<?=rawurlencode($data['self_url'])?>">Email</a>
			<?endif;?>
			<?=($IsEmail&&$IsFaceB?'&nbsp; | &nbsp;':'')?>
			<?if($IsFaceB):?>
			<a class="ig-ip-facebook" href="#" onclick="document.getElementById('ig_shareform').submit(); return false;">Facebook</a>
			<?endif;?>
		</div>
		<div style="position: absolute; width: 1px; height: 1px;">
			<form id="ig_shareform" target="_blank" action="http://www.addthis.com/bookmark.php">
				<input class="ig-shrmys" type="hidden" value="facebook" name="s"/>
				<input type="hidden" value="Picturesurf" name="pub"/>
				<input type="hidden" value="<?=rawurlencode($data['self_url'])?>" name="url"/>
				<input class="ig-shrtitle" type="hidden" name="title" value="<?=addslashes($data['title'])?>"/>
				<input type="hidden" value="" name="lng"/>
				<input type="hidden" value="Gallery" name="winname"/>
				<input type="hidden" value="Gallery" name="content"/>
			</form>
			<script type="text/javascript">
				var G_PSGallerySelectImageNum = "<?=($data['sequence']-1)?>";
				var G_PSGallerySelectImageToken = "<?=$data['token']?>";
			</script>
		</div>
		<div class="ig-ip-spacer"></div>
	</div>
</td></tr>
</table>
	[PSGallery=<?=$data['code']?>]
<script type="text/javascript" src="<?=PICTURESURF_URL_GAL?>js/ig_imagepage.js"></script>