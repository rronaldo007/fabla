#!/bin/bash

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "PHP is not installed. Please install PHP first."
    exit 1
fi

# Create database
echo "Creating database..."
php bin/console doctrine:database:create --if-not-exists

# Create migration
echo "Creating migration..."
php bin/console make:migration -n

# Run migration
echo "Running migration..."
php bin/console doctrine:migrations:migrate -n

# Load fixtures
echo "Loading fixtures..."
php bin/console doctrine:fixtures:load -n

echo "Database setup completed successfully!"