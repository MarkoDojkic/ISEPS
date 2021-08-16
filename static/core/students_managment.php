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

        $nameSurname_pattern = "/^([\x{0410}-\x{0418}\x{0402}\x{0408}\x{041A}-\x{041F}\x{0409}\x{040A}\x{0420}-\x{0428}\x{040B}\x{040F}A-Z\x{0110}\x{017D}\x{0106}\x{010C}\x{0160}]{1}[\x{0430}-\x{0438}\x{0452}\x{043A}-\x{043F}\x{045A}\x{0459}\x{0440}-\x{0448}\x{0458}\x{045B}\x{045F}a-z\x{0111}\x{017E}\x{0107}\x{010D}\x{0161}]+(\s|\-)?)+$/u";
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