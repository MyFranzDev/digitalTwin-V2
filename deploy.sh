#!/bin/bash
# Deploy script for digitalTwin-V2 FBA enhancements
# Usage: ./deploy.sh

SERVER="newrality@iad1-shared-e1-31.dreamhost.com"
APP_DIR="newrality.com/demo/digitaltwin"
PASSWORD="touchlabs2"

echo "🚀 Starting deployment..."

# Test connection
echo "📡 Testing SSH connection..."
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER "hostname" || {
    echo "❌ SSH connection failed. Server may have temporarily blocked this IP."
    echo "Wait 5-10 minutes and try again."
    exit 1
}

# Pull latest changes from GitHub
echo "⬇️  Pulling latest changes from GitHub..."
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER "cd $APP_DIR && git pull origin master" || {
    echo "⚠️  No git repository found on server. Continuing with manual upload..."
}

# Run database migration
echo "🗄️  Running database migration..."
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER "cd $APP_DIR && php scripts/migrate_fba_results.php" && {
    echo "✅ Database migration completed"
} || {
    echo "⚠️  Database migration may have failed. Check manually."
}

# Set permissions
echo "🔐 Setting file permissions..."
sshpass -p "$PASSWORD" ssh -o StrictHostKeyChecking=no $SERVER "chmod -R 755 $APP_DIR/scripts/*.py && chmod +x $APP_DIR/scripts/*.php"

echo "✅ Deployment completed!"
echo "🌐 Test at: https://newrality.com/demo/digitaltwin/"
