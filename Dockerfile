# Utilise une image de base PHP avec Apache
FROM php:8.2-apache

# Copie tous les fichiers de votre projet dans le dossier de travail du serveur web
COPY . /opt/lampp/htdocs/gestion_caisse_finale

# Expose le port 80 pour le serveur web
EXPOSE 80
