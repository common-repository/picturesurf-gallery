<div>
	<h2>
		<a href="<?=$posturl?>"><?=$data->title?></a>
	</h2>
	<p>
	<?foreach($data->ims as $img):?>
		<a href="<?=$img->href?>" title="<?=$img->title?>"><img src="<?=$img->thumbnail?>" style="margin:2px 0; border:1px solid #BDC7D8"/></a>
	<?endforeach;?>
	</p>
	<p style="text-align:right; font-size:10px">
	Powered by <a title="Powered by Picturesurf Gallery" href="http://www.picturesurf.org/get-gallery" style="color:#0D6DCE">Picturesurf Gallery</a>
	</p>
</div>