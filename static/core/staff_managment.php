<?php
    session_start();
    require "../../constants.php";
    require "database_connection.php";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));
    
    foreach(array_keys($_POST) as $key){
       
        switch(explode('_',$key)[0]){
            case "switchRole": switchRole(explode('_',$key)[1],$xml,$conn); break;
            case 'edit': editStaffMember(explode('_',$key)[1],$xml,$conn); break;
            case 'delete': deleteStaffMember(explode('_',$key)[1],$xml,$conn); break;
            default: continue 2;
        }
    }

    function switchRole($id,$xml,$conn){
        $newRole = $_POST["oldRole_$id"] === "professor" ? "assistant" : "professor";

        $query = sprintf("SELECT * FROM subject WHERE professor_id = '%s' OR assistant_id = '%s';",mysqli_real_escape_string($conn,$id),mysqli_real_escape_string($conn,$id));

        if(sizeof($conn->query($query)->fetch_assoc()) > 0){
            $staffMember = $newRole === "professor" ? $xml->registrationPage->assistant[0] : $xml->registrationPage->professor[0];
            showErrorAlert($xml->errors->switchRoleError1[0] . " " . strtolower($staffMember) . " " . $xml->errors->switchRoleError2[0]);
        }
        else {
            $query = sprintf("UPDATE staff SET role = '%s' WHERE staff_id = '%s';",mysqli_real_escape_string($conn,$newRole),mysqli_real_escape_string($conn,$id));

            $conn->query($query) or showErrorAlert($xml->errors->switchRoleError0[0]);
        }

        reloadPage();
    }

    function editStaffMember($id,$xml,$conn){

        $nameSurname_pattern = "/^[A-ZБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФСЦЧЏШŠĐČĆ]{1}[a-zабвгдђежзијклљмнњопрстћуфсцчџшšđčć]{1,14}\s([A-ZАБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФСЦЧЏШŠĐČĆ]{1}[a-zабвгдђежзијклљмнњопрстћуфсцчџшšđčć]{1,14}\s?){1,3}$/";
        $password_pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/";

        if($_POST["newNS_$id"] !== null && preg_match_all($nameSurname_pattern, $_POST["newNS_$id"])){
            $query = sprintf("UPDATE staff SET name_surname = '%s' WHERE staff_id = '%s';",mysqli_real_escape_string($conn,$_POST["newNS_$id"]),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or showErrorAlert($xml->errors->wrong_nS[0]);
        }

        if($_POST["newUE_$id"] !== null){
            $query = sprintf("UPDATE staff SET email = '%s' WHERE staff_id = '%s';",mysqli_real_escape_string($conn,$_POST["newUE_$id"]),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or showErrorAlert($xml->errors->wrong_email[0]);
        }

        if($_POST["newPASS_$id"] !== null && preg_match_all($password_pattern, $_POST["newPASS_$id"])){
            $query = sprintf("UPDATE staff SET password_hash = '%s' WHERE staff_id = '%s';",mysqli_real_escape_string($conn,password_hash($_POST["newPASS_$id"], PASSWORD_DEFAULT)),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or showErrorAlert($xml->errors->wrong_pass[0]);
            $date = date('d.m.Y H:i:s', time()); //H - 24, h - 12
            $newPass = $_POST["newPASS_$id"];
            $editData = "
                ------PASSWORD_CHANGED_STAFF------
                        ID: {$id}
              NEW PASSWORD: $newPass
                 CHANGE AT: {$date}
                ------PASSWORD_CHANGED_STAFF------                  
            ";

            file_put_contents(DIR_ROOT . DIR_MISCELLANEOUS . "/editedPasswords.rtf", $editData, FILE_APPEND | LOCK_EX);
        }

        reloadPage();   
    }

    function deleteStaffMember($id,$xml,$conn){

        $query = sprintf("DELETE FROM staff WHERE staff_id = '%s';",mysqli_real_escape_string($conn,$id));

        $conn->query($query) or showErrorAlert($xml->errors->deleteError[0]);

        reloadPage();
    }

    function reloadPage(){
        echo "<script>setTimeout(function(){
            window.top.location.reload();
        }, 1);</script>";
    }

    function showErrorAlert($message){
        echo "<script>alert('$message');</script>";
        reloadPage();
    }
?>