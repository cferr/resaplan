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

require("resaplan.conf.php");
require("resaplan.constants.php");
require("resaplan.backend.php");
require("resaplan.frontend.php");

/****** MAIN SCRIPT ******/

if($_COOKIE || $_POST["user"])
    session_start();

// The week offset, user-specified : which week do they want to get ?
$userOffset = intval(($_GET["when"] == NULL) ? 0 : $_GET["when"]);
$userOffsetProvided = ($_GET["when"] != NULL);

// Translations for the days.
$trans = array(
    "Saturday" => "Samedi",
    "Sunday" => "Dimanche"
);

// Hours of an open day.
$minHour = 8;
$maxHour = 20;

$sqlConnection = mysqli_connect($db_host, $db_username, $db_password) or die("Error connecting to database");
mysqli_select_db($sqlConnection, $db_name) or die("Cannot select database"); //mysqli_error($sqlConnection));

if($perform_install_new_db) {
    install();
}

if($rescue) {
    rescue();
?>
<p>Veuillez enlever la variable <code>rescue</code> du fichier de configuration
de Résaplan pour continuer.</p>
<?php
    die();
}

// The credentials, if any.
if($_POST["user"] && $_POST["password"])
{
    $_SESSION["user"] = $_POST["user"];
    $salt = getUserSalt($_SESSION["user"]);
    if($salt) {
        $_SESSION["password"] = hashPassword($_POST["password"], $salt);
//         echo "hashd is " . $_SESSION["password"];
    }
}


// Destroy the credentials if needed.
$disconnectSuccessful = false;
if ($_GET["die"] == true && $_SESSION)
{
    if($_SESSION["user"] != NULL) {
        $_SESSION["user"] = NULL;
        $_SESSION["password"] = NULL;
        $disconnectSuccessful = true;
    }
    session_destroy();

}

$isAjax = ($_GET["type"] == "ajax");
$mode = modeFromString($_GET["mode"]);

$errCode = NORESULT;

if($_SESSION["user"])
{
    if(logIn($_SESSION["user"], $_SESSION["password"]))
    {
        $errCode = handlePostEvents();
    } else $errCode = ERROR_WRONGCREDENTIALS;
}

if(!$isAjax) {

    pageScaffoldBegin();
    
    $days = getDays($userOffset);

    // delete events that will no longer be used
    deletePastEvents($retainDelay);

    printDocumentHeader();

    nav();

    if($errCode != SUCCESS && $errCode != NORESULT)
        errMessage($errCode);
    
    if($errCode == SUCCESS)
        successMessage($errCode);

    if($disconnectSuccessful)
        disconnectSuccessfulMessage();

    switch($mode) {
        case view:
            outputTables($minHour, $maxHour, $days, false);
            break;
        case edit:
            if($userInfo) {
                outputEditInfoMessage();
                outputTables($minHour, $maxHour, $days, true);
            } else 
                loginForm();
            break;
        case admin:
            if($userInfo) {
                if($userInfo->isAdmin) 
                    outputAdminTables();
                else
                    accessDeniedError();
            } else loginForm();
            break;
        case chgpass:
            if($userInfo)
                outputChgPass();
            else 
                loginForm();
            break;
        case cookies:
            cookieLegalMessage();
            break;
        default:
            errMessage(ERROR_INVALIDARG);
            break;
    }

    printCopyright();
    
    pageScaffoldEnd();

} else {
    if($errCode != SUCCESS && $errCode != NORESULT)
        errMessage($errCode);
    else if($errCode == SUCCESS)
        successMessage($errCode);
    
    
    switch(actionFromString($_POST["action"]))
    {
        case edituser:
        case deluser:
        case showusers:
            if($userInfo->isAdmin)
                userAdminTable();
            else
                accessDeniedError();
            break;
        
        case editroom:
        case delroom:
        case showrooms:
            if($userInfo->isAdmin)
                roomAdminTable();
            else
                accessDeniedError();
            break;
        
        case editmanage:
        case showmanage:
            if($userInfo->isAdmin)
                manageAdminTable();
            else
                accessDeniedError();
            break;
    }
}

mysqli_close($sqlConnection);

?>
