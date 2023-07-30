Todo for you: 
1) Add these two php files to the folder: /var/www/links
2) Install php-fpm 8.2 or higher
3) Edit the index.php file to Add your shortner links...
4) Edit analytics.php to assign a password.
5) Install a web server -- see below

To enable Short URLs for your domain running on Nginx for Linux OS:
$ sudo nano /etc/sites-enabled/shortner
```
server {
    listen 80; # see Chat-GPT for help making this work for ssl 443...
    server_name links.local; # edit for your domain name here

    root /var/www/links; # edit to be your web-root
    index index.php;

    location / {
        try_files $uri $uri/ @rewrite;
    }

    location @rewrite {
        rewrite ^/(.*)$ /index.php?s=$1 last;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```
### Please ensure you have PHP-FPM configured and running correctly for the PHP processing to work. Also, adjust the fastcgi_pass directive to point to the correct PHP-FPM socket based on your setup.
### Test before reloading nginx: $ nginx -t
## THEN do: $ sudo service nginx reload

--------------------------------------------------------

If you perferr Apache2, then do the following to setup that web-server:
$ sudo a2enmod rewrite

Replace your_domain.com with your doamin name...

$ nano /etc/apache2/sites-available/your_domain.com.conf
```<VirtualHost *:80>
    ServerName your_domain.com
    DocumentRoot /var/www/links

    <Directory /var/www/links>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    RewriteEngine On
    RewriteBase /

    RewriteRule ^index\.php/([^/]+)$ index.php?s=$1 [L,QSA]
</VirtualHost>
```
When traffic is low or non-existant, restart apache2:
$ sudo service apache2 restart

--------------------------------------------------------
OKay, -- 

Now that your Web Server is setup:
Test: Open with a web browser http://links.local/test OR your domain name...

$ curl -v http://links.local/test
See the Location is now: https://google.com
