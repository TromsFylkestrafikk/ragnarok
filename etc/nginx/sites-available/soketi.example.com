# -*- mode: nginx; -*-

server {
    server_name soketi.example.com;
    root /var/www/html;
    access_log  /var/log/nginx/soketi-access.log;
    error_log  /var/log/nginx/soketi-error.log error;

    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    ssl_certificate /etc/letsencrypt/live/soketi.example.com-0003/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/soketi.example.com-0003/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.html index.htm index.php;

    charset utf-8;
    server_tokens off;

    location / {
        proxy_pass             http://127.0.0.1:6001;
        proxy_read_timeout     60;
        proxy_connect_timeout  60;
        proxy_redirect         off;

        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
