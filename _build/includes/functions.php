<?php

function getSnippetContent($filename) {
	$o = file_get_contents($filename);
	$o = trim(str_replace(array('<?php','?>'),'',$o));
	return $o;
}

function rrmdir($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);

		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}

		reset($objects);
		rmdir($dir);
	}
}
