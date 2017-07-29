<?php

// Constants
define("view", 0);
define("edit", 1);
define("admin", 2);
define("chgpass", 3);
define("cookies", 4);

define("noaction", 0);
define("editentry", 1);
define("editroom", 2);
define("edituser", 3);
define("editmanage", 4);
define("delroom", 5);
define("deluser", 6);
define("showrooms", 7);
define("showusers", 8);
define("showmanage", 9);
define("selfpasswd", 10);

// POST error constants
define("NORESULT", -1);
define("SUCCESS", 0);
define("ERROR_MYSQLI", 1);
define("ERROR_DUPLICATE", 2);
define("ERROR_INVALIDARG", 3);
define("ERROR_MISSINGAUTH", 4);
define("ERROR_NOSUCHENTRY", 5);
define("ERROR_WRONGCREDENTIALS", 6);
define("ERROR_NOADMINLEAVE", 7);
define("ERROR_PASSCHANGE_OLDWRONG", 8);
define("ERROR_EMPTYPASSWORD", 9);

function modeString($mode)
{
    switch($mode) {
        case view:
            return "";
        case edit:
            return "edit";
        case admin:
            return "admin";
        case chgpass:
            return "chgpass";
        case cookies:
            return "cookies";
        default:
            return ""; // view
    }
    
}

function modeFromString($str)
{
    if($str == "edit")
        return edit;
    if($str == "admin")
        return admin;
    if($str == "chgpass")
        return chgpass;
    if($str == "cookies")
        return cookies;    
    return view;
}


function actionString($action)
{
    switch($action) {
        case editentry:
            return "editentry";
        case editroom:
            return "editroom";
        case edituser:
            return "edituser";
        case editmanage:
            return "editmanage";
        case delroom:
            return "delroom";
        case deluser:
            return "deluser";
        case showrooms:
            return "showrooms";
        case showusers:
            return "showusers";
        case showmanage:
            return "showmanage";
        case selfpasswd:
            return "selfpasswd";
        default:
            return "noaction";
    }
    
}

function actionFromString($action)
{
    if($action == "editentry")
        return editentry;
    if($action == "editroom")
        return editroom;
    if($action == "edituser")
        return edituser;
    if($action == "editmanage")
        return editmanage;
    if($action == "delroom")
        return delroom;
    if($action == "deluser")
        return deluser;
    if($action == "showrooms")
        return showrooms;
    if($action == "showusers")
        return showusers;
    if($action == "showmanage")
        return showmanage;
    if($action == "selfpasswd")
        return selfpasswd;
    
        
    return noaction;
}

function errorString($err)
{
    global $sqlConnection;
    switch($err)
    {
    case SUCCESS:
        return "L'opération a réussi.";
        break;
    case ERROR_MYSQLI:
        return "Erreur dans la requête vers la base de données : " . mysqli_error($sqlConnection) . ". Veuillez contacter le webmaster.";
        break;
    case ERROR_DUPLICATE:
        return "Il existe déjà un utilisateur portant le même nom d'utilisateur.";
        break;
    case ERROR_INVALIDARG:
        return "Un des arguments passés n'est pas valide.";
        break;
    case ERROR_MISSINGAUTH:
        return "Vous n'avez pas les droits requis pour cette action.";
        break;
    case ERROR_NOSUCHENTRY:
        return "L'entrée demandée n'existe pas.";
        break;
    case ERROR_WRONGCREDENTIALS:
        return "Les informations d'identification saisies sont invalides.";
        break;
    case ERROR_NOADMINLEAVE:
        return "Un administrateur ne peut se démettre de son rôle d'administrateur.";
        break;
    case ERROR_PASSCHANGE_OLDWRONG:
        return "L'ancien mot de passe que vous avez saisi est erroné.";
        break;
    case ERROR_EMPTYPASSWORD:
        return "Le mot de passe saisi ne peut être vide.";
        break;
    }
    return "";
}



?>
