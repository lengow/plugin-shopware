# Module Shopware Lengow

## Installation ##

### Installation de shopware ###

1 - Aller sur le site de shopware : http://community.shopware.com/Downloads_cat_448.html?_ga=1.137823606.712675709.1462865288

2 - Sélectionner la version à télécharger (le module étant compatible à partir de la 4.3.0)

3 - Décompresser le projet dans /var/www/shopware/shopware_x.x.x

4 - Modification du fichier /etc/hosts

    echo "127.0.0.1 shopware_x-x-x.local" >> /etc/hosts

5 - Création du fichier virtualhost d'apache

    sudo vim /etc/apache2/sites-enabled/shopware_x-x-x.conf
    <VirtualHost *:80>
    DocumentRoot /var/www/shopware/shopware_x.x.x/
    ServerName shopware_x-x-x.local
    <Directory /var/www/shopware/shopware_x.x.x/>
        Options FollowSymLinks Indexes MultiViews
        AllowOverride All
    </Directory>
        ErrorLog /var/log/apache2/shopware-x-x-x-error_log
        CustomLog /var/log/apache2/shopware-x-x-x-access_log common
    </VirtualHost>
6 - Rédémarrer apache

    sudo service apache2 restart

7 - Creation de la base de données

    mysql -u root -p -e "CREATE DATABASE shopware-x-x-x";

8 - Se connecter sur Shopware pour lancer l'installation

    http://shopware_x-x-x.local

9 - Installer un jeu de données

    Voir la partie "Installation" du README.md présent sur https://github.com/shopware/shopware

### Récupération des sources ###

Cloner le repo dans votre espace de travail :

    cd /var/www/shopware/shopware_x.x.x/engine/Shopware/Plugins/Community/Backend/
    git clone git@bitbucket.org:lengow-dev/shopware.git Lengow/

### Installation dans Shopware ###

1 - Se connecter au Back office de Shopware à l'addresse http://shopware_x-x-x.local/backend/ (défaut : u=demo p=demo)

2 - Installer le plugin via le plugin manager (Configuration > Plugin Manager > Installed)

## Traduction ##

Pour traduire le projet il faut modifier les fichier *.yml dans le répertoire :
/var/www/shopware/shopware_x.x.x/engine/Shopware/Plugins/Community/Backend/Lengow/Snippets/backend/Lengow/yml/

### Installation de Yaml Parser ###

    sudo apt-get install php5-dev libyaml-dev
    sudo pecl install yaml

### Mise à jour des traductions ###

Une fois les traductions terminées, il suffit de lancer le script de mise à jour de traduction :

    cd /var/www/shopware/shopware_x.x.x/engine/Shopware/Plugins/Community/Backend/Lengow/Tools/
    php Translate.php