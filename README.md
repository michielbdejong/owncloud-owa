# owncloud-owa

This owncloud app lets you add open web apps to your owncloud instance.

# install

These instructions assume standard install with apache2 on a debian-like system.

* First, install owncloud as normal. 
* Copy the open\_web\_apps folder into /var/www/apps/
* choose a storage origin. This can be an additional subdomain, or an additional port on which Apache should listen, like https://example.com:44344
* add this origin as an additional vhost to apache config and point it to /var/www/apps/open\_web\_apps/storage\_root/
* make sure the AllowOverride directive for this vhost allows /var/www/apps/open\_web\_apps/storage\_root/.htaccess to set its RewriteRule
* sudo apt-get install libxattr1-dev pear
* sudo pecl install xattr
* copy the 'webfinger' file to /var/www/.well-known/webfinger
* assuming there are no files other than 'webfinger' in that directory, then in /etc/apache2/sites-enabled/default-ssl, add:

    <Directory /var/www/.well-known/>
       Header set Access-Control-Allow-Origin "*"
       Header set Content-Type "application/json"
    </Directory>

* sudo service apache2 restart
* log in to owncloud as an admin and activate the app
* configure the storage origin in the owncloud admin settings

# license

You hereby have my permission to use this app unlicensed, or under the MIT license, or under the AGPL license. I borrowed the "Open web apps"
rocket glyph from Mozilla Marketplace, since I know of no other good generic icon for open web apps, currently.
