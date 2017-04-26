<?php

use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request as Request;

// load the image manipulation library
require 'vendor/autoload.php';

// the request
$request = Request::createFromGlobals();

// the original image to process
$img_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/' . $request->path();

// where we will write the cache file
$cache_path = dirname(__FILE__) . '/cache/' . http_build_query($request->query()) . '_' . basename($img_path);

try {
    // if CACHE HIT
    if (file_exists($cache_path)) {

        $img = Image::make($cache_path);

    }
    // else CACHE MISS
    else {
    	// load wordpress
		require_once( dirname(__FILE__) . '/../../../wp-load.php');
		
		if (!function_exists('is_plugin_active')) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		
		// check for this plugin being active
		if (is_plugin_active('atd_pictator/atd-pictator.php')) {
			
			$driver = get_option('image_library');
			
			$driver = in_array($driver, ['gd', 'imagick']) ? $driver : 'gd';
			
			// image manipulation object
			Image::configure(['driver' => $driver]);
			
	        $img = Image::make($img_path);
	
		    // Image Resize
	        if ($request->query('a', 'resize') == 'resize') {
			    switch ($request->query('r')) {
				    case 'contain':
					    $img->resize($request->query('w'), $request->query('h'), function($constraint) {
	                    	$constraint->aspectRatio();
	                	});
				    break;
				    case 'widen':
					    $img->widen($request->query('w'));
				    break;
				    case 'heighten':
					    $img->heighten($request->query('h'));
				    break;
				    case 'cover':
					    $img->fit(
						    $request->query('w'), 
						    $request->query('h')
					    );
			    }
	        }
		    // Image Crop
	        else if ($request->query('a') == 'crop') {
	            $img->crop(
				    $request->query('w'), 
				    $request->query('h'), 
				    $request->query('x'), 
				    $request->query('y')
			    );
	        }
	        
	        $img->save($cache_path);

		}
    }

	if(isset($img)) {
    	echo $img->response();
	} else {
		http_response_code(404);
		exit;
	}

} catch (Exception $e) {
	http_response_code(404);
	exit;
}


