FROM php:8.4.2-fpm
# 1. Bring in Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 2. Copy files into the container
COPY . /app/

# 3. Update packages and install Node 22
RUN apt update
RUN apt install -y curl gpg
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
RUN apt install -y nodejs

# 4. Install system image libraries
RUN apt install -y libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev graphicsmagick imagemagick unzip libzip-dev

# 5. Configure and install the PHP GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install gd

# 6. Install the PHP zip extension (Fixes the slow Composer download issue)
RUN docker-php-ext-install zip

# 7. Install the MySQL driver (Crucial so Laravel can talk to your database!)
RUN docker-php-ext-install pdo_mysql
    
# 8. Install backend dependencies
RUN composer install

# 9. Install frontend dependencies
RUN npm install

# 10. Compile frontend assets (Fixed syntax)
RUN npm run build

# Copy the entrypoint script into the container's system path
COPY entrypoint.sh /usr/local/bin/entrypoint.sh

# Make sure the container has permission to execute the script
RUN chmod +x /usr/local/bin/entrypoint.sh

# Tell Docker to execute this script the moment the container boots up
ENTRYPOINT ["entrypoint.sh"]