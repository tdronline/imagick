<?php
function optimizeImage( $path, $name, $maxSize = max_image_size ) {
	$pngMaxQ = png_max_quality;
	$pngMinQ = png_min_quality;
	$jpgQ    = jpeg_quality;
	$file    = pathinfo( $name );
	$ds = DIRECTORY_SEPARATOR;

	if ( ! empty( $file ) ) {
		$temp_file = '.' . $ds . 'temp'. $ds . $file['basename'];
		copy( $name, $temp_file );
		$subPath = str_replace( $path, '', stripslashes( $file['dirname'] ) );
		$optPath = optimized_files . $subPath . $ds . $file['basename'];

		// Create folders if not exist
		if ( ! is_dir( optimized_files . $subPath . $ds ) ) {
			mkdir( optimized_files . $subPath . $ds, 0777, true );
		}
		// Commands
		## Note in UNIX systems "magick" part is not necessary. Please remove it if you face any trouble.
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			$magick = 'magick';
		} else {
			$magick = '';
		}

		// Run Optimisation Commands
		$cmdJPEG = "$magick mogrify -strip -sampling-factor 4:2:0 -strip -filter Triangle -unsharp 0.25x0.08+8.3+0.045 -dither None -posterize 136 -quality $jpgQ -interlace JPEG -colorspace RGB \"$temp_file\"";

		// Use PNGQuant https://pngquant.org
		$cmdPNG = "pngquant --force --quality $pngMinQ-$pngMaxQ --output \"$temp_file\" \"$temp_file\"";

		// Resize command
		$cmdResize = "$magick mogrify \"$name\"  -resize {$maxSize}x{$maxSize}  \"$temp_file\"";

		// Get image extension
		if ( isset( $file['extension'] ) ) {
			$ext = $file['extension'];
		} else {
			$ext = '';
		}

		// if Extension is matched
		$ex = strtolower( $ext );
		if ( in_array( $ex, [ 'jpg', 'jpeg', 'png' ] ) ) {
			$originalSize  = 0;
			$optimizedSize = 0;

			// If image is Larger than defined size
			$dim = getimagesize( $name );
			if ( $dim[0] > $maxSize || $dim[1] > $maxSize ) {
				exec( "$cmdResize" );
				$resized = 1;
			} else {
				$resized = 0;
			}

			// If JPG image Run Optimization
			if ( in_array( $ex, [ 'jpg', 'jpeg' ] ) ) {
				$originalSize = filesize( $name );
				if ( exec( "$cmdJPEG" ) ) {
					//echo "Succss";
				}
				clearstatcache();
				$optimizedSize = filesize( $temp_file );
			}

			// If JPG image Use TinyPNG
			if ( $ex == 'png' ) {
				$originalSize = filesize( $name );
				if ( exec( "$cmdPNG" ) ) {
					//echo "Succss";
				}
				clearstatcache();
				$optimizedSize = filesize( $temp_file );
			}

			// Calculate Stats
			$compression = round( 100 - ( ( $optimizedSize / $originalSize ) * 100 ), 2 );
			$fzOri       = round( $originalSize / 1024, 2 );
			$fzOpt       = round( $optimizedSize / 1024, 2 );

			$logentry = $file['basename'] . ", $fzOri, $fzOpt, $compression \n";

			file_put_contents( 'log.txt', $logentry, FILE_APPEND | LOCK_EX );

			if ( is_null( $compression ) || $compression <= 0 ) {
				$logentry = "$name, $fzOri, $fzOpt, $compression \n";
				file_put_contents( 'error_log.txt', $logentry, FILE_APPEND | LOCK_EX );
				copy( $name, $optPath );
				unlink( $temp_file );
			} else {
				// Move file if optimization is OK
				copy( $temp_file, $optPath );
				unlink( $temp_file );
			}

			return array( $fzOri, $fzOpt, $compression, $resized );
		} else {
			copy( $name, $optPath );
			unlink( $temp_file );
		}
	}
}

function getImages( $path ) {
	// Iterate through folders and get file names to an array
	$objects = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path ), RecursiveIteratorIterator::SELF_FIRST );
	if ( !empty( $objects ) ) {
		$files = [];
		foreach ( $objects as $name => $object ) {
			if ( is_file( $name ) ) {
				$files[] = addslashes( $name );
			} else {
				$files[] = '';
			}
		}

		if ( is_array( $files ) ) {
			return json_encode( $files );
		} else {
			return null;
		}
	}
}

function optImages( $path, $maxSize ) {
	$totOriginal  = 0;
	$totOptimized = 0;
	$filesJSON    = getImages( $path );
	if(!empty($filesJSON)) {
		$files = json_decode( $filesJSON );
		foreach ( $files as $name ) {
			if ( is_file( $name ) ) {
				$file = pathinfo( $name );
				$fz   = optimizeImage( $path, $name, $maxSize );
				if ( $fz[3] == 1 ) {
					$resize = "<b>Resized: </b>";
				} else {
					$resize = '';
				}
				$fileName = stripslashes( $name );
				if ( $fz[2] <= 0 ) {
					$com   = 'No Need';
					$style = "background-color: #BDBDBD;";
				} else {
					$style = "background: linear-gradient(to right, rgba(136,255,50,1) 0%,rgba(229,229,229,0.38) {$fz[2]}%,rgba(229,229,229,0) 100%);";
					$com   = $fz[2] . '%';
				}
				echo "<div class='row' title='$fileName'><span class='file'>$resize {$file['basename']}</span> <span class='ori'>{$fz[0]}Kb</span> <span class='opt'>{$fz[1]}Kb</span> <span class='comp' style='$style'>$com</span></div>";
				if ( $fz[0] > $fz[1] ) {
					$totOriginal  = $totOriginal + $fz[0];
					$totOptimized = $totOptimized + $fz[1];
				}
				flush();
				ob_flush();
			}
		}

		$totfzOri       = round( $totOriginal / 1024, 2 );
		$totfzOpt       = round( $totOptimized / 1024, 2 );
		$totcompression = round( 100 - ( ( $totOptimized / $totOriginal ) * 100 ), 2 );
		$save           = round( ( $totOriginal - $totOptimized ) / 1024, 2 );
		$c              = round( $totcompression, 2 );
		$totFiles       = sizeof( $files );
		$style          = "background: linear-gradient(to right, rgba(136,255,50,1) 0%,rgba(229,229,229,0.38) $c%,rgba(229,229,229,0) 100%);";
		echo "<div class='row tot'><span class='save'>Processed: {$totFiles} FIles Saved: {$save}Mb </span><span class='ori'>{$totfzOri}Mb</span> <span class='opt'>{$totfzOpt}Mb</span> <span class='comp' style='$style'>{$totcompression}%</span></div>";
	} else {
		$ifolder = original_files;
		echo "<h1>No images found in folder $ifolder</h1>
<p>Add some images or change configuration in <strong>index.php</strong></p>";
	}
}

?>