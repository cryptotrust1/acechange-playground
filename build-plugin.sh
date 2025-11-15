#!/bin/bash
# Build script pre vytvorenie WordPress plugin ZIP

echo "Building AceChange SEO Plugin for WordPress..."

# Zistenie verzie z hlavného súboru
VERSION=$(grep "Version:" acechange-seo-plugin/acechange-seo.php | awk '{print $3}')
echo "Version: $VERSION"

# Názov výstupného ZIP
OUTPUT="acechange-seo-plugin-v${VERSION}.zip"

# Vytvorenie ZIP
cd acechange-seo-plugin/
zip -r "../${OUTPUT}" . \
  -x "*.git*" \
  -x "*tests/*" \
  -x "*.md" \
  -x ".gitignore" \
  -x "*.zip"

cd ..

echo ""
echo "✅ Build completed!"
echo "📦 File: ${OUTPUT}"
echo "📊 Size: $(ls -lh ${OUTPUT} | awk '{print $5}')"
echo ""
echo "Upload this file to WordPress:"
echo "WordPress Admin → Plugins → Add New → Upload Plugin"
