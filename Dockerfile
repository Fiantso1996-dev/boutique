# Utilise une image de base PHP avec Apache
FROM php:8.2-apache

# Copie tous les fichiers de votre projet dans le dossier de travail du serveur web
COPY . /var/www/html/

# Expose le port 80 pour le serveur web
EXPOSE 80
