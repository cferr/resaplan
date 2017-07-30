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

/***** HTML FUNCTIONS that OUTPUT DATA to the PAGE *****/

// Nice table for just one day.
function niceTable($minHour, $maxHour, $day)
{
    global $prefix; 
    global $sqlConnection;
    
    $rooms = listRooms();
    if($rooms == NULL) {
        noRoomMessage();
        return false;
    }
    tableHeader($day, $rooms);
    
    $events = array();
    for ($hour = $minHour; $hour <= $maxHour; $hour++)
    {
        $curtime = $day + $hour * 3600;
        foreach($rooms as $salle)
        {
            $evt = new Event();

            // cell content

            $query = mysqli_query($sqlConnection, "SELECT * FROM " . $prefix . "_reserv WHERE `date` = " . $curtime . " AND `room` = '" . $salle->roomId . "'");
            $query_result = NULL;
            if ($query && mysqli_num_rows($query) > 0) $query_result = mysqli_fetch_array($query);
            $evt->eventText = ($query_result) ? $query_result["event"] : "";

            // bet on lazy evaluation !

            $evt->sameAsPrevious = ($hour > $minHour && $events[$hour - 1][$salle->roomId]) ? true : false;
            $evt->rowspan = 1; // calculate this later
            $evt->overriden = false;
            $events[$hour][$salle->roomId] = $evt;
        }
    }

    // merge events

    foreach($rooms as $salle)
    {
        $hour = $minHour;
        $curText = "";
        $curSpan = 1;
        while ($hour <= $maxHour)
        {
            if ($events[$hour][$salle->roomId]->eventText == $curText)
            {
                if ($hour > $minHour)
                {
                    $curSpan+= 1;
                    $events[$hour][$salle->roomId]->overriden = true; // clear
                }
            }
            else
            {
                if ($events[$hour - $curSpan][$salle->roomId]) $events[$hour - $curSpan][$salle->roomId]->rowspan = $curSpan;
                $curText = $events[$hour][$salle->roomId]->eventText;
                $curSpan = 1;
            }

            $hour++;
        }

        if ($events[$hour - $curSpan][$salle->roomId]) $events[$hour - $curSpan][$salle->roomId]->rowspan = $curSpan;
    }

    // display events

    for ($hour = $minHour; $hour <= $maxHour; $hour++)
    {
        $curtime = $day + $hour * 3600;

        // beautify the timestamp to make a date that can be displayed

        $dispTime = date("H:i", $curtime);
//         $endtime = $hour + 1;
        insertRow();
        insertColumn();
        echo $dispTime;
        endColumn();

        foreach($rooms as $salle)
        {
            $evt = $events[$hour][$salle->roomId];
            if ($evt->overriden == false) // sure it's not null !
            {
                if ($evt->eventText != "")
                {
                    insertColumn("background-color:#33CC66 !important;", $evt->rowspan);
                    echo stripslashes($evt->eventText);
                    endColumn();
                }
                else
                {
                    insertColumn("color:#333333; font-style: italic", $evt->rowspan);
                    echo "Libre";
                    endColumn();
                }
            }
        }

        endRow();
    }

    endTable();
    return true;
}


// admin table for a single day
function editTable($minHour, $maxHour, $day)
{
    global $prefix; 
    global $sqlConnection;
    global $userInfo;
    
    $currentTime = strtotime("now");
    
    $rooms = listRooms();
    if($rooms == NULL) {
        noRoomMessage();
        return;
    }
    tableHeader($day, $rooms);
    
    for ($hour = $minHour; $hour <= $maxHour; $hour++)
    {
        $curtime = $day + $hour * 3600;

        // beautify the timestamp to make a date that can be displayed

        $dispdate = date("H:i", $curtime);
        $endtime = $hour + 1;
        insertRow();
        
        // display the hour as a first column
        insertColumn();
        echo $dispdate . " à " . (($endtime < 10) ? "0" : "") . $endtime . ":00";
        endColumn();

        foreach($rooms as $salle)
        {
            $query = mysqli_query($sqlConnection, "SELECT * FROM " . $prefix . "_reserv WHERE `date` = " . $curtime . " AND `room` = '" . $salle->roomId . "'");
            $cell_content = "";
            if ($query && mysqli_num_rows($query) > 0)
            {
                $data = mysqli_fetch_array($query);
                $cell_content = $data["event"];
            }

            insertColumn(($cell_content != "")?"background-color:#33CC66 !important;":"");
            if($userInfo->canWrite($salle->roomId) && $curtime >= $currentTime)
                insertTextArea($curtime . "_" . $salle->roomId, ($cell_content != "")?stripslashes($cell_content):"");
            else
                echo (($cell_content != "")?stripslashes($cell_content):"");
            
            endColumn();
        }
        
        endRow();
    }
    endTable();
}

function outputTables($minHour, $maxHour, $days, $adminMode)
{
    global $userOffset;
    fullColumnBegin();
    outputDays($days[0], $days[1]);
    if($adminMode)
        adminFormHeader();
    
    
    global $retainDelay;
    colDivHeader();
    if(checkOffsetValid($userOffset, $retainDelay)) 
    {
        foreach($days as $day)
        {
            if($adminMode)
                editTable($minHour, $maxHour, $day);
            else
                if(!niceTable($minHour, $maxHour, $day))
                break;
        }
    } 
    else 
    {
        printOffsetInvalid();
    }
    colDivFooter();
    
    
    if($adminMode)
        adminFormEnd();
    fullColumnEnd();
}



function printOffsetInvalid()
{
?>
<div class="col-sm-12">
<div class="alert alert-danger">
<p class="bg-danger">Il n'existe plus d'enregistrements pour ces dates.</p>
</div>
</div>
<?php
}

function colDivFooter()
{
?>
</div>
<?php
}

function colDivHeader()
{
?>
<div class="row">
<?php
}

function fullColumnBegin()
{
?>
<div class="col-sm-12">
<?php
}

function fullColumnEnd()
{
?>
</div>
<?php
}

function noRoomMessage()
{
?>
<div class="col-sm-12">
<div class="alert alert-danger">
<p class="text-center alert-danger">Aucune salle n'a été définie. Référez-vous au tableau d'affichage présent en mairie pour connaître les réservations.</p>
</div>
</div>
<?php
}

function errMessage($err)
{
?>
<div class="col-sm-12">
<div class="alert alert-danger alert-dismissable">
<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
<p class="bg-danger"><span class="glyphicon glyphicon-warning-sign"></span> <?php echo errorString($err); ?></p>
</div>
</div>
<?php
}

function successMessage($code)
{
?>
<div class="col-sm-12">
<div class="alert alert-success alert-dismissable">
<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
<p class="bg-success"><span class="glyphicon glyphicon-info-sign"></span> <?php echo errorString($err); ?></p>
</div>
</div>
<?php
}


function insertColumn($style = "", $rowspan = "")
{
?>
<td <?php 
    if($style != "") { ?> style="<?php echo $style; ?>" <?php }
    if($rowspan != "") { ?> rowspan="<?php echo $rowspan; ?>" <?php } ?> >
<?php
}

function endColumn()
{
?>
</td>
<?php
}

function insertRow()
{
?>
<tr>
<?php
}

function endRow()
{
?>
</tr>
<?php
}

function insertTextArea($id, $content)
{
?>
<textarea class="form-control" name="<?php echo $id; ?>" 
            id="<?php echo $id; ?>">
<?php echo $content; ?>
</textarea>
<?php
}

function endTable()
{
?>
</table>
</div>
<?php
}

function outputDays($saturday, $sunday)
{
    global $retainDelayInWeeks;
    global $userOffset;
    global $displayedURL;
    global $mode;
?>
<div class="col-sm-8">
<p class="lead">Week-end des <?php
        echo date("d/m/Y", $saturday); ?> et <?php
        echo date("d/m/Y", $sunday); ?>
</p>
</div>
<div class="col-sm-4 text-center">
        <?php if($userOffset > -$retainDelayInWeeks) { ?>
<a class="btn btn-default noprint" href="<?php echo $displayedURL; ?>?<?php if($mode != view) { ?>mode=<?php echo modeString($mode); ?>&<?php } ?>when=<?php echo $userOffset - 1;?>"><span class="glyphicon glyphicon-step-backward"></span> Précédent</a>
    <?php } ?>
<a class="btn btn-default noprint" href="<?php echo $displayedURL; ?>?<?php if($mode != view) { ?>mode=<?php echo modeString($mode); ?>&<?php } ?>when=<?php echo $userOffset + 1;?>"><span class="glyphicon glyphicon-step-forward"></span> Suivant</a>
<?php if($mode == view) { ?>
<a class="btn btn-default noprint" href="javascript:window.print()"><span class="glyphicon glyphicon-print"></span> Imprimer</a>
<?php } ?>
</div>
<?php
}

function printDocumentHeader()
{
    global $pageTitle;
?>
<div class="noprint">
<?php
    pageHead();
?>
</div>
<div class="printurl">
<h1 class="text-center"><?php echo $pageTitle; ?></h1>
</div>
<?php
?>
<?php
}

function printCopyright()
{
    global $displayedURL;
?>
<div class="col-sm-12">
<?php copyrightInfo(); ?>
<h3 class="printurl">Une version à jour de ce tableau est disponible sur <?php echo $displayedURL; ?></h3>
</div>
<?php
}


function printWeekScroll()
{
    global $userOffset;
    global $displayedURL;
    global $mode;
?>
<form class="navbar-form navbar-left">
<?php
    if ($userOffset > - 2)
    { } 
    ?>
    <select class="form-control" name="date" id="date" onchange="document.location.href='<?php
    echo $displayedURL; ?>?<?php
    if ($mode != view)
    {
        echo "mode=". modeString($mode) ."&amp;";
    } ?>when='+this.value;">
<option>Choisir un week-end</option>

<?php
    $current = $userOffset;
    $count = 0;
    $proxDays = getDays(0);
    $sat = date("d/m/Y", $proxDays[0]);
    $sun = date("d/m/Y", $proxDays[1]);
    
?>
<option value="0">Le plus proche (<?php
    echo $sat . " et " . $sun ; ?>)</option>
<?php

    // 6 months back

    $current = $current - 26;
    if ($current < - 2) // we keep two weeks back - don't hardcode it this way !
    {
        $current = - 2;
    }

    while ($count < 53)
    {
        $sat = date("d/m/Y", strtotime( (($current >= 0)?"+":"-") . abs($current) . " weeks", $proxDays[0]));
        $sun = date("d/m/Y", strtotime( (($current >= 0)?"+":"-") . abs($current) . " weeks", $proxDays[1]));
?>
<option value="<?php
        echo $current; ?>"><?php
        echo $sat; ?> et <?php
        echo $sun ; ?></option>
<?php
        $count++;
        $current++;
    }

?>
</select>
</form>

<?php
}

function loginForm()
{
    global $userOffset;
    global $mode;
    global $userOffsetProvided;
?>
<div class="col-sm-12">
<h2>Identifiez-vous pour gérer les réservations.</h2>
<form name="login" id="login" action="?<?php if($mode != view) { echo "mode=" . modeString($mode) . "&"; } 
    if($userOffsetProvided) { echo "when=" . $userOffset; } ?>" method="post" class="form-horizontal">
<div class="form-group">
<label for="user" class="col-sm-2">Nom d'utilisateur:</label>
<div class="col-sm-5">
<input class="form-control" type="text" name="user" placeholder="Nom d'utilisateur"/>
</div>
</div>
<div class="form-group">
<label for="password" class="col-sm-2">Mot de passe:</label>
<div class="col-sm-5">
<input class="col-sm-10 form-control" type="password" name="password" placeholder="Mot de passe"/>
</div>
</div>
<div class="col-sm-offset-2 col-sm-5">
<input class="btn btn-primary" type="submit" value="Connexion" />
<a class="btn btn-default" href="/?<?php if($userOffsetProvided) { echo "when=" . $userOffset; }?>">Annuler</a>
</div>
</form>
</div>
<?php
}

function adminFormHeader()
{
    global $userOffset;
?>
<form name="modif" id="modif" action="?mode=edit&when=<?php
    echo $userOffset; ?>" method="post">
<input type="hidden" name="action" id="action" value="<?php echo actionString(editentry); ?>" />
<?php
}

function adminFormEnd()
{
?>
<br />
<input class="btn btn-primary btn-lg btn-block" type="submit" value="Valider les changements" />
</form>
<?php
}

// Displays the given day plus the name of the rooms.

function tableHeader($day, $rooms)
{
    global $trans; // day translations
    
    
    if($rooms == NULL)
        return;
    
    $disp = date("l d/m", $day);
    foreach($trans as $orig => $trad)
    {
        $disp = str_replace($orig, $trad, $disp);
    }

?>
<div class="col-md-6">
<table cellpadding="0" cellspacing="1" border="1" style="border:thin; text-align:center; vertical-align:top; margin: 10px auto auto auto; font-size:20px; display: inline-block;">
<tr><td colspan="<?php
    echo count($rooms) + 1; ?>" ><?php
    echo $disp; ?></td></tr>
<tr>
<td><b>Heure</b></td>
<?php
    foreach($rooms as $room)
    {
?>
<th style="width:150px; text-align: center"><b><?php
        echo $room->friendlyName; ?></b></th>
<?php
    }

?>
</tr>
<?php
}

function navRight()
{
    global $userInfo;
    global $displayedURL;
    global $userOffset;
    global $userOffsetProvided;
    global $mode;
    
    ?>
            <ul class="nav navbar-nav navbar-right">
                <?php if($mode == view) { ?>
                <li><a href="<?php echo $displayedURL; ?>?mode=<?php echo modeString(edit); ?>&when=<?php
    echo $userOffset; ?>">Modifier</a></li>
                <?php } ?>
                <?php if($mode != view && $userInfo) { ?>
                <li><a href="<?php echo $displayedURL; ?>?when=<?php
    echo $userOffset; ?>">Visualiser</a></li>
                <?php } ?>
                <?php if($userInfo) { ?>
                    <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false" style="font-weight: bold;"><?php echo $userInfo->friendlyName; ?> <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <?php if($userInfo->isAdmin) { ?>
                        <li><a href="<?php echo $displayedURL; ?>?mode=<?php echo modeString(admin); ?>">Administration</a></li>
                        <?php } ?>
                        <li><a href="<?php echo $displayedURL; ?>?mode=<?php echo modeString(chgpass); ?>">Changer de mot de passe</a></li>
                    </ul>
                    </li>
                <li> <p class="navbar-btn"><a class="btn btn-default" href="<?php echo $displayedURL; ?>?die=true<?php
                if($userOffsetProvided) { echo "&when=" . $userOffset; }?>">Déconnexion</a></p></li>
                <?php } ?>
                
            </ul>
    <?php
}


function nav()
{
    global $navTitle;
    global $mode;
?>
<div class="col-sm-12">
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand"><?php echo $navTitle; ?>
            
            </a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <?php if($mode == view || $mode == edit) printWeekScroll(); ?>
            <?php navRight(); ?>
        </div>
    </div>
</nav>
</div>

<div id="errorContainer" class="col-sm-12 alert alert-danger alert-dismissable hidden">
<a href="#" class="close" data-hide="errorContainer" aria-label="close">&times;</a>
<p class="bg-danger"><span class="glyphicon glyphicon-warning-sign"></span> <span id="errorMessage"></span></p>
</div>

<script>

$("[data-hide]").on("click", function(){
    $("#" + $(this).attr("data-hide")).addClass("hidden");
});

</script>

<?php
}

function outputEditInfoMessage()
{
?>
<div class="col-sm-12">
<div class="alert alert-info">
<p class="bg-info"><span class="glyphicon glyphicon-info-sign"></span> N'oubliez pas d'enregistrer vos changements à l'aide du bouton "Valider les changements" situé en bas des tableaux. Il n'est pas possible de modifier des cases dont la date est déjà passée.</p>
</div>
</div>
<?php
}

function userAdminTable() {
    global $mode;
    global $displayedURL;
    global $userInfo;
    $users = listUsers();
    $rooms = listRooms();
?>

<h3>Utilsateurs</h3>
<p>La liste des utilisateurs du programme est affichée ici. Vous pouvez en ajouter, les renommer, changer leur mot de passe, les supprimer.</p>
<p>Notez que chaque utilisateur peut changer son propre mot de passe. Un administrateur a accès à cette page.</p>
<table class="table table-condensed">
<tr>
<th>Nom affiché</th><th>Nom d'utilisateur</th><th>Mot de passe</th><th>Administrateur</th><th>Modifier</th>
</tr>
<?php
    foreach($users as $usr) {
?>
<tr>
    <td><?php echo $usr->friendlyName; ?></td>
    <td><?php echo $usr->userName; ?></td>
    <td>***</td>
    <td><?php if($usr->isAdmin) { ?><span class="glyphicon glyphicon-ok"></span><?php }?></td>
    <td><a href="#" class="btn btn-default btn-sm userEditBtn" data-toggle="modal" data-target="#modal_userEdit" data-num="<?php echo $usr->userId; ?>" data-friendlyName="<?php echo htmlspecialchars($usr->friendlyName); ?>" data-isAdmin="<?php echo $usr->isAdmin; ?>" data-user="<?php echo htmlspecialchars($usr->userName); ?>"><span class="glyphicon glyphicon-pencil"></span></a>
    <?php if($usr->userId != $userInfo->userId) { ?>
    <a href="#" class="btn btn-default btn-sm" data-toggle="modal" data-target="#modal_userDelConfirm" data-delete-type="<?php echo actionString(deluser); ?>" data-delete-id="<?php echo $usr->userId; ?>"><span class="glyphicon glyphicon-remove"></span></a>
    <?php } ?>
    </td>    
</tr>
<?php
    }
?>
<tr>
    <td colspan="5"><a href="#" class="btn btn-default btn-newUser" data-toggle="modal" data-target="#modal_userEdit"><span class="glyphicon glyphicon-plus"></span> Ajouter</a></td>
</tr>
</table>


<?php
}

function roomAdminTable() {
    global $mode;
    global $displayedURL;
    $users = listUsers();
    $rooms = listRooms();
?>

<h3>Installations</h3>
<p>Le tableau qui suit présente les installations collectives gérées par ce programme. Vous pouvez en ajouter, en changer le nom, en supprimer.</p>
<table class="table table-condensed">
<tr>
<th>Nom de l'installation</th><th>Modifier</th>
</tr>
<?php
    foreach($rooms as $room) {
?>
<tr>
    <td><?php echo $room->friendlyName; ?></td>
    <td>
    <a href="#" class="btn btn-default btn-sm roomEditBtn" data-toggle="modal" data-target="#modal_roomEdit" data-id="<?php echo $room->roomId; ?>" data-friendlyName="<?php echo htmlspecialchars($room->friendlyName); ?>"><span class="glyphicon glyphicon-pencil"></span></a>
    <a href="#" class="btn btn-default btn-sm" data-toggle="modal" data-target="#modal_roomDelConfirm" data-delete-type="<?php echo actionString(delroom); ?>" data-delete-id="<?php echo $room->roomId; ?>"><span class="glyphicon glyphicon-remove"></span></a>
    
    </td>
</tr>
<?php
    }
?>
<tr>
    <td colspan="2"><a href="#" class="btn btn-default btn-newRoom" data-toggle="modal" data-target="#modal_roomEdit"><span class="glyphicon glyphicon-plus"></span> Ajouter</a></td>
</tr>
</table>



<?php
}

function manageAdminTable() {
    global $mode;
    global $displayedURL;
    $users = listUsers();
    $rooms = listRooms();
?>

<h3>Autorisations</h3>
<p>La matrice ci-dessous présente les autorisations de modification. Pour chaque utilsateur, sélectionnez s'il peut modifier les réservations d'une installation particulière. <b>N'oubliez pas d'enregistrer les changements que vous effectuez.</b></p>
<form action="<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>" method="post" class="form" id="manageForm">
<input type="hidden" name="action" value="<?php echo actionString(editmanage); ?>" />
<table class="table table-condensed">
<tr>
<th>Installation</th>
<?php
    foreach($users as $usr) {
?>
<th><?php echo $usr->friendlyName; ?></th>
<?php
    }
?>
</tr>
<?php
    foreach($rooms as $room) {
?>
<tr>
<td><?php echo $room->friendlyName; ?></td>
<?php
        foreach($users as $usr) {
?>
<td><input type="checkbox" name="<?php echo $usr->userId . "_" . $room->roomId; ?>" <?php echo ($usr->canWrite($room->roomId))?"checked":""; ?> />
<input type="hidden" name="<?php echo $usr->userId . "_" . $room->roomId; ?>.hidden" value="0" />
</td>
<?php
        }
?>
</tr>
<?php
    }
?>
</table>
<input type="submit" class="btn btn-primary" value="Enregistrer" />
</form>


<?php
}

function outputAdminTables()
{
    global $displayedURL;
    global $mode;
?>
<div class="col-sm-12">
<h2>Administration</h2>
<p>Les sections ci-dessous présentent les parmètres modifiables par les administrateurs.</p>
<!-- form for deletions -->

<form id="deleteForm" method="post">
<input type="hidden" name="action" id="del_action" />
<input type="hidden" name="del_num" id="del_num" />
</form>


<ul class="nav nav-tabs">
  <li role="presentation" class="nav-item active"><a class="nav-link" href="#rooms" aria-controls="rooms" data-toggle="tab" role="tab" data-get="<?php echo actionString(showrooms); ?>">Installations</a></li>
  <li role="presentation" class="nav-item" ><a href="#users" aria-controls="users" data-toggle="tab" role="tab" data-get="<?php echo actionString(showusers); ?>">Utilisateurs</a></li>
  <li role="presentation" class="nav-item"><a href="#roomManagers" aria-controls="roomManagers" data-toggle="tab" role="tab"  data-get="<?php echo actionString(showmanage); ?>">Autorisations</a></li>
</ul>

<script>
$("a[data-get]").click(function() {
    $("#del_action")[0].value = $(this).attr("data-get");
    var eltid = $(this).attr("href");
    
    $("#del_num")[0].value = "-1";
    $.ajax({type:"POST", 
        data: $("#deleteForm").serialize(), 
        url:"<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>&type=ajax", 
        success: function(data){
            $(eltid).html(data);
        },
        error: function(){
            alert('Une erreur est survenue.');
        }
    });
});

</script>

<div class="tab-content">
    <div class="tab-pane active" id="rooms" role="tabpanel">
        <?php roomAdminTable(); ?>
    </div>
    <div class="tab-pane" id="users" role="tabpanel">
        <?php userAdminTable(); ?>
    </div>
    <div class="tab-pane" id="roomManagers" role="tabpanel">
        <?php manageAdminTable(); ?>
    </div>
</div>

</div>

<div id="modal_roomEdit" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
        <form method="post" id="roomEdForm">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Propriétés de l'installation sportive</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="<?php echo actionString(editroom); ?>" />
                <input type="hidden" name="ed_num" id="ed_num" value="-1" />
                <div class="form-group">
                <label for="ed_friendlyName">Nom de l'installation:</label><input type="text" id="ed_friendlyName" name="ed_friendlyName" class="form-control" placeholder="Nom de l'installation" />
                </div>
                
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Valider</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
            </div>
        </form>
    </div>

  </div>
</div>

<div id="modal_roomDelConfirm" class="modal fade" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="dataConfirmLabel">Confirmer la suppression ?</h3>
    </div>
    <div class="modal-body">Supprimer une installation sportive entraîne la suppression de toutes les réservations qui lui sont associées. Cette opération n'est pas réversible. Confirmez-vous la suppression ?</div>
    <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Non</button><a class="btn btn-danger" data-dismiss="modal" id="roomdelConfirmOK">Oui</a>
    </div>
    </div>
    </div>
</div>

<script>
$(document.body).on("click", ".roomEditBtn", function() {
  $("#roomEdForm")[0].reset();
  $("#ed_friendlyName").attr("placeholder",$(this).attr("data-friendlyName"));
  $("#ed_num").attr("value", $(this).attr("data-id"));
});
$(document.body).on("click", ".btn-newRoom", function() {
  $("#roomEdForm")[0].reset();
  $("#ed_num").attr("value", "-1");
  $("#ed_friendlyName").attr("placeholder","Nom de l'installation");
});

$("#roomEdForm").submit(function(e){
    e.preventDefault();
    $.ajax({type:"POST", 
        data: $(this).serialize(), 
        url:"<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>&type=ajax", 
        success: function(data){
            $("#rooms").html(data);
        },
        error: function(){
            alert('Une erreur est survenue.');
        }
    });
    $("#modal_roomEdit").modal('hide');
});

$("#roomdelConfirmOK").click(function() {
    $.ajax({type:"POST", 
        data: $("#deleteForm").serialize(), 
        url:"<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>&type=ajax", 
        success: function(data){
            $("#rooms").html(data);
        },
        error: function(){
            alert('Une erreur est survenue.');
        }
    });
});
</script>

<div id="modal_userEdit" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
    <form method="post" id="userEdForm">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Propriétés de l'utilisateur</h4>
      </div>
      <div class="modal-body row">
            <input type="hidden" name="action" id="action" value="<?php echo actionString(edituser); ?>" />
            <input type="hidden" name="usered_num" id="usered_num" value="-1" />
            <div class="form-group">
            <div class="col-md-4"><label for="usered_friendlyName" >Nom affiché:</label></div><div class="col-md-8"><input type="text" id="usered_friendlyName" name="usered_friendlyName" class="form-control" placeholder="Nom affiché" /></div>
            </div>
            <div class="form-group">
            <div class="col-md-4"><label for="usered_user">Nom d'utilisateur:</label></div><div class="col-md-8"><input type="text" id="usered_user" name="usered_user" class="form-control" placeholder="Nom d'utilisateur" /></div>
            </div>
            <div class="form-group">
            <div class="col-md-4"><label for="usered_password">Mot de passe:</label></div><div class="col-md-8"><input type="password" id="usered_password" name="usered_password" class="form-control" placeholder="Mot de passe" /></div>
            </div>
            <div class="form-group">
            <div class="col-md-4"><label for="usered_isAdmin">Administrateur:</label></div><div class="col-md-8"><input type="checkbox" id="usered_isAdmin" name="usered_isAdmin"/></div>
            </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Valider</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
      </div>
    </form>
    </div>

  </div>
</div>

<div id="modal_userDelConfirm" class="modal fade" role="dialog" aria-labelledby="dataConfirmLabel" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="dataConfirmLabel">Confirmer la suppression ?</h3>
    </div>
    <div class="modal-body">Confirmez-vous la suppression de l'utilisateur ?</div>
    <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Non</button><a class="btn btn-danger" data-dismiss="modal" id="userdelConfirmOK">Oui</a>
    </div>
    </div>
    </div>
</div>

<script>
$(document.body).on("click", ".userEditBtn", function() {
  $("#userEdForm")[0].reset();
  $("#usered_num").attr("value", $(this).attr("data-num"));
  $("#usered_friendlyName").attr("placeholder",$(this).attr("data-friendlyName"));
  $("#usered_user").attr("placeholder",$(this).attr("data-user"));
  if($(this).attr("data-isAdmin") == "1") 
    $("#usered_isAdmin")[0].checked = true;
  else
    $("#usered_isAdmin")[0].checked = false;
});
$(document.body).on("click", ".btn-newUser", function() {
  $("#userEdForm")[0].reset();
  $("#usered_num").attr("value", "-1");
  $("#usered_friendlyName").attr("placeholder","Nom affiché");
  $("#usered_user").attr("placeholder","Nom d'utilisateur");
  $("#usered_isAdmin")[0].checked = false;
});
$("#userEdForm").submit(function(e){
    e.preventDefault();
    $.ajax({type:"POST", 
        data: $(this).serialize(), 
        url:"<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>&type=ajax", 
        success: function(data){
            $("#users").html(data);
        },
        error: function(){
            alert('Une erreur est survenue.');
        }
    });
    $("#modal_userEdit").modal('hide');

});

$("#userdelConfirmOK").click(function() {
    $.ajax({type:"POST", 
        data: $("#deleteForm").serialize(), 
        url:"<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>&type=ajax", 
        success: function(data){
            $("#users").html(data);
        },
        error: function(){
            alert('Une erreur est survenue.');
        }
    });
});
$(document.body).on('submit', "#manageForm", function(e){
    e.preventDefault();
    $.ajax({type:"POST", 
        data: $(this).serialize(), 
        url:"<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>&type=ajax", 
        success: function(data){
            $("#roomManagers").html(data);
        },
        error: function(){
            alert('Une erreur est survenue.');
        }
    });
    
     //return false;
});

//$("a[data-delete-id]").click(function() {
$(document.body).on('click', 'a[data-delete-id]' ,function(){
    $("#del_action")[0].value = $(this).attr("data-delete-type");
    $("#del_num")[0].value = $(this).attr("data-delete-id");
});

</script>



<script>
</script>

<?php
}

function outputChgPass()
{
    global $displayedURL;
    global $mode;
    global $userInfo;
?>
<div class="col-sm-12">

<h3>Changement de mot de passe</h3>

<form class="form-horizontal" action="<?php echo $displayedURL; ?>?mode=<?php echo modeString($mode); ?>" method="post" id="chgPassForm">
<input type="hidden" name="action" value="<?php echo actionString(selfpasswd); ?>" />

<div class="form-group">
<label for="oldPass" class="col-sm-2">Ancien mot de passe:</label>
<div class="col-sm-5">
<input type="password" name="oldpass" id="oldPass" class="form-control" placeholder="Ancien mot de passe"/>
</div>
</div>

<div class="form-group">
<label for="newPass" class="col-sm-2">Nouveau mot de passe:</label>
<div class="col-sm-5">
<input type="password" name="newpass" id="newPass" class="form-control" placeholder="Nouveau mot de passe"/>
</div>
</div>

<div class="form-group">
<label for="newPassConf" class="col-sm-2">Nouveau mot de passe (confirmez):</label>
<div class="col-sm-5">
<input type="password" name="newpassconf" id="newPassConf" class="form-control" placeholder="Nouveau mot de passe"/>
</div>
</div>

<div class="col-sm-offset-2 col-sm-5">
<input type="submit" class="btn btn-primary" value="Valider" />
<a href="<?php echo $displayedURL; ?>" class="btn btn-default">Annuler</a>
</div>

</form>

<script>

$("#chgPassForm").submit(function(e) {
    if($("#newPassConf")[0].value != $("#newPass")[0].value) {
        e.preventDefault();
        $("#errorMessage").html("Les nouveaux mots de passe saisis ne correspondent pas.")
        $("#errorContainer").removeClass('hidden');
        
    }
});

</script>

</div>
<?php
}

function accessDeniedError()
{
?>
<div class="col-sm-12">
<div class="alert alert-danger">
<p class="bg-danger"><span class="glyphicon glyphicon-warning-sign"></span> Vous n'avez pas accès à cette page.</p>
</div>
</div>
<?php
}

function disconnectSuccessfulMessage()
{
?>
<div class="col-sm-12">
<div class="alert alert-success alert-dismissable">
<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
<p class="bg-success"><span class="glyphicon glyphicon-info-sign"></span> Vous êtes à présent déconnecté.</p>
</div>
</div>
<?php
}

function pageScaffoldBegin()
{
    global $pageTitle;
?>
<!DOCTYPE html>

<html>
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<script src="https://code.jquery.com/jquery-3.2.1.js"></script>

<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/js/bootstrap-editable.min.js"></script>

<script>
$.fn.editable.defaults.mode = 'inline';
</script>

<style>
.printurl {
    display: none;
}

@media print {
.printurl {
    display: block;
}
}

.noprint {
    display: default;
}

@media print {
.noprint {
    display: none;
}
}

</style>

<title><?php echo $pageTitle; ?></title>

<link rel="icon" href="resaplan.ico" />
</head>
<body>
<div class="container">

<?php
}


function pageScaffoldEnd()
{
?>
</div>
<h6 class="text-center"><a href="<?php echo $displayedURL; ?>?mode=<?php echo modeString(cookies); ?>">Mentions légales et information sur les cookies</a> - <b>Résaplan</b> (c) 2011-2017 Corentin Ferry (<a href="https://github.com/cferr/resaplan/">Source sur Github</a>)</h6>
</body>
</html>
<?php
}



?>
