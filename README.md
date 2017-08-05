## Résaplan

Logiciel de gestion de salles et installations sportives sur les week-ends

### Installation

Pour installer Résaplan, vous devez disposer de :

 - Un navigateur internet en état de marche,
 - PHP 5,
 - MySQL

Téléchargez Résaplan puis modifiez le fichier resaplan.conf.php, en particulier 
la section avec vos identifiants de base de données :

   $db_host     = "";          // Adresse
   $db_username = "";          // Utilisateur
   $db_password = "";          // Mot de passe
   $db_name     = "";          // Nom de la base de données

Si vous le souhaitez, modifiez aussi les mots de passe d'administrateur par 
défaut.

Une fois ceci fait, allez à l'emplacement sur votre site où vous avez placé les
fichiers de Résaplan. Les bases de données seront installées.

Vous devrez ensuite modifier le fichier de configuration de Résaplan pour enlever
la variable *perform_install_new_db*. Vous devriez alors pouvoir entrer dans
l'interface d'administration de Résaplan.


