<?php
    session_start();
    require "../../constants.php";
    require "database_connection.php";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));
    

    foreach(array_keys($_POST) as $key){
       
        switch(explode('_',$key)[0]){
            case 'edit': editStudent(explode('_',$key)[1],$xml,$conn); break;
            case 'delete': deleteStudent(explode('_',$key)[1],$xml,$conn); break;
            default: continue 2;
        }
    }

    function editStudent($id,$xml,$conn){

        $nameSurname_pattern = "/^[A-ZБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФСЦЧЏШŠĐČĆ]{1}[a-zабвгдђежзијклљмнњопрстћуфсцчџшšđčć]{1,14}\s([A-ZАБВГДЂЕЖЗИЈКЛЉМНЊОПРСТЋУФСЦЧЏШŠĐČĆ]{1}[a-zабвгдђежзијклљмнњопрстћуфсцчџшšđčć]{1,14}\s?){1,3}$/";
        $password_pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/";

        if($_POST["newNS_$id"] !== null && preg_match_all($nameSurname_pattern, $_POST["newNS_$id"])){
            $query = sprintf("UPDATE student SET name_surname = '%s' WHERE student_id = '%s';",mysqli_real_escape_string($conn,$_POST["newNS_$id"]),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or showErrorAlert($xml->errors->wrong_nS[0]);
        }

        if($_POST["newIX_$id"] !== null && preg_match_all("[12]{1}[0-9]{3}\\/[0-9]{6}",$_POST["newIX_$id"])){
            $query = sprintf("UPDATE student SET indexNo = '%s' WHERE student_id = '%s';",mysqli_real_escape_string($conn,$_POST["newIX_$id"]),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or showErrorAlert($xml->errors->wrong_indexNo[0]);
        }

        if($_POST["newUE_$id"] !== null){
            $query = sprintf("UPDATE student SET studentEmail = '%s' WHERE student_id = '%s';",mysqli_real_escape_string($conn,$_POST["newUE_$id"]),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or showErrorAlert($xml->errors->wrong_email[0]);
        }

        if($_POST["newPASS_$id"] !== null && preg_match_all($password_pattern, $_POST["newPASS_$id"])){
            $query = sprintf("UPDATE student SET password_hash = '%s' WHERE student_id = '%s';",mysqli_real_escape_string($conn,password_hash($_POST["newPASS_$id"], PASSWORD_DEFAULT)),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or die($xml->errors->wrong_nS[0]);
            $date = date('d.m.Y H:i:s', time()); //H - 24, h - 12
            $newPass = $_POST["newPASS_$id"];
            $editData = "
                ------PASSWORD_CHANGED_STUDENT------
                        ID: {$id}
              NEW PASSWORD: $newPass
                 CHANGE AT: {$date}
                ------PASSWORD_CHANGED_STUDENT------                  
            ";

            file_put_contents(DIR_ROOT . DIR_MISCELLANEOUS . "/editedPasswords.rtf", $editData, FILE_APPEND | LOCK_EX);
        }

        reloadPage();
    }

    function deleteStudent($id,$xml,$conn){

        $query = sprintf("DELETE FROM student WHERE student_id = '%s';",mysqli_real_escape_string($conn,$id));
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
    }
?>