
### Features

- Updates prices with a cronjob

  

### Setup

#### Apache  : Virtual hosts conf file

```javascript

<VirtualHost  *:80>
	DocumentRoot  "PATH_TO_YOUR_DOCUMENT_ROOT"
	ServerName  winner
	ServerAlias  winner
	ErrorLog  "logs/winner-error.log"
	CustomLog  "logs/winner-common.log" common
	
	SetEnv  DB_HOSTNAME  `YOUR_DB_HOST`
	SetEnv  DB_PORT  `YOUR_DB_PORT`
	SetEnv  DB_NAME  `YOUR_DB_NAME`
	SetEnv  DB_USERNAME  `YOUR_DB_USERNAME`
	SetEnv  DB_PASSWORD  `YOUR_DB_PASSWORD`
	SetEnv  BINANCE_PUBLIC_KEY  `YOUR_BINANCE_PUBLIC_KEY`
	SetEnv  BINANCE_SECRET_KEY  `YOUR_BINANCE_SECRET_KEY`
</VirtualHost>
```
#### Nginx  : conf file

```javascript

server {
    server_name winner.internet;

    root `PATH_TO_YOUR_DOCUMENT_ROOT`;
    index index.php index.html;

    location / {
        try_files $uri $uri.html $uri/ @extensionless-php;
        index index.html index.htm index.php;
    }

    location ~ \.php$ {
        #try_files $uri =404;

        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        include snippets/fastcgi-php.conf;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 3001;
        fastcgi_intercept_errors on;
        fastcgi_buffer_size 32k;

        fastcgi_param DB_HOSTNAME `YOUR_DB_HOST`;
        fastcgi_param DB_PORT `YOUR_DB_PORT`;
        fastcgi_param DB_NAME `YOUR_DB_NAME`;
        fastcgi_param DB_USERNAME `YOUR_DB_USERNAME`;
        fastcgi_param DB_PASSWORD `YOUR_DB_PASSWORD`;

        fastcgi_param BINANCE_PUBLIC_KEY `YOUR_BINANCE_PUBLIC_KEY`;
        fastcgi_param BINANCE_SECRET_KEY `YOUR_BINANCE_SECRET_KEY`;
    }

    location ~ /\.ht {
        deny all;
    }

    location @extensionless-php {
        rewrite ^(.*)$ $1.php last;
    }
} 

```

  

#### Run composer

```bash

composer update

```