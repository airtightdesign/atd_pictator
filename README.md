# ATD Pictator for Wordpress #

A Wordpress plugin that allows dynamic resizing/cropping of images that have been uploaded to the default wordpress 'uploads' folder.

### Installation ###

#### Wordpress Admin ####

Clone the repo to wp-content/plugins/atd_pictator

Activate the plugin from the wordpress Plugins page

Visit the settings page by navigating to **Settings >> ATD Pictator** to verify that everything is correctly installed.

#### NGINX ####

Open up your vhost file and enter this in:

```
location /wp-content/uploads {
	rewrite ^/(.+)/(.+)/(.+)\.(jpg|jpeg|png|gif|bmp)$ /wp-content/plugins/atd_pictator/index.php last;
}
```

#### Manual ####

Clone the repo to wp-content/plugins/atd_pictator

```
hg clone ssh://hg@bitbucket.org/airtightdesign/atd_pictator

```
Put .htaccess file in uploads folder (make sure mod_rewrite is enabled first!)
```
cp wp-content/plugins/atd_pictator/htaccess wp-content/uploads/.htaccess
```
Create cache directory
```
mkdir wp-content/plugins/atd_pictator/cache;
```
Set cache directory ownership
```
chgrp www-data wp-content/plugins/atd_pictator/cache;
```
Set cache directory permissions
```
chmod 775 wp-content/plugins/atd_pictator/cache;
chmod g+s wp-content/plugins/atd_pictator/cache;
```

### Clearing the Cache ###

#### Wordpress Admin ####
Visit **Settings >> ATD Pictator**

#### Manual ####
```
rm -rf wp-content/plugins/atd_pictator/cache/*
```

### Test It ###

Assuming you have uploaded the file lena.jpg through the wordpress admin, it should now exist somewhere in the up to /wp-content/uploads/YYYY/MM/lena.jpg.  Open the image in a fresh tab and try adding the following string to the end of the url: ?a=crop&w=100&h=100&x=10&y=10

### Available Parameters ###

* a - the 'action' to perform (resize|crop - defaults to 'resize')
* r - the type of resize (contain|widen|heighten|cover)
* w - the width
* h - the height
* x - the x offset
* y - the y offset

#### Image Resizing ####

Contain an image within a bounding box
```
/wp-content/uploads/YYYY/MM/lena.jpg?r=contain&w=200&h=200
```
![pictator.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator.jpg)
![arrow.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/arrow.jpg)
![pictator-contain-200x200.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator-contain-200x200.jpg)


Resize an image by specifying the width, preserving aspect ratio
```
/wp-content/uploads/YYYY/MM/lena.jpg?r=widen&w=200
```
![pictator.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator.jpg)
![arrow.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/arrow.jpg)
![pictator-widen-200.jpg](https://bitbucket.org/repo/xdMGXB/images/3705539239-lena-widen-200.jpg)


Resize an image by specifying the height, preserving aspect ratio
```
/wp-content/uploads/YYYY/MM/lena.jpg?r=heighten&h=200
```
![pictator.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator.jpg)
![arrow.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/arrow.jpg)
![pictator-heighten-200.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator-heighten-200.jpg)


Resize an image to cover the provided box dimensions.  This can clip the image.
```
http://thesite.com/wp-content/uploads/YYYY/MM/lena.jpg?r=cover&w=100&h=200
```
![pictator.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator.jpg)
![arrow.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/arrow.jpg)
![pictator-cover-100x200.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator-cover-100x200.jpg)



#### Image Cropping ####

The following will crop the image to 100x100 pixels, starting at position 100,100
```
/wp-content/uploads/YYYY/MM/lena.jpg?a=crop&w=100&h=100&x=100&y=100
```
![pictator.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator.jpg)
![arrow.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/arrow.jpg)
![pictator-crop-100x100-100x100.jpg](http://www.airtightdesign.com/wp-content/uploads/2017/04/pictator-crop-100x100-100x100.jpg)


### Who do I talk to? ###

wjbrown@airtightdesign.com
