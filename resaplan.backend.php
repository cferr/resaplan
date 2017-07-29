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

/***** CODE ONLY - NO HTML ******/

class User
{
    var $userId;
    var $friendlyName;
    var $userName;
    var $isAdmin;
    function canWrite($roomId)
    {
        global $prefix; 
        global $sqlConnection;
        $query = mysqli_query($sqlConnection, "SELECT * FROM `".$prefix."_roomManagers` WHERE `userId`='".$this->userId."' AND `roomId`='".$roomId."'");
        return ($query && mysqli_num_rows($query) > 0)?true:false; 
    }
}

class Event
{
    var $rowspan;
    var $eventText;
    var $sameAsPrevious;
    var $overriden;
}

class Room
{
    var $roomId;
    var $friendlyName;
}

function hashPassword($passwd, $salt)
{
    return hash_pbkdf2("sha256", $passwd, $salt, 1000);
}

function getUserSalt($userName)
{
    global $prefix; 
    global $sqlConnection;
    
    $query = mysqli_query($sqlConnection, "SELECT * FROM " . $prefix . "_users WHERE user='" . addslashes($userName)."'");
    if($query && mysqli_num_rows($query) > 0)
    {
        $arr = mysqli_fetch_array($query);
        return base64_decode($arr["salt"]);
    }
    
    return NULL;
}

function getUser($userName, $password)
{
    global $prefix; 
    global $sqlConnection;
    
    $query = mysqli_query($sqlConnection, "SELECT * FROM " . $prefix . "_users WHERE user='" . addslashes($userName)."'");
    if($query && mysqli_num_rows($query) > 0)
    {
        $values = mysqli_fetch_array($query);
        if($values["password"] == $password) // the salt is in
        {
            $usr = new User();
            $usr->userId = intval($values["num"]);
            $usr->userName = $values["user"];
            $usr->friendlyName = $values["friendlyName"];
            $usr->isAdmin = ($values["admin"] == 1);
            $usr->salt = $values["salt"];
            return $usr;
        }
    }
    return NULL;
}

// Returns an array containing user objects
function listUsers()
{
    global $sqlConnection;
    global $prefix;
    
    $users = array();
    $query = mysqli_query($sqlConnection, "SELECT * FROM `" . $prefix . "_users`");
    if($query && mysqli_num_rows($query) > 0)
    {
        $i = 0;
        $values = mysqli_fetch_array($query);
        while ($values != NULL)
        {
            $usr = new User();
            $usr->userId = $values["num"];
            $usr->userName = $values["user"];
            $usr->friendlyName = $values["friendlyName"];
            $usr->isAdmin = ($values["admin"] == 1);
            $usr->salt = $values["salt"];
            $users[$i] = $usr;
            $values = mysqli_fetch_array($query);
            $i++;
        }
        return $users;
    }
    
    return NULL;
}

function getDays($wOffset)
{
    $currentDay = date("l");
    $sign = ($wOffset >= 0)?"+ ":"-";
    $weekOffset = abs($wOffset);
    if ($currentDay != "Sunday" && $currentDay != "Saturday")
    {
        $saturday = strtotime("next Saturday " . $sign . $weekOffset . " weeks 00:00");
        $sunday = strtotime("next Sunday " . $sign . $weekOffset . " weeks 00:00");
    }
    else
    if ($currentDay == "Sunday")
    {
        $saturday = strtotime("last Saturday " . $sign . $weekOffset . " weeks 00:00");
        $sunday = strtotime("today " . $sign . $weekOffset . " weeks 00:00");
    }
    else
    {
        $saturday = strtotime("today ". $sign . $weekOffset . " weeks 00:00");
        $sunday = strtotime("next Sunday ". $sign . $weekOffset . " weeks 00:00");
    }

    $days = array(
        $saturday,
        $sunday
    );
    return $days;
}


function listRooms()
{
    global $prefix; 
    global $sqlConnection;
    $roomsQuery = mysqli_query($sqlConnection, "SELECT * FROM " . $prefix . "_rooms ORDER BY friendlyName ASC");
    $rooms = array();
    if (mysqli_num_rows($roomsQuery) > 0)
    {
        $i = 0;
        $array = mysqli_fetch_array($roomsQuery);
        while ($array != NULL)
        {
            $curRoom = new Room();
            $curRoom->friendlyName = $array["friendlyName"];
            $curRoom->roomId = $array["num"];
            $rooms[$i] = $curRoom;
            $array = mysqli_fetch_array($roomsQuery);
            $i++;
        }
        return $rooms;
    }

    return NULL;
}


function getPrintTable()
{
    global $mode;
    if($_SESSION["user"] == NULL && $mode != view)
        return false;
    return true;
}

function logIn($username, $password)
{
    global $userOffset;
    global $userInfo;
    global $displayedURL;
    $canContinue = false;
    $userInfo = getUser($username, $password);
    if($userInfo)
        return true;
    else
    {
        // Authentication failed
        session_destroy();
        return false;
    }
    
}

function handlePostEvents()
{
    global $prefix; 
    global $sqlConnection;
    global $userInfo;
    if($userInfo == NULL)
        return ERROR_MISSINGAUTH;
    
    $retval = SUCCESS;
    
    switch(actionFromString($_POST["action"]))
    {
        case editentry:
            foreach($_POST as $key => $value)
            {
                if ($key != "action")
                {
                    $valsplit = split("_", $key, 2);
                    $date = $valsplit[0];
                    $curDate = strtotime("now");
                    $salle = str_replace("_", " ", $valsplit[1]);
                    $query = mysqli_query($sqlConnection, "SELECT * FROM `" . $prefix . "_reserv` WHERE date='" . $date . "' AND `room`='" . $salle . "'") or $retval = ERROR_MYSQLI;
                    if($userInfo->canWrite($salle) && $curDate <= $date)
                    {
                        if (mysqli_num_rows($query) > 0)
                        {
                            // Item already exists. Replace it.
                            if ($value != "" && $value != NULL)
                            {
                                $query = mysqli_query($sqlConnection, "UPDATE `" . $prefix . "_reserv` SET `event`='" . addslashes($value) . "' WHERE date='" . $date . "' AND `room`='" . $salle . "'") or $retval =  ERROR_MYSQLI;
                            }
                            else
                            {
                                $query = mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_reserv` WHERE `date`='" . $date . "' AND `room`='" . $salle . "'") or $retval = ERROR_MYSQLI;
                            }
                        }
                        else if ($value != "" && $value != NULL)
                        {
                            // Item doesn't exist: create it.
                            $query = mysqli_query($sqlConnection, "INSERT INTO `" . $prefix . "_reserv`(`event`,`date`,`room`) VALUES('" . addslashes($value) . "','" . $date . "','" . $salle . "')") or $retval = ERROR_MYSQLI;
                        }
                    } else $retval = ERROR_MISSINGAUTH;
                }
            }
            break;
        case selfpasswd:
            $oldPasswd = $_POST["oldpass"];
            $hashdOldPasswd = hashPassword($oldPasswd, base64_decode($userInfo->salt));
            
            $newPasswd = $_POST["newpass"];
            if($newPasswd == "") {
                $retval = ERROR_EMPTYPASSWORD;
                break;
            }
            $salt = openssl_random_pseudo_bytes(32);
            $hashdNewPasswd = hashPassword($newPasswd, $salt);
            
            // check old password
            if(getUser($userInfo->userName, $hashdOldPasswd))
            {
                $query = mysqli_query($sqlConnection, "UPDATE `" . $prefix . "_users` SET `password`='" . $hashdNewPasswd . "', `salt` = '" . base64_encode($salt) . "' WHERE `num` = " . $userInfo->userId) or $retval = ERROR_MYSQLI;
                $_SESSION["password"] = $hashdNewPasswd;
            } else $retval = ERROR_PASSCHANGE_OLDWRONG;
            
            
            
            break;
            
        case edituser:
            if(!$userInfo->isAdmin) {
                $retval = ERROR_MISSINGAUTH;
                break;
            }
            if(!is_numeric($_POST["usered_num"])) {
                $retval = ERROR_INVALIDARG;
                break;
            }
            $num = intval($_POST["usered_num"]);
            
            $setFriendlyName = ($_POST["usered_friendlyName"] != "");
            $friendlyName = addslashes($_POST["usered_friendlyName"]);
            
            $setPassword = ($_POST["usered_password"] != "");
            $salt = "";
            if($setPassword)
                $salt = openssl_random_pseudo_bytes(32);
            if($setPassword && $_POST["usered_password"] == "") {
                $retval = ERROR_EMPTYPASSWORD;
                break;
            }
            
            $password = hashPassword($_POST["usered_password"], $salt);
            
            $setUserName = ($_POST["usered_user"] != "");
            $userName = addslashes($_POST["usered_user"]);
            
            if($setUserName) {
                $query = mysqli_query($sqlConnection, "SELECT * FROM `" . $prefix . "_users` WHERE `user` = '" . $userName . "'");
                
                if($query && mysqli_num_rows($query) > 0) {
                    $retval = ERROR_DUPLICATE;
                    break;
                }
            }
            
            $setAdmin = true;
            $admin = ($_POST["usered_isAdmin"] != NULL);
            if($userInfo->userId == $num && !$admin) {
                $retval = ERROR_NOADMINLEAVE;
                break;
            }
            
            if($num > 0) {
                $query = mysqli_query($sqlConnection, "UPDATE `" . $prefix . "_users` SET " .
                    implode(", ", array_filter(array(
                        (($setFriendlyName)?"`friendlyName` = '" . $friendlyName . "'" : ""),
                        (($setPassword)?"`password` = '" . $password . "', `salt` = '" . base64_encode($salt) . "'" : "" ),
                        (($setUserName)?"`user` = '" . $userName . "'" : "" ),
                        (($setAdmin)?"`admin` = " . ($admin?"1":"0") : "")
                      ))) .
                " WHERE num='" . $num . "'") or $retval = ERROR_MYSQLI;
            } else if($userInfo->isAdmin && $num == -1 && $setFriendlyName && $setUserName && $setPassword) {
                $querystr = "INSERT INTO `" . $prefix . "_users`(`user`,`password`, `salt` ,`friendlyName`,`admin`) VALUES('" . $userName . "','" . $password . "','" . base64_encode($salt) . "','" . $friendlyName . "','" . ($admin?"1":"0") . "')";
                $query = mysqli_query($sqlConnection, $querystr) or $retval = ERROR_MYSQLI;
            } else $retval = ERROR_INVALIDARG;
            
            // Is it us ? Then change the session's password...
            if($userInfo->userId == $num) {
                if($setUserName) $_SESSION["user"] = $userName;
                if($setPassword) $_SESSION["password"] = $password;
            }
            
            break;
        case deluser:
            if(!$userInfo->isAdmin) {
                $retval = ERROR_MISSINGAUTH;
                break;
            }
            if(!is_numeric($_POST["del_num"])) {
                $retval = ERROR_INVALIDARG;
                break;
            }
            $num = intval($_POST["del_num"]);
            
            if($userInfo->userId == $num) {
                $retval = ERROR_NOADMINLEAVE;
                break;
            }
            
            if($num > 0) {
                $query = mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_users` WHERE num='" . $num . "'") or $retval = ERROR_MYSQLI;
                $query = mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_roomManagers` WHERE userId='" . $num . "'") or $retval = ERROR_MYSQLI;
            } else $retval = ERROR_NOSUCHENTRY;
            break;
            
        case editroom:
            if(!$userInfo->isAdmin) {
                $retval = ERROR_MISSINGAUTH;
                break;
            }
            if(!is_numeric($_POST["ed_num"])) {
                $retval = ERROR_INVALIDARG;
                break;
            }
            $num = intval($_POST["ed_num"]);
            $setFriendlyName = ($_POST["ed_friendlyName"] != "");
            $friendlyName = addslashes($_POST["ed_friendlyName"]);
            if($num > 0 && $setFriendlyName) {
                $query = mysqli_query($sqlConnection, "UPDATE `" . $prefix . "_rooms` SET `friendlyName` = '" . $friendlyName . "' WHERE num='" . $num . "'") or $retval = ERROR_MYSQLI;
            } else if($num == -1 && $setFriendlyName) {
                $query = mysqli_query($sqlConnection, "INSERT INTO `" . $prefix . "_rooms`(`friendlyName`) VALUES('" . $friendlyName . "')") or $retval = ERROR_MYSQLI;
            } else $retval = ERROR_MYSQLI;
            
            break;
        case delroom:
            if(!$userInfo->isAdmin) {
                $retval = ERROR_MISSINGAUTH;
                break;
            }
            $num = intval($_POST["del_num"]);
            if($num > 0) {
                $query = mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_rooms` WHERE num='" . $num . "'") or $retval = ERROR_MYSQLI;
                $query = mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_roomManagers` WHERE `roomId`='" . $num . "'") or $retval = ERROR_MYSQLI;
                $query = mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_reserv` WHERE `room`='" . $num . "'") or $retval = ERROR_MYSQLI;
            } else $retval = ERROR_NOSUCHENTRY;
            
            break;
        case editmanage:
            if(!$userInfo->isAdmin) {
                $retval = ERROR_MISSINGAUTH;
                break;
            }
            
            foreach($_POST as $key => $value)
            {
                // Only take the hidden fields
                if(!preg_match("/.hidden/i", $key))
                    continue;
                
                $input_name = preg_split("/.hidden/", $key)[0];
                $splitted = preg_split("/_/", $input_name);
                if(count($splitted) == 2 && is_numeric($splitted[0]) && is_numeric($splitted[1]))
                {
                    $userId = intval($splitted[0]);
                    $roomId = intval($splitted[1]);
                    
                    // Then it's OK, we can check
                    if($_POST[$input_name] != NULL)
                    {
                        // Grant
                        $isExisting = mysqli_query($sqlConnection, "SELECT * FROM `" . $prefix . "_roomManagers` WHERE `userId`='".$userId."' AND `roomId`='".$roomId."'");
                        
                        if($isExisting && mysqli_num_rows($isExisting) == 0)
                        {
                            mysqli_query($sqlConnection, "INSERT INTO `" . $prefix . "_roomManagers`(`userId`, `roomId`) VALUES(".$userId.",  ".$roomId.")") or $retval = ERROR_MYSQLI;
                        }
                    }
                    else
                    {
                        // Deny
                        mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_roomManagers` WHERE `userId`=".$userId." AND `roomId`=".$roomId."") or $retval = ERROR_MYSQLI;
                    }
                    
                }
            }
        
            break;
        
        default:
            $retval = NORESULT;
            break;
    }
    
    return $retval;
}

function deletePastEvents($delay)
{
    global $prefix; 
    global $sqlConnection;
    $currentDay = date("l");
    if ($currentDay != "Saturday" && $currentDay != "Sunday") //delete the past events. Don't run these on weekends, this is safer.
    {
        $day = strtotime("-" . $delay);
        $query = mysqli_query($sqlConnection, "DELETE FROM `" . $prefix . "_reserv` WHERE `date` < " . $day);
    }
}

function checkOffsetValid($delayInWeeks, $maxDelay)
{
    $day = strtotime((($delayInWeeks >= 0)?"+":"-") . abs($delayInWeeks) . " weeks");
    $dayMin = strtotime("-" . $maxDelay);

    return !($day < $dayMin);
}

function table_exists($table) 
{
    global $sqlConnection;
    
    if ($result = $sqlConnection->query("SHOW TABLES LIKE '".$table."'")) {
        if($result->num_rows == 1) {
            return true;
        }
    }
    
    return false;
}

function install()
{
    global $prefix;
    global $sqlConnection;
    global $installUserName;
    global $installPassword;
    
    
    if(!table_exists($prefix . "_users")) {
        echo "Création BDD utilisateurs... ";
        mysqli_query($sqlConnection, "CREATE TABLE `" . $prefix . "_users` (
            `num` int(10) NOT NULL AUTO_INCREMENT,
            `user` text NOT NULL,
            `password` text NOT NULL,
            `admin` int(1) DEFAULT NULL,
            `friendlyName` text NOT NULL,                                                                                                                                                                 
            `salt` text,                                                                                                                                  
            PRIMARY KEY (`num`)                                                                                                                                                                           
            ) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8") or print(mysqli_error($sqlConnection));
        $salt = openssl_random_pseudo_bytes(32);
        $hashd = hashPassword($installPassword, $salt);
        
        mysqli_query($sqlConnection, "INSERT INTO `" . $prefix . "_users`(`user`, `friendlyName`, `password`, `salt`, `admin`) VALUES('" . $installUserName . "', 'Administrateur', '" . $hashd ."', '" . base64_encode($salt). "', '1')") or print(mysqli_error($sqlConnection));
    }
    
    if(!table_exists($prefix . "_rooms")) {
        echo "Création BDD installations... ";
        mysqli_query($sqlConnection, "CREATE TABLE `" . $prefix . "_rooms` (
            `num` int(10) NOT NULL AUTO_INCREMENT,
            `friendlyName` text,
            PRIMARY KEY (`num`)
            ) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8") or print(mysqli_error($sqlConnection));
    }
    
    if(!table_exists($prefix . "_roomManagers")) {
        echo "Création BDD autorisations... ";
        mysqli_query($sqlConnection, "CREATE TABLE `" . $prefix . "_roomManagers` (
            `num` int(11) NOT NULL AUTO_INCREMENT,
            `roomId` int(11) NOT NULL,
            `userId` int(11) NOT NULL,
            PRIMARY KEY (`num`)
            ) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8") or print(mysqli_error($sqlConnection));
    }
    
    if(!table_exists($prefix . "_reserv")) {
        echo "Création BDD réservations... ";
        mysqli_query($sqlConnection, "CREATE TABLE `" . $prefix . "_reserv` (
            `num` int(10) NOT NULL AUTO_INCREMENT,
            `date` bigint(20) NOT NULL,
            `room` int(11) DEFAULT NULL,
            `event` text,
            PRIMARY KEY (`num`)
            ) ENGINE=MyISAM AUTO_INCREMENT=0 DEFAULT CHARSET=utf8") or print(mysqli_error($sqlConnection));
    }
    
    $success = (table_exists($prefix . "_users"))
        && (table_exists($prefix . "_rooms"))
        && (table_exists($prefix . "_roomManagers"))
        && (table_exists($prefix . "_reserv"));
        
    if(!$success)
        die("Échec de configuration : " . mysqli_error($sqlConnection));
    else
        die("<p>Création des tables effectuée, veuillez retirer <code>perform_install_new_db</code> du fichier de configuration de Résaplan.</p>");
}


function rescue()
{
    global $prefix;
    global $sqlConnection;
    global $rescueUserName;
    global $rescuePassword;
    
    // If no users, create a default admin one
//     $testQuery = mysqli_query($sqlConnection, "SELECT * FROM `" . $prefix . "_users` WHERE `admin` = 1");
//     if($testQuery && mysqli_num_rows($testQuery) == 0)
//     {
        // add an admin account if it doesn't already exist -otherwise grant it admin privileges
        $salt = openssl_random_pseudo_bytes(32);
        $hashd = hashPassword($rescuePassword, $salt);
        
        $testQuery = mysqli_query($sqlConnection, "SELECT * FROM `" . $prefix . "_users` WHERE `user` = '". $rescueUserName ."'");
        if($testQuery && mysqli_num_rows($testQuery) == 0) {
            mysqli_query($sqlConnection, "INSERT INTO `" . $prefix . "_users`(`user`, `friendlyName`, `password`, `salt`, `admin`) VALUES('" . $rescueUserName . "', 'Administrateur', '" . $hashd ."', '" . base64_encode($salt). "', '1')");
        }
        else
            mysqli_query($sqlConnection, "UPDATE `" . $prefix . "_users` SET `admin` = 1, `password` = '" . $hashd . "' WHERE `user` = '" . $rescueUserName . "'");
        
//     }
}


?>
