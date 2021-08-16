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

        $nameSurname_pattern = "/^([\x{0410}-\x{0418}\x{0402}\x{0408}\x{041A}-\x{041F}\x{0409}\x{040A}\x{0420}-\x{0428}\x{040B}\x{040F}A-Z\x{0110}\x{017D}\x{0106}\x{010C}\x{0160}]{1}[\x{0430}-\x{0438}\x{0452}\x{043A}-\x{043F}\x{045A}\x{0459}\x{0440}-\x{0448}\x{0458}\x{045B}\x{045F}a-z\x{0111}\x{017E}\x{0107}\x{010D}\x{0161}]+(\s|\-)?)+$/u";
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