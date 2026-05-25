#!/bin/bash
# Remove secrets from Git history

echo "🔧 Removing secrets from Git history..."
echo ""
echo "This will rewrite Git history. Make sure you have a backup!"
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    exit 1
fi

# Method 1: Using git filter-branch (built-in)
echo "Method 1: Using git filter-branch..."
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch HTTP_500_FIX.md test-connection.php" \
  --prune-empty --tag-name-filter cat -- 10f3054..HEAD

echo ""
echo "✅ History rewritten!"
echo ""
echo "Now force push:"
echo "git push origin main --force"
echo ""
echo "⚠️  WARNING: This will overwrite remote history!"
