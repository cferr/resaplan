<?php
//     Résaplan - manage sports buildings on weekends
//     Copyright (C) 2011-2017 Corentin Ferry
// 
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, either version 3 of the License, or
//     (at your option) any later version.
// 
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
// 
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//     

// Commenter ceci une fois Résaplan installé.
$perform_install_new_db = true;
$installUserName = "admin"; // Cet utilisateur administrateur sera créé.
$installPassword = "admin"; // Ceci est son mot de passe.


// Secours. À utiliser pour retrouver l'accès administrateur sans refaire
// toutes les bases de données. Décommenter pour utiliser.
// $rescue = true;
// $rescueUserName = "rescue"; // Cet utilisateur administrateur sera créé.
// $rescuePassword = "rescue"; // Ceci est son mot de passe.


// Préfixe des tables de Résaplan dans la base de données (permet les 
// installations multiples sur le même serveur)
$prefix = "resaplan";

// Titre affiché dans la barre de navigation (court)
$navTitle = "Résaplan";

// Titre affiché dans la barre de titre des navigateurs
$pageTitle = "Résaplan";

// Durée pendant laquelle conserver des événements passés (les deux durées, en
// semaines et en jours, doivent être égales)
$retainDelay = "21 days";
$retainDelayInWeeks = 3;

// L'URL à laquelle Résaplan fonctionnera. Laisser vide en cas de doute.
$displayedURL = "";

// Coordonnées du serveur de base de données
$db_host     = "";          // Adresse
$db_username = "";          // Utilisateur
$db_password = "";          // Mot de passe
$db_name     = "";          // Nom de la base de données


// Cette fonction définit l'en-tête des pages de Résaplan.
function pageHead()
{
?>
<h1 class="text-center">
Résaplan
</h1>
<?php
}


// Cette fonction définit les mentions légales. Adaptez-la à votre site.
function cookieLegalMessage()
{
?>
<div class="col-sm-12">
<h3>Cookies</h3>
<p>Ce site utilise un cookie pour identifier l'utilisateur qui souhaite modifier
le tableau des réservations.</p>
<p>Un tel cookie ne permet pas de tracer l'activité, l'historique de navigation,
les noms d'utilsateur et mots de passe utilisés sur d'autres sites que le 
présent site internet par l'utilisateur. Il n'est utilisé qu'à des fins de 
vérification de droits de modification, et est supprimé lorsque l'utilisateur
clique sur "Déconnexion".</p>
<p>Aucun cookie n'est déposé sur l'ordinateur de l'utilisateur lorsque celui-ci
n'entre pas de nom d'utilisateur et mot de passe en y étant invité (par exemple
en cliquant sur le bouton "Modifier").</p>
<h3>MIT License</h3>
<p>Résaplan utilise <a href="https://getbootstrap.com/">Bootstrap</a>, pour lui
permettre d'avoir son apparence. Bootstrap est sous licence MIT.</p>
<pre>
The MIT License (MIT)

Copyright (c) 2011-2016 Twitter, Inc.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
</pre>

<h3>GPL License</h3>
<p>Résaplan est distribué sous licence GPL.</p>
<pre>
Copyright (C) 2011-2017 Corentin Ferry

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see &lt;http://www.gnu.org/licenses/&gt;.
</pre>

</div>
<?php
}

// Cette fonction vous permet d'insérer votre propre copyright en bas des 
// tableaux de Résaplan.
function copyrightInfo()
{
?>
<h6>(c) <?php echo date("Y"); ?> Votre Site</h6>
<?php
}

?>
