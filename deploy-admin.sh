#!/bin/bash

# Gebeta - Trigger Render Deployment
# This script commits and pushes changes to trigger automatic deployment on Render

echo "🚀 Deploying Gebeta Admin Panel to Render..."

# Add all changes
git add .

# Commit with timestamp
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
git commit -m "Deploy admin panel updates - $TIMESTAMP"

# Push to main branch (triggers Render deployment)
git push origin main

echo "✅ Changes pushed to GitHub"
echo "⏳ Render will automatically deploy in 2-3 minutes"
echo "📍 Check deployment status at: https://dashboard.render.com"
echo ""
echo "Once deployed, access admin panel at:"
echo "https://gebeta-52mf.onrender.com/admin/dashboard.php"
