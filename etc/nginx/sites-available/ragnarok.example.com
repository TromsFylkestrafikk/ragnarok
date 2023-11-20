# -*- mode: nginx; -*-

server {
    server_name ragnarok.example.com;
    root /var/www/ragnarok/public;
    access_log /var/log/nginx/ragnarok.example.com-access.log;
    error_log /var/log/nginx/ragnarok.example.com-error.log;

    listen 443 ssl;
    listen [::]:443 ssl;
    ssl_certificate /etc/letsencrypt/live/ragnarok.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ragnarok.example.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    index index.php;
    charset utf-8;
    error_page 404 /index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    fastcgi_buffers 16 16k;
    fastcgi_buffer_size 32k;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
