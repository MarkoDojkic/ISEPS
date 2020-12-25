<?php

    session_start();
    require "../../constants.php";
    require_once "database_connection.php";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));

    foreach(array_keys($_POST) as $key){
       
        switch(explode('_',$key)[0]){
            case 'details': showDetails(explode('_',$key)[1],$xml,$conn); break 2;
            case 'edit': editSubject(explode('_',$key)[1],$xml,$conn); break 2;
            case 'viewStudents': viewAttendingStudents(explode('_',$key)[1],$xml,$conn); break 2;
            case 'exercises': viewExercises(explode('_',$key)[1],$xml,$conn); break 2;
            case 'startNE': startNewExercise(explode('_',$key)[1],$xml,$conn); break 2;
            case 'cancel': exit;
            default: continue 2;
        }
    }

    function showDetails($id,$xml,$conn){
        $query = sprintf("SELECT title, titleEnglish, professor_id FROM `subject`
        WHERE subject_id = '%s';", mysqli_real_escape_string($conn,$id));

        $data = $conn->query($query)->fetch_assoc();
        $action = DIR_ROOT_ONLY . DIR_CORE . "/teaching_exercises.php";

        $professor = $conn->query("SELECT name_surname FROM staff WHERE staff_id = '{$data["professor_id"]}';")->fetch_assoc()["name_surname"];

        echo "
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css'>
        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
        <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js'></script>
        <script>
            var rows = window.top.document.querySelectorAll('tbody tr');
            var i;
            for (i = 0; i < rows.length; i++) {
                if(rows[i].id != 'tr_{$id}') rows[i].style.backgroundColor = '#00000000';
                else rows[i].style.backgroundColor = 'lightblue';
            }
        </script>
        <form action='{$action}' method='post' style='text-align: center;margin:0px auto; background-color: #cdeaff; height: 100%;'>
            <div class='form-group'>
                <label for='subject_name'>{$xml->professorPage->subject_name[0]}:</label><br><br>
                <input type='text' class='form-control' name='subject_name' id='subject_name' autocomplete='subject_name' placeholder='{$data["title"]}' readonly>/
                <input type='text' class='form-control' name='subject_nameEng' id='subject_nameEng' autocomplete='subject_nameEng' placeholder='{$data["titleEnglish"]}' readonly>
            </div>
            <div class='form-group'>
                <label for='professor'>{$xml->registrationPage->professor[0]}:</label>
                <input type='text' class='form-control' name='professor_name' id='professor_name' placeholder='{$professor}' readonly>
            </div>
            <br>		
            <div class='form-group'>
                <button type='submit' class='btn btn-info' id='viewStudents_$id' name='viewStudents_$id'>{$xml->assistantPage->viewStudentsBtn[0]}</button>
                <button type='submit' class='btn btn-danger' id='cancel' name='cancel'>{$xml->professorPage->cancelBtn[0]}</button>
            </div>
        </form>";
    }  

    function viewAttendingStudents($id,$xml,$conn){
        $alert = "{$xml->assistantPage->viewStudentsText[0]}";
        $localization = $_SESSION["language"] === "english" ? "titleEnglish" : "title";

        $query = sprintf("SELECT DISTINCT student.name_surname, student.indexNo, faculty.{$localization} as faculty, study.{$localization} as study, student.enrolledYear as enrolledYear FROM subject_study
        INNER JOIN study ON subject_study.study_id = study.study_id
        INNER JOIN student ON subject_study.study_id = student.study_id
        INNER JOIN faculty ON study.faculty_id = faculty.faculty_id
        INNER JOIN `subject` ON subject_study.subject_id = `subject`.subject_id
        WHERE subject_study.subject_id = '%s' AND  subject.is_inactive = '0'",mysqli_real_escape_string($conn,$id));


        $data = $conn->query($query) or die($xml->errors->viewAttendingStudentsError[0]);

        while($attendingStudent = $data->fetch_assoc()){
            $alert .= "\\n - {$attendingStudent["name_surname"]} ({$attendingStudent["indexNo"]}) - {$attendingStudent["faculty"]} ({$attendingStudent["study"]})";
        }

        if(!strpos($alert,'-')) $alert = $xml->assistantPage->schoolYearOver[0];

        echo "<script>alert('{$alert}');</script>";
        showDetails($id,$xml,$conn);
    }

    function viewExercises($id,$xml,$conn){

        $query = sprintf("SELECT is_inactive FROM subject WHERE subject_id = '%s'",mysqli_real_escape_string($conn,$id));

        $data = $conn->query($query)->fetch_assoc() or die("<i style='color:red;font-size:14px;'> - " . $xml->errors->viewLEError[0]. "</i>");;

        if($data['is_inactive']) 
            die ("
            <script>
            alert('{$xml->assistantPage->schoolYearOver[0]}');
            
            setTimeout(function(){
                window.top.location.reload();
            }, 1);</script>");

        $exercises = "<option value=''>-</option>";

        $query = sprintf("SELECT exercise_id FROM exercise WHERE log_file_name LIKE '%s\_%%'"
                    ,mysqli_real_escape_string($conn,$id));

        $data = $conn->query($query) or die("<i style='color:red;font-size:14px;'> - " . $xml->errors->viewLEError[0]. "</i>");

        while($exercise = $data->fetch_assoc()){
            $exercises .= "<option value='{$exercise['exercise_id']}'>ID: {$exercise['exercise_id']}</option>";
        }

        $dateLocal = $_SESSION["language"] === "english" ? date("Y-m-d") : date("d.m.Y");

        $label1 = $dateLocal . ' ' . $xml->assistantPage->startTime[0];
        $label2 = $dateLocal . ' ' . $xml->assistantPage->endTime[0];

        $action = DIR_ROOT_ONLY . DIR_CORE . "/teaching_exercises.php";
        $ajax_url = "getLEInfo.php";

        echo "
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css'>
        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
        <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js'></script>
        <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
        <script>
            var rows = window.top.document.querySelectorAll('tbody tr');
            var i;
            for (i = 0; i < rows.length; i++) {
                if(rows[i].id != 'tr_{$id}') rows[i].style.backgroundColor = '#00000000';
                else rows[i].style.backgroundColor = 'lightblue';
            }

            function updateExerciseInfo(selections){
                if(selections[selections.selectedIndex].value === '') {
                    document.querySelector('#exerciseInfo').innerHTML = '';
                    return;
                }
                $.ajax({
                    type: 'POST',
                    url: '{$ajax_url}',
                    data: {'exercise_id':selections[selections.selectedIndex].value}, 
                    success: function(result){
                        document.querySelector('#exerciseInfo').innerHTML = result;
                    }
                });
            }
        </script>
        
        <form target='phpIframe' action='{$action}' method='post' style='text-align: center;margin:0px auto; background-color: #cdeaff; height: 100%;'>
            <br>
            <div class='form-group'>
                <label for='exerciseSelection'>{$xml->assistantPage->exerciseSelection[0]}:</label>
                <select class='form-control' name='exerciseSelection' id='exerciseSelection' onchange='updateExerciseInfo(this)'>
                    {$exercises}
                </select>
            </div>
            <div id='exerciseInfo'></div>
            <div class='form-group'>
                <label for='start_time'>{$label1}</label>
                <input type='time' id='start_time' name='start_time'>
            </div>
            <div class='form-group'>
                <label for='end_time'>{$label2}</label>
                <input type='time' id='end_time' name='end_time'>
            </div>			
            <div class='form-group'>
                <button type='submit' class='btn btn-success' id='startNE_$id' name='startNE_$id'>{$xml->assistantPage->exerciseStartBtn[0]}</button>
                <button type='submit' class='btn btn-danger' id='cancel' name='cancel'>{$xml->professorPage->cancelBtn[0]}</button>
            </div>
        </form>
        ";
    }


    function startNewExercise($id,$xml,$conn){

        $exerciseLength = date_diff(new DateTime($_POST['start_time']),new DateTime($_POST['end_time']));
        
        $query = sprintf("SELECT log_file_name, end_time FROM exercise WHERE log_file_name LIKE '%s_%%' 
                                        ORDER BY end_time DESC LIMIT 1", mysqli_real_escape_string($conn,$id));

        $lastExerciseData = $conn->query($query)->fetch_assoc() or null;
        $lengthFromLastExercise = date_diff(new DateTime($_POST['start_time']),new DateTime($_POST['start_time']));
        //defaultly set so it'll have invert parametar 0 to pass if test below

        $var = explode("^",file_get_contents(DIR_ROOT . DIR_MISCELLANEOUS . "/" . "exerciseLogs/" 
        . $lastExerciseData["log_file_name"])); //example of test data [1.2020-12-01,11:30$12:45]

        $dateTime = explode(".",$var[0])[1] . explode("$",$var[1])[1];

        if($lastExerciseData != null)
            $lengthFromLastExercise = date_diff(new DateTime($dateTime),new DateTime($_POST['start_time']));

        if($exerciseLength->i < 45 && $exerciseLength->h == 0 // incorrect: 12:00->12:44;12:00->11:59;12:00->18:01
            || $exerciseLength->invert === 1 || $exerciseLength->h > 6 || $lengthFromLastExercise->invert === 1) {
                echo "<script>alert('{$xml->errors->LELengthInvalid[0]}');</script>";
                viewExercises($id,$xml,$conn);
                exit;
            }

        do {
            $log_file_name = $id . "_" . random_int($id, 100*$id) . '.log'; //generate unique log filename (up to 100 exercises per subject)
        } while(file_exists(DIR_ROOT . DIR_MISCELLANEOUS . "/exerciseLogs/" . $log_file_name));

        $query = sprintf("INSERT INTO exercise (log_file_name,start_time,end_time) VALUES ('%s','%s','%s')",
                        mysqli_real_escape_string($conn,$log_file_name),
                        mysqli_real_escape_string($conn,$_POST['start_time']),
                        mysqli_real_escape_string($conn,$_POST['end_time']));

        $conn->query($query) or die("<script>alert('{$xml->errors->startLEError[0]}');</script>");

        $query = sprintf("SELECT count(exercise_id) as count FROM exercise WHERE log_file_name LIKE '%s_%%'"
                    ,mysqli_real_escape_string($conn,$id));

        $count = $conn->query($query)->fetch_assoc()["count"];

        $data = $count . "." . date("Y-m-d") . '^' . $_POST['start_time'] . '$' . $_POST['end_time'] . "\n";

        file_put_contents(DIR_ROOT . DIR_MISCELLANEOUS . "/exerciseLogs/" . $log_file_name, $data, LOCK_EX);

        echo("<script>alert('{$xml->assistantPage->startNewExerciseSuccessfull[0]}');</script>");

        viewExercises($id,$xml,$conn);
    }
?>