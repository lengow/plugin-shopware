#!/bin/bash
# Build archive for Shopware module
# Step :
#     - Remove .DS_Store
#     - Remove .README.md
#     - Remove .idea
#     - Clean export folder
#     - Clean logs folder
#     - Clean translation folder
#     - Clean tools folder
#     - Remove .gitFolder and .gitignore

remove_if_exist(){
    if [ -f $1 ]; then
        rm $1
    fi
}

remove_directory(){
    if [ -d "$1" ]; then
        rm -rf $1
    fi
}

remove_files(){
    DIRECTORY=$1
    FILE=$2
    find $DIRECTORY -name $FILE -nowarn -exec rm -rf {} \;
    echo "- Delete $FILE : ""$VERT""DONE""$NORMAL"""
}

remove_directories(){
    DIRECTORY=$1
    find $DIRECTORY -maxdepth 1 -mindepth 1 -type d -exec rm -rf {} \;
    echo "- Delete $FILE : ""$VERT""DONE""$NORMAL"""
}

# Check parameters
if [ -z "$1" ]; then
    echo 'Version parameter is not set'
    echo
    exit 0
else
    VERSION="$1"
    ARCHIVE_NAME='Lengow.'$VERSION'.zip'
fi

# Variables
FOLDER_TMP="/tmp/Backend"
FOLDER_LOGS="/tmp/Backend/Lengow/Logs"
FOLDER_EXPORT="/tmp/Backend/Lengow/Export"
FOLDER_TOOLS="/tmp/Backend/Lengow/Tools"
FOLDER_TRANSLATION="/tmp/Backend/Lengow/Snippets/backend/Lengow/yml"

VERT="\\033[1;32m"
ROUGE="\\033[1;31m"
NORMAL="\\033[0;39m"
BLEU="\\033[1;36m"

# Process
echo
echo "#####################################################"
echo "##                                                 ##"
echo "##       ""$BLEU""Lengow Shopware""$NORMAL"" - Build Module          ##"
echo "##                                                 ##"
echo "#####################################################"
echo
FOLDER="$(dirname "$(dirname "$(pwd)")")"
echo $FOLDER
if [ ! -d "$FOLDER" ]; then
	echo "Folder doesn't exist : ""$ROUGE""ERROR""$NORMAL"""
	echo
	exit 0
fi

# Generate translations
php translate.php
echo "- Generate translations : ""$VERT""DONE""$NORMAL"""
# Create files checksum
php checkmd5.php
echo "- Create files checksum : ""$VERT""DONE""$NORMAL"""
#remove TMP FOLDER
remove_directory $FOLDER_TMP
#copy files
cp -rRp $FOLDER $FOLDER_TMP
# Remove .gitkeep
remove_files $FOLDER_TMP ".gitkeep"
# Remove dod
remove_files $FOLDER_TMP "dod.md"
# Remove Readme
remove_files $FOLDER_TMP "README.md"
# Remove .git
remove_files $FOLDER_TMP ".git"
# Remove .gitignore
remove_files $FOLDER_TMP ".gitignore"
# Remove .DS_Store
remove_files $FOLDER_TMP ".DS_Store"
# Remove .idea
remove_files $FOLDER_TMP ".idea"
# Clean Log Folder
remove_files $FOLDER_LOGS "*.txt"
echo "- Clean logs folder : ""$VERT""DONE""$NORMAL"""
# Clean export folder
remove_directories $FOLDER_EXPORT
echo "- Clean export folder : ""$VERT""DONE""$NORMAL"""
# Clean export folder
remove_directory $FOLDER_TOOLS
echo "- Remove Tools folder : ""$VERT""DONE""$NORMAL"""
#remove TMP FOLDER_TRANSLATION
remove_directory $FOLDER_TRANSLATION
echo "- Remove Translation yml folder : ""$VERT""DONE""$NORMAL"""

# Make zip
cd /tmp
zip "-r" $ARCHIVE_NAME "Backend"
echo "- Build archive : ""$VERT""DONE""$NORMAL"""
mv $ARCHIVE_NAME ~/Bureau