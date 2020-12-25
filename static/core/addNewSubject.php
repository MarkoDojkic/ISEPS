<?php
    session_start();
    require "../../constants.php";
    require_once "database_connection.php";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));

    $errors = array();
    $subject_name = trim($_POST["subject_name"]);
    $assistant = $_POST['assistant_selection']; //ili je prazno ili je id asistenta
    $studies = array();
    $years = array();

    if (!@strpos($subject_name, "/", 1) || strpos($subject_name, "/") === 1) $errors[] = "wrong_sN"; 
    
    if (@$_POST['studies'] === null) $errors[] = "did_not_selected_any_study";

    if(sizeof($errors) !== 0){
        foreach ($errors as $errorName){
            echo "<i style='color:red;font-size:14px;'> - " . $xml->errors->{$errorName}[0] . "</i><br><br>";
        }
    }
    else {

        foreach($_POST['studies'] as $study_id){ //format studyID_takingYear (non taking selection is null)
            $studies[] = explode("_",$study_id)[0];
            $year[] = explode("_",$study_id)[1];
        }

        $checkingTitle_temp = explode("/",$subject_name)[0];

        if($assistant === ""){
            $query = sprintf("INSERT INTO subject (title,titleEnglish,professor_id) VALUES ('%s','%s','%s');",
                            mysqli_real_escape_string($conn,$checkingTitle_temp), 
                            mysqli_real_escape_string($conn,explode("/",$subject_name)[1]),
                            mysqli_real_escape_string($conn,$_SESSION['loggedInId']));
        }
        else {
            $query = sprintf("INSERT INTO subject (title,titleEnglish,professor_id,assistant_id) VALUES ('%s','%s','%s','%s');",
                            mysqli_real_escape_string($conn,$checkingTitle_temp), 
                            mysqli_real_escape_string($conn,explode("/",$subject_name)[1]),
                            mysqli_real_escape_string($conn,$_SESSION['loggedInId']),
                            mysqli_real_escape_string($conn,$assistant));
        }

        $conn->query($query) or die("<i style='color:red;font-size:14px;'>" . $xml->errors->addSubjectError[0] . " (1)</i><br><br>"); //subject already exists

        $subject_id = $conn->query("SELECT subject_id FROM subject WHERE title = '{$checkingTitle_temp}';")->fetch_assoc()['subject_id'] 
                            or die("<i style='color:red;font-size:14px;'>" . $xml->errors->addSubjectError[0] . " (2)</i><br><br>");

        for($i = 0; $i < sizeof($studies); $i++){
            $query = sprintf("INSERT INTO subject_study (study_id,subject_id,year) VALUES ('%s','%s','%s');",
                            mysqli_real_escape_string($conn,$studies[$i]),
                            mysqli_real_escape_string($conn,$subject_id),
                            mysqli_real_escape_string($conn,$year[$i]));
            $conn->query($query) or die("<i style='color:red;font-size:14px;'>" . $xml->errors->addSubjectError[0] . " (3)</i><br><br>");
        }
        
        echo "<i style='color:green;font-size:14px;'> + {$xml->professorPage->addSubjectSuccessfull[0]}</i><br><br>";

        echo "<script>setTimeout(function(){
            window.top.location.reload();
         }, 5000);</script>";
    }
?>