FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    subversion \
    default-mysql-client \
    curl \
    unzip \
    git \
    $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN pecl install uopz \
    && docker-php-ext-enable uopz

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Install PHPUnit
# RUN curl -sL https://phar.phpunit.de/phpunit-9.phar -o /usr/local/bin/phpunit \
#     && chmod +x /usr/local/bin/phpunit

# Install WP-CLI
RUN curl -sL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp

# Copy client SSL config — disables SSL for ALL mysql/mysqladmin calls globally
COPY docker/mysql-client.cnf /etc/mysql/conf.d/ssl-disable.cnf

COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]

# Open an interactive shell
# ENTRYPOINT ["bash"]