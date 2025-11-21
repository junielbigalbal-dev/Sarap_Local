WORKDIR /var/www/html

# Copy project files to Nginx root
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy configs
COPY default.conf /etc/nginx/conf.d/default.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY zz-render.conf /usr/local/etc/php-fpm.d/zz-render.conf

# Create necessary directories with proper permissions
RUN mkdir -p /var/log/php-fpm /var/log/nginx \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 8080 (Render uses this)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/health.php || exit 1

# Start supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
