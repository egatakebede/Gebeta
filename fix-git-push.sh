#!/bin/bash
# Permanent Fix for Git Push Issues

echo "🔧 Fixing Git Configuration..."

# Solution 1: Use SSH instead of HTTPS (Recommended)
echo ""
echo "Option 1: Switch to SSH (Recommended)"
echo "========================================="
echo "1. Check if you have SSH key:"
if [ -f ~/.ssh/id_rsa.pub ] || [ -f ~/.ssh/id_ed25519.pub ]; then
    echo "   ✅ SSH key exists"
    echo ""
    echo "   Your public key:"
    cat ~/.ssh/id_rsa.pub 2>/dev/null || cat ~/.ssh/id_ed25519.pub 2>/dev/null
    echo ""
    echo "   Copy the key above and add to GitHub:"
    echo "   https://github.com/settings/keys"
else
    echo "   ❌ No SSH key found. Generate one:"
    echo "   ssh-keygen -t ed25519 -C 'your_email@example.com'"
fi

echo ""
echo "2. Change remote to SSH:"
echo "   cd /home/e/Gebeta"
echo "   git remote set-url origin git@github.com:egatakebede/Gebeta.git"
echo "   git push origin main"

echo ""
echo ""
echo "Option 2: Use Personal Access Token"
echo "========================================="
echo "1. Create token at: https://github.com/settings/tokens"
echo "   - Click 'Generate new token (classic)'"
echo "   - Select scopes: repo (all)"
echo "   - Copy the token"
echo ""
echo "2. Configure Git credential helper:"
echo "   git config --global credential.helper store"
echo "   git push origin main"
echo "   # Enter username: egatakebede"
echo "   # Enter password: <paste_your_token>"
echo ""
echo "   Token will be saved in ~/.git-credentials"

echo ""
echo ""
echo "Option 3: Fix DNS Permanently"
echo "========================================="
echo "If you get 'Could not resolve host' errors:"
echo ""
echo "1. Add Google DNS to /etc/resolv.conf:"
echo "   sudo bash -c 'echo \"nameserver 8.8.8.8\" >> /etc/resolv.conf'"
echo "   sudo bash -c 'echo \"nameserver 8.8.4.4\" >> /etc/resolv.conf'"
echo ""
echo "2. Or configure systemd-resolved:"
echo "   sudo systemctl restart systemd-resolved"
echo "   resolvectl status"

echo ""
echo ""
echo "Quick Fix (Right Now):"
echo "========================================="
echo "Run this command to push with inline credentials:"
echo ""
echo "git push https://YOUR_TOKEN@github.com/egatakebede/Gebeta.git main"
echo ""
echo "Replace YOUR_TOKEN with your GitHub Personal Access Token"

echo ""
echo "✅ Done! Choose one option above and execute the commands."
