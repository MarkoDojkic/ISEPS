<?php
    session_start();
    require "../../constants.php";
    require "database_connection.php";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));

    $errors = array();
    $id = trim($_POST["id"]);
    $password = trim($_POST["password"]);

    $id_pattern = "/^[0-9]{1,20}$/";
    $password_pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/";
    //one uppercase, lowercase and digit (length of min 8)
    if (!preg_match($id_pattern, $id)) $errors[] = "wrong_id";
    
    if (!preg_match($password_pattern, $password)) $errors[] = "wrong_pass";
    
    if ($_POST["captcha"] !== $_SESSION['captcha_text']) $errors[] = "invalid_captcha";

    if(sizeof($errors) !== 0){
        foreach ($errors as $errorName){
            echo "<i style='color:red;font-size:14px;'> - " . $xml->errors->{$errorName}[0] . "</i><br><br>";
        }
    }
    else {        

        // * alternative hashing method using exec('java -jar encryptor.jar ' . $password, $hashed_pass); not working

        $query = sprintf("SELECT name_surname, password_hash, role FROM staff WHERE staff_id = %s;",
                            mysqli_real_escape_string($conn,$id));

        $data = ($conn->query($query))->fetch_assoc();
        $nameSurname = $data["name_surname"];
        $pass_hash = $data['password_hash'];
        $loginAs = $data['role'];

        if($password === null) $errors[] = "userNotFound";
        else if(!password_verify($password, $pass_hash)) $errors[] = "wrong_pass";

        if(sizeof($errors) !== 0){
            foreach ($errors as $errorName){
                echo "<i style='color:red;font-size:14px;'> - " . $xml->errors->{$errorName}[0] . "</i><br><br>";
            }
        }

        else {
            $protocol = !empty($_SERVER['HTTPS']) ? 'https':'http';
            $_SESSION['loggedInUser'] = $nameSurname;
            $_SESSION['loggedInAs'] = $loginAs;
            $_SESSION['loggedInId'] = $id;
            $page_redirect = $loginAs === "assistant" ? "teaching_exercises" : "teaching_subject_management";
            echo "<script>window.top.location.href = '/index.php?language={$_SESSION['language']}&page={$page_redirect}';</script>";
        }
    }
?>