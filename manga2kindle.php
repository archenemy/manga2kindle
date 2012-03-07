<?php
define('TMPDIR','tmp'.time().'/');

$nombreFichero = $argv[1];
$infoFichero = pathinfo($nombreFichero);
// var_dump($infoFichero);
$descomprimido = null;
switch ($infoFichero['extension']) {
	case 'cbr': case 'rar':
		$descomprimidos = unrar($infoFichero['basename']);
	break;
	case 'cbz': case 'zip': default:
		$descomprimidos = unzip($infoFichero['basename']);
	break;
}

$convertidos = array();

$nuevoDir = $infoFichero['filename'].'.Kindle';
mkdir($nuevoDir);

foreach ($descomprimidos as $imagen) {
	$imagenConvertida = procesaFichero($imagen);
	$infoImagenConvertida = pathinfo($imagenConvertida);
	// var_dump($infoImagenConvertida);
	rename($imagenConvertida,$nuevoDir.'/'.$infoImagenConvertida['basename']);
	unlink($imagen);
}

foreach (glob(TMPDIR.'/*') as $basura) {
	unlink($basura);
}
rmdir(TMPDIR);

/*
 * @param $fichero nombre del fichero a descomprimir
 * @return array con la lista de ficheros descomprimida
 */ 
function unrar($fichero) {
	$fichero = escapeshellarg($fichero);
	$cmd = "unrar x -ep {$fichero} ".TMPDIR;
	$resultado = array();
	exec($cmd,$resultado);
	$files = glob(TMPDIR.'*.jpg');
	return $files;
}

/*
 * @param $fichero nombre del fichero a descomprimir
 * @return array con la lista de ficheros descomprimida
 */ 
function unzip($fichero) {
	$fichero = escapeshellarg($fichero);
	$cmd = "unzip -j {$fichero} -d ".TMPDIR;
	$resultado = array();
	exec($cmd,$resultado);
	$files = glob(TMPDIR.'*.jpg');
	return $files;
}

function procesaFichero($fichero) {
	$infoFichero = pathinfo($fichero);
	$size = getimagesize($fichero);
	$alto = $size[1];
	$ancho = $size[0];
	$altoFinal = 800;
	$anchoFinal = 600;
	
	if ($alto/$ancho > 4/3) {
		//reducimos el alto 
		$altoFinal = 800;
		$anchoFinal = $ancho/($alto/$altoFinal);
		
	} else {
		// reducimos el ancho
		$anchoFinal = 600;
		$altoFinal = $alto/($ancho/$anchoFinal);
	}

	$imagen = imagecreatefromjpeg($fichero);
	$imagenFinal = imagecreatetruecolor($anchoFinal,$altoFinal);
	$copiaOK = imagecopyresampled($imagenFinal,$imagen,0,0,0,0,$anchoFinal,$altoFinal,$ancho,$alto);
	if (!$copiaOK) {
		die("Error al cambiar el tamaño de la imagen {$fichero}.");
	}
	$grayscaleOK = imagefilter($imagenFinal,IMG_FILTER_GRAYSCALE);
	if (!$grayscaleOK) {
		die("Error al convertir la imagen {$fichero} a escala de grises.");
	}
	$rutaDestino = TMPDIR.$infoFichero['filename'].'.kindle.jpg';
	$pngOK = imagejpeg($imagenFinal,$rutaDestino);
	if (!$pngOK) {
		die("Error al convertir {$fichero} a PNG final.");
	}
	return $rutaDestino;
}

?>