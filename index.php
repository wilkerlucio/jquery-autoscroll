<?php

require_once 'Image.php';

$images = array();
$path = 'examples/images/';

$dir = opendir($path);
$i = 0;

while ($arq = readdir($dir)) {
	if ($arq == '.' || $arq == '..') continue;
	
	$image = new Fishy_Image($path . $arq);
	$image->resize(106, 80, 1);
	$image->save("{$path}image{$i}.jpg");
	
	echo "<img src=\"{$path}image{$i}.jpg\" width=\"106\" height=\"80\" alt=\"\" />";
	
	$i++;
}
