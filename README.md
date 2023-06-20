### Features
- Updates prices with a cronjob

### Setup
#### Virtual hosts conf file　

```javascript
<VirtualHost *:80>
    DocumentRoot "PATH_TO_LOCAL_FILE"
    ServerName winner 
	ServerAlias winner
    ErrorLog "logs/winner-error.log"
    CustomLog "logs/winner-common.log" common
	
	SetEnv DB_HOSTNAME localhost
	SetEnv DB_PORT 3306
	SetEnv DB_NAME winner
	SetEnv DB_USERNAME winner
	SetEnv DB_PASSWORD winner
	
	SetEnv BINANCE_PUBLIC_KEY YOUR_BINANCE_PUBLIC_KEY
	SetEnv BINANCE_SECRET_KEY YOUR_BINANCE_SECRET_KEY	
</VirtualHost>

```

#### Run composer
```bash
composer update
```