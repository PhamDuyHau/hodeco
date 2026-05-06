Options -Indexes

##############################################
# SECURITY HEADERS
##############################################
<IfModule mod_headers.c>
    Header always set Content-Security-Policy "upgrade-insecure-requests;"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"

	<FilesMatch "\.(css|js)$">
		Header set X-Robots-Tag "noindex, nofollow"
	</FilesMatch>
</IfModule>

##############################################
# PROTECT SENSITIVE FILES
##############################################
<FilesMatch "^(\.env|\.env\..*|composer\.json|composer\.lock|package\.json|package-lock\.json|phpunit\.xml|wp-config\.php|wp-config-sample\.php|README\.md|LICENSE|CHANGELOG(\.md)?)$">
    Require all denied
</FilesMatch>

##############################################
# REWRITE ENGINE
##############################################
RewriteEngine On

# Block VCS folders
RewriteRule (^|/)\.git - [F,L]
RewriteRule (^|/)\.svn - [F,L]

# Block access to parent directory files
RewriteRule ^\.\./ - [F,L]

# Disable PHP in uploads
RewriteRule ^wp-content/uploads/.*\.(?:php|phar|phtml|php3|php4|php5|php7|phps)$ - [F,L]

##############################################
# USER-AGENT DETECTION
##############################################
SetEnvIfNoCase User-Agent "(googlebot|bingbot|applebot)" ALLOW_BOT
SetEnvIfNoCase User-Agent "(facebookexternalhit|facebookcatalog|meta-externalagent|meta-externalfetcher)" ALLOW_BOT
SetEnvIfNoCase User-Agent "(twitterbot|linkedinbot|slackbot|discordbot|telegrambot|whatsapp)" ALLOW_BOT
SetEnvIfNoCase User-Agent "(gptbot|gpt-crawler|anthropic|claudebot|perplexity|pplx)" BAD_BOT
SetEnvIfNoCase User-Agent "(mj12|dotbot|megaindex|seoscanners|crawler4j|nikto|zgrab|sqlmap|blex|seranking)" BAD_BOT
SetEnvIfNoCase User-Agent "(amazonbot|alexa)" BAD_BOT
SetEnvIfNoCase User-Agent "(baiduspider|360spider|sogou|yisouspider|bytespider)" BAD_BOT
SetEnvIfNoCase User-Agent "(yandex|mail\.ru)" BAD_BOT
# Note: curl/wget removed from BAD_BOT to allow debugging and Facebook access
# Note: aws/route53 excluded to avoid breaking AWS health checks
SetEnvIf User-Agent "^$" BAD_BOT

# Block bad bots (but NOT for media files, and NOT for ALLOW_BOT crawlers)
RewriteCond %{ENV:ALLOW_BOT} !=1
RewriteCond %{ENV:BAD_BOT} =1
RewriteCond %{REQUEST_URI} !\.(gif|jpe?g|png|webp|avif|svg|ico|woff2?|ttf|otf|eot|mp4|webm|mp3|pdf)$ [NC]
RewriteRule ^ - [F,L]

# Enforce HTTPS
RewriteCond %{HTTPS} off
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

##############################################
# COMPRESSION (GZIP)
##############################################
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE application/javascript
  AddOutputFilterByType DEFLATE application/json
  AddOutputFilterByType DEFLATE application/rss+xml
  AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
  AddOutputFilterByType DEFLATE application/xhtml+xml
  AddOutputFilterByType DEFLATE application/xml
  AddOutputFilterByType DEFLATE font/opentype
  AddOutputFilterByType DEFLATE font/otf
  AddOutputFilterByType DEFLATE font/ttf
  AddOutputFilterByType DEFLATE image/svg+xml
  AddOutputFilterByType DEFLATE image/x-icon
  AddOutputFilterByType DEFLATE text/css
  AddOutputFilterByType DEFLATE text/html
  AddOutputFilterByType DEFLATE text/javascript
  AddOutputFilterByType DEFLATE text/plain
  AddOutputFilterByType DEFLATE text/xml
</IfModule>

##############################################
# STATIC CACHE
##############################################
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 6 months"
    ExpiresByType image/png "access plus 6 months"
    ExpiresByType image/gif "access plus 6 months"
    ExpiresByType image/webp "access plus 6 months"
    ExpiresByType image/avif "access plus 6 months"
    ExpiresByType image/svg+xml "access plus 6 months"
    ExpiresByType image/x-icon "access plus 6 months"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType font/woff2 "access plus 6 months"
    ExpiresByType font/woff "access plus 6 months"
    ExpiresByType font/ttf "access plus 6 months"
    ExpiresByType font/otf "access plus 6 months"
</IfModule>
