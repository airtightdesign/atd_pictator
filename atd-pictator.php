<?php
/**
 * Plugin Name: Pictator
 * Plugin URI: https://github.com/airtightdesign/atd-pictator
 * Description: Dynamically alter and cache images requested from the uploads directory.
 * Version: 1.0.0
 * Author: AirTight Design
 * Author URI: http://airtightdesign.com
 * License: MIT
 */

class Pictator
{
    private static $upload_dir;
    
    private static $htaccess_src;

    private static $htaccess_debug_src;

    private static $htaccess_dst;

    private static $cache_dir;

    private static $sample_img_src;

    private static $sample_img_dst;

    private static $install_notifications = [];

    private static $docs = 'https://bitbucket.org/airtightdesign/atd_pictator/overview';

    // Since we dynamically detect directories (and we are NOT a real part of the wordpress ecosystem)
    // these variables have to be computed at runtime.  The bottom of this file invokes the init() method.
    public static function init()
    {
        $upload_dir               = wp_upload_dir();
        
        self::$upload_dir         = $upload_dir['basedir'];
        self::$htaccess_src       = dirname(__FILE__) . '/htaccess_index';
        self::$htaccess_debug_src = dirname(__FILE__) . '/htaccess_debug';
        self::$htaccess_dst       = self::$upload_dir . '/.htaccess';
        self::$cache_dir          = dirname(__FILE__) . '/cache';
        self::$sample_img_src     = dirname(__FILE__) . '/assets/pictator.jpg';
        self::$sample_img_dst     = self::$upload_dir . '/pictator.jpg';
    }

    // returns link to the documentation / README
    private static function docs_link()
    {
        return sprintf(
            '<a href="%s" target="_blank">View installation instructions.</a>',
            self::$docs
        );
    }

    // creates .htaccess file in the uploads directory
    public static function plugin_activated()
    {
        // attempt to create .htaccess file in uploads directory
        if (!self::create_upload_dir()) {
            self::assignNotification("Unable to create uploads directory.");
        }
        
        // attempt to create .htaccess file in uploads directory
        if (!self::create_htaccess()) {
            self::assignNotification("Unable to create .htaccess file. <br>" . self::docs_link());
        }

        // attempt to create cache directory
        if (!self::create_cache_dir()) {
            self::assignNotification("Unable to create image cache directory.");
        }

        // attempt to copy lena.jpg to uploads directory as pictator.jpg
        if (!self::create_sample_image()) {
            self::assignNotification("Unable to create sample image.");
        }

        self::outputNotifications();
    }

    // deletes .htaccess from the uploads directory
    public static function plugin_deactivated()
    {
        // attempt to destroy the sample image file from uploads directory
        if (!self::destroy_sample_image()) {
            self::assignNotification("Unable to remove sample image file from uploads.");
        }
        
        // attempt to destroy the cache directory
        if (!self::destroy_cache_dir()) {
            self::assignNotification("Unable to remove image cache directory.");
        }
        
        // attempt to destroy .htaccess file from uploads directory
        if (!self::destroy_htaccess()) {
            self::assignNotification("Unable to remove .htaccess file.");
        }

        self::outputNotifications();
    }
    
    // creates an .htaccess file in the uploads directory
    private static function create_upload_dir()
    {
        $success = false;
        
        $upload_dir = self::$upload_dir;
        
        // attempt to create directory
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir);
        }
        
        // set permissions on the directory
        if (is_dir($upload_dir)) {
            @chmod($upload_dir, 0775);
        }
        
        if(is_dir($upload_dir) && is_writable($upload_dir)) {
            $success = true;
        }
        
        return $success;
    }

    // creates an .htaccess file in the uploads directory
    private static function create_htaccess($debug = false)
    {
        $success = false;
        
        $src = $debug ? self::$htaccess_debug_src : self::$htaccess_src;
        $dst = self::$htaccess_dst;

        // only copy it if it doesn't already exist
        if (!file_exists($dst)) {
            @copy($src, $dst);
        }

        // if the file exists, we make sure it matches the source file
        if(file_exists($dst) && md5_file($src) == md5_file($dst)) {
            $success = true;
        }
        
        return $success;
    }

    // deletes .htaccess from the uploads directory
    private static function destroy_htaccess($debug = false)
    {
        $src = $debug ? self::$htaccess_debug_src : self::$htaccess_src;
        $dst = self::$htaccess_dst;
        
        // if the file exists, we make sure it matches the source file before deleting it
        if(file_exists($dst) && md5_file($src) == md5_file($dst)) {
            self::unlink($dst);
        }
        
        return !file_exists($dst);
    }

    // creates the directory where images will be cached
    private static function create_cache_dir()
    {
        $success = false;
        
        $cache_dir = self::$cache_dir;

        // attempt to create directory
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir);
        }

        // set permissions on the directory
        if (is_dir($cache_dir)) {
            @chmod($cache_dir, 0775);
        }
        
        if(is_dir($cache_dir) && is_writable($cache_dir)) {
            $success = true;
        }
        
        return $success;
    }
    
    private static function destroy_cache_dir()
    {
        $cache_dir = self::$cache_dir;
        
        if(file_exists($cache_dir)) {
            self::unlink($cache_dir);
        }
        
        return !file_exists($cache_dir);
    }

    // copies a test image to the uploads directory
    private static function create_sample_image()
    {
        $src = self::$sample_img_src;
        $dst = self::$sample_img_dst;

        // only copy it if it doesn't already exist
        if (!file_exists($dst)) {
            @copy($src, $dst);
        }

        // if the file exists, we dangerously assume its correct
        return file_exists($dst);
    }

    // deletes the sample image from the uploads dir
    private static function destroy_sample_image()
    {
        $dst = self::$sample_img_dst;
        self::unlink($dst);
        return !file_exists($dst);
    }

    // removes height and width attributes from images inserted into wordpress content already
    public static function remove_image_attributes($html) {
        $html = preg_replace( '/(width|height)="\d*"\s/', "", $html );
        return $html;
    }

    // clears the cache directory
    public static function clear_cache_dir()
    {
        self::unlink(self::$cache_dir . "/*");
    }

    // true IFF the htaccess file in the uploads directory is the same as the local debuggable htaccess version
    public static function debug_enabled()
    {
        return sha1_file(self::$htaccess_debug_src) == sha1_file(self::$htaccess_dst);
    }

    // add an admin menu item for the plugin
    public static function pictator_menu()
    {
       	add_options_page( 'Pictator Settings', 'Pictator', 'manage_options', 'pictator-settings', array('Pictator', 'pictator_settings') );
    }

    // settings page, allows for debug enable/disable & cache clearing operations
    // also shows sample image file transformations
    public static function pictator_settings()
    {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        $cache_size = self::dirsize(self::$cache_dir);
        $cache_file_count = self::count_files(self::$cache_dir);
        ?>

        <style>
            figure {
                display: inline-block;
                padding: 10px;
                margin: 0 8px 8px 0;
                text-align: center;
                background: #fff;
                border-radius: 3px;
                border: 1px solid #ccc;
                vertical-align: top;
            }

            figcaption {
                word-break: break-all;
            }

            @supports (display: flex) {
                .row {
                    display: flex;
                    justify-content: space-between;
                }

                .block {
                    width: 49%;
                    background: #fff;
                    border-radius: 3px;
                    border: 1px solid #ccc;
                    padding: 10px 20px;
                    margin-bottom: 12px;
                    box-sizing: border-box;
                }
                
                .block-full {
        	        width: 100%;
        	    }
            }

        </style>

        <div class="wrap">
            <h2>Pictator Settings</h2>

            <?php if (!file_exists(self::$htaccess_dst)) : ?>
                <div class="notice notice-error">
                    <p>The .htaccess file does not exist in <?php echo self::$htaccess_dst; ?>.</p>
                </div>
            <?php endif; ?>

            <?php if (!file_exists(self::$cache_dir)) : ?>
                <div class="notice notice-error">
                    <p>The cache directory at <?php echo self::$cache_dir; ?> does not exist.</p>
                </div>
            <?php elseif (!is_writable(self::$cache_dir)) : ?>
                <div class="notice notice-error">
                    <p>The cache directory at <?php echo self::$cache_dir; ?> is not writable.</p>
                </div>
            <?php endif; ?>
            
            <div class="row">
    	        <div class="block block-full">
    	            <h3>Usage</h3>
    	            
    	            <p><a href="https://github.com/airtightdesign/pictator#readme" target="_blank">Usage Guide</a></p>
    	            
    	            <p>Potator allows you to use arbitrarily sized images in your css and templates by appending some query parameters to the image source url.</p>
    	            
      	            Available Parameters
                    <pre>
    a - the 'action' to perform (resize|crop - defaults to 'resize')
    r - the type of resize (contain|widen|heighten|cover)
    w - the width
    h - the height
    x - the x offset
    y - the y offset
                    </pre>
                    
                    <p><a href="#example-images">Example Images</a></p>

    	        </div>
    	    </div>

            <div class="row">
                <div class="block">
                    <h3>Cache Statistics</h3>
                    <p>
                        <strong>Total # cache files:</strong>
                        <span><?php echo $cache_file_count; ?></span>
                    </p>

                    <p>
                        <strong>Total cache filesize:</strong>
                        <span><?php echo ($cache_size / 1000000); ?> MB</span>
                    </p>
                </div>

                <div class="block">
                    <h3>Clear Cache</h3>

                    <p>Clears pre-sized image cache. Images will be recreated upon intitial request.</p>

                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="pictator_clear_cache">
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Clear Cache') ?>" />
                        </p>
                    </form>
                </div>
            </div>

    	    <div class="row">
    	        <div class="block">
    	            <h3>Image Processing Method</h3>

            	    <form action="options.php" method="post">
            	        <?php settings_fields('atd-pictator'); ?>
            	        <?php do_settings_sections('atd-pictator'); ?>
                        <p>
                            <input type="radio" name="image_library" id="image-method-gd" value="gd" <?php if (get_option('image_library') == 'gd') { echo "checked"; } ?> />
                            <label for="image-method-gd">GD</label>
                        </p>

                        <p>
                            <input type="radio" name="image_library" id="image-method-imagemagick" value="imagick" <?php if (get_option('image_library') == 'imagick') { echo "checked"; } ?> />
                            <label for="image-method-imagemagick">ImageMagick</label>
                        </p>

                        <?php submit_button(); ?>
        			</form>
    	        </div>

                <div class="block">
                    <h3>Debug Output</h3>
                    <p>Displays image information in a text overlay on top of the image. This overlay is added to images while a user is logged in to WordPress.</p>
                    <?php if (self::debug_enabled()) : ?>
                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="pictator_disable_debug">
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Disable Debug') ?>" />
                        </p>
                    </form>
                    <?php else: ?>
                    <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                        <input type="hidden" name="action" value="pictator_enable_debug">
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Enable Debug') ?>" />
                        </p>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <h3 id="example-images">Example Images</h3>

            <div class="cleafix">
                <figure>
                    <img style="vertical-align: top; margin-bottom: 8px" src="/wp-content/uploads/pictator.jpg">
                    <figcaption>pictator.jpg</figcaption>
                </figure>

                <figure>
                    <img style="vertical-align: top; margin-bottom: 8px" src="/wp-content/uploads/pictator.jpg?r=cover&w=200&h=200">
                    <figcaption>pictator.jpg?r=cover&w=200&h=200</figcaption>
                </figure>

                <figure>
                    <img style="vertical-align: top; margin-bottom: 8px" src="/wp-content/uploads/pictator.jpg?w=300&r=widen">
                    <figcaption>pictator.jpg?w=300&r=widen</figcaption>
                </figure>

                <figure>
                    <img style="vertical-align: top; margin-bottom: 8px" src="/wp-content/uploads/pictator.jpg?a=crop&w=200&h=200&x=15&y=50">
                    <figcaption>pictator.jpg?a=crop&w=200&h=200&x=15&y=50</figcaption>
                </figure>
            </div>
        </div>
        <?php
    }

    // filter function to add 'Settings' link to plugin page
    public static function pictator_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=pictator-settings">' . __( 'Settings' ) . '</a>';
        array_push( $links, $settings_link );
          return $links;
    }

    // callback for admin-posts.php, clears cache directory
    public static function pictator_clear_cache()
    {
        self::clear_cache_dir();
        status_header(200);
        wp_redirect(wp_get_referer());
        exit;
    }

    // callback for admin-posts.php, disables debug mode
    public static function pictator_disable_debug()
    {
        self::destroy_htaccess();
        self::create_htaccess();
        status_header(200);
        wp_redirect(wp_get_referer());
        exit;
    }

    // callback for admin-posts.php, enables debug mode
    public static function pictator_enable_debug()
    {
        self::destroy_htaccess();
        self::create_htaccess(true);
        status_header(200);
        wp_redirect(wp_get_referer());
        exit;
    }

    // recursively computes the filesize of a directory by summing the filesizes of its contents recursively
    public static function dirsize($path)
    {
        $bytestotal = 0;

        $path = realpath($path);

        if ($path !== false) {
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
                $bytestotal += $object->getSize();
            }
        }

        return $bytestotal;
    }

    // recursively returns the number of files contained in a directory recursively.
    public static function count_files($dir)
    {
        $size = 0;

        foreach(scandir($dir) as $file) {
            if (!in_array($file, ['.','..'])) {
                if (is_dir(rtrim($dir, '/') . '/' . $file)) {
                    $size += self::count_files(rtrim($dir, '/') . '/' . $file);
                } else {
                    $size++;
                }
            }
        }

        return $size;
    }

    // re-implementation of the php unlink function, but instead of taking a filename as
    // the input, it takes a pattern that will be passed to the glob() function
    // This implementation deletes recursively, so USE WITH CAUTION!
    public static function unlink($pattern = "*")
    {
        foreach (glob($pattern) as $file) {
            if (is_dir($file)) {
                self::unlink($file . "/*");
                rmdir($file);
            } else {
                unlink($file);
            }
        }
    }

    private static function assignNotification($notificationString = '') 
    {
        self::$install_notifications[] = $notificationString;
    }
    
    public static function outputNotifications()
    {
        $styling = "color: #444;font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;font-size: 13px;";
        if (self::$install_notifications) {
            $output = '<div class="error notice" style="' . $styling . '">';
            $output .= '<p>' . implode(self::$install_notifications) . '</p>';
            $output .= '</div>';
            echo $output;
            die();
        }
    }
}

function register_settings()
{
    register_setting('atd-pictator','image_library');
    // die('register settings');
}

// this call initializes the path variables
Pictator::init();

register_activation_hook( __FILE__, array('Pictator', 'plugin_activated' ));
register_deactivation_hook( __FILE__, array('Pictator', 'plugin_deactivated' ));

if (is_admin()) {
    add_action( 'admin_menu',                          array('Pictator', 'pictator_menu') );
    add_action( 'admin_post_pictator_clear_cache',     array('Pictator', 'pictator_clear_cache' ));
    add_action( 'admin_post_pictator_disable_debug',   array('Pictator', 'pictator_disable_debug' ));
    add_action( 'admin_post_pictator_enable_debug',    array('Pictator', 'pictator_enable_debug' ));
    add_filter( 'post_thumbnail_html',                 array('Pictator', 'remove_image_attributes'), 10 );
    add_filter( 'image_send_to_editor',                array('Pictator', 'remove_image_attributes'), 10 );
    add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array('Pictator', 'pictator_settings_link'), 10 );
    add_action( 'admin_init', 'register_settings');
}
