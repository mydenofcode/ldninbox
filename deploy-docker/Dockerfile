FROM ubuntu:24.04

# Prevent interactive prompt issues
ARG DEBIAN_FRONTEND=noninteractive

# Install Apache, PHP, and extensions
RUN apt-get update && \
    apt-get install -y apache2 php libapache2-mod-php php-mysql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Enable Apache mods
RUN a2enmod rewrite

# Copy app into container
COPY ./app /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose Apache port
EXPOSE 80

# Start Apache in foreground
CMD ["apachectl", "-D", "FOREGROUND"]
