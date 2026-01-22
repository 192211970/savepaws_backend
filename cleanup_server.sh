#!/bin/bash

# ========================================
# Server Cleanup Script for SavePaws Backend
# ========================================
# This script removes duplicate "- Copy" files from the server
# Run this on your server via SSH

echo "========================================="
echo "SavePaws Backend Cleanup Script"
echo "========================================="
echo ""
echo "This will DELETE all files with '- Copy' in the name"
echo "Current directory: $(pwd)"
echo ""

# Count files to be deleted
copy_count=$(find . -maxdepth 1 -name "*Copy*" -type f | wc -l)
echo "Found $copy_count files with 'Copy' in the name"
echo ""

if [ $copy_count -eq 0 ]; then
    echo "No duplicate files found. Server is clean!"
    exit 0
fi

# List files to be deleted
echo "Files to be deleted:"
find . -maxdepth 1 -name "*Copy*" -type f -exec basename {} \;
echo ""

# Ask for confirmation
read -p "Do you want to delete these files? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Cleanup cancelled."
    exit 0
fi

# Delete files
echo ""
echo "Deleting duplicate files..."
find . -maxdepth 1 -name "*Copy*" -type f -delete

# Verify deletion
remaining=$(find . -maxdepth 1 -name "*Copy*" -type f | wc -l)

if [ $remaining -eq 0 ]; then
    echo "✅ Success! All duplicate files deleted."
else
    echo "⚠️ Warning: $remaining files still remain"
fi

echo ""
echo "========================================="
echo "Cleanup Complete!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Update db.php with correct credentials"
echo "2. Create uploads/ folder if missing"
echo "3. Set permissions: chmod 755 uploads/"
echo "4. Test database connection"
echo ""
