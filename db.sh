#!/bin/bash
# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP first."
    exit 1
fi

# Create database
echo "Creating database..."
php bin/console doctrine:database:create --if-not-exists --env=prod

# Run migrations
echo "Running migration..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "Production database setup completed successfully!"
