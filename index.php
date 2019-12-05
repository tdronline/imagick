<?php

########################
#### Configurations ####
########################

// define maximum image size width or height
define('max_image_size',  2000);

// define input folder
define('original_files',  "./img-original");

// define output folder
define('optimized_files', "./img-optimized");

// JPEG Quality (60-90 recomonded)
define('jpeg_quality', 78);

// PNG Quality
define('png_max_quality', 80);
define('png_min_quality', 40);

# NOTE: IF YOU ARE USING A LINUX BASED SYSTEM UPDATE THE PNGQUANT FILE AS NECESSARY
// https://pkgs.org/download/pngquant

########################
require_once("functions.php"); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>iMagic - Image Optimiser</title>
    <link href="https://fonts.googleapis.com/css?family=Saira:300,400,600" rel="stylesheet">
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
        }

        .container {
            font-family: 'Saira', sans-serif;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        .container h1 {
            margin-bottom: 30px;
            margin-top: 10px;
        }

        .row {
            font-size: 14px;
            width: 100%;
            display: block;
            padding: 15px 0;
            text-align: right;
            border-bottom: 1px solid #eee;
        }

        .row.tot {
            font-size: 25px;
            font-weight: 600;
            text-align: center;
            text-align: right;
            border: none;
            position: absolute;
            top: 0;
            right: 0;
        }

        .file {
            float: left;
            text-align: left;
            width: 320px;
            display: inline-block;
            padding: 0 10px;
        }

        .ori {
            color: #bf360c;
            background-color: #FFCCBC;
            padding: 0 20px;
            min-width: 125px;
            display: inline-block;
            border-radius: 10px 3px 3px 10px;
        }

        .opt {
            color: #1b5e20;
            background-color: #C5E1A5;
            padding: 0 20px;
            min-width: 125px;
            display: inline-block;
            border-radius: 3px 10px 10px 3px;
			text-align: left;
        }

        .save {
            padding-right: 20px;
        }

        .comp {
            border: 1px solid #e0e0e0;
            color: #000;
            font-weight: 600;
            border-radius: 10px;
            padding: 0 10px;
            display: inline-block;
            min-width: 100px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Optimize images</h1>
    <?php
    set_time_limit(0);
    $path = realpath(original_files); // Optimize Path, max size
	if(!empty($path)){
		optImages($path, 2000);
	}
    ?>
</div>
</body>
</html>