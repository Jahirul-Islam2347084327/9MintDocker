FROM dhi.io/php:8.4.21-dev

# 1. Bring in Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 2. Copy files into the container
COPY . /app/

# 3. Update the package list so apt knows where npm lives, then install it
RUN apt update && apt install -y npm

# 4. Install backend dependencies
RUN composer install

# 5. Install frontend dependencies
RUN npm install