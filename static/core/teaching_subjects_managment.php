<?php

    session_start();
    require "../../constants.php";
    require_once "database_connection.php";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));

    foreach(array_keys($_POST) as $key){
       
        switch(explode('_',$key)[0]){
            case 'details': showDetails(explode('_',$key)[1],$xml,$conn); break 2;
            case 'startSY': startNewSubjectYear(explode('_',$key)[1],$xml,$conn); break 2;
            case 'endSY': endCurrentSubjectYear(explode('_',$key)[1],$xml,$conn); break 2;
            case 'edit': editSubject(explode('_',$key)[1],$xml,$conn); break 2;
            case 'viewStudents': viewAttendingStudents(explode('_',$key)[1],$xml,$conn); break 2;
            case 'lectures': viewLectures(explode('_',$key)[1],$xml,$conn); break 2;
            case 'startNL': startNewLecture(explode('_',$key)[1],$xml,$conn); break 2;
            case 'cancel': exit;
            default: continue 2;
        }
    }

    function showDetails($id,$xml,$conn){
        $query = sprintf("SELECT title, titleEnglish, assistant_id FROM `subject`
        WHERE subject_id = '%s';", mysqli_real_escape_string($conn,$id));

        $data = $conn->query($query)->fetch_assoc();
        $assistants = "";
        $assistants_data = $conn->query("SELECT name_surname, staff_id FROM staff WHERE role = 'assistant';");
        
        if($data["assistant_id"] === null) $assistants .= "<option value='' selected>-</option>";
        else $assistants .= "<option value=''>-</option>";

        while($assistant = $assistants_data->fetch_assoc()){
            if($assistant["staff_id"] === $data["assistant_id"])
                $assistants .= "
                    <option value='{$assistant["staff_id"]}' selected>{$assistant["name_surname"]}</option>
                ";
            else 
                $assistants .= "
                    <option value='{$assistant["staff_id"]}'>{$assistant["name_surname"]}</option>
                ";
        }
        
        $action = DIR_ROOT_ONLY . DIR_CORE . "/teaching_subjects_managment.php";

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
            <br>
            <div class='form-group'>
                <label for='subject_name'>{$xml->professorPage->subject_name[0]}:</label><br><br>
                <input type='text' class='form-control' name='subject_name' id='subject_name' autocomplete='subject_name' placeholder='{$data["title"]}'>/
                <input type='text' class='form-control' name='subject_nameEng' id='subject_nameEng' autocomplete='subject_nameEng' placeholder='{$data["titleEnglish"]}'>
            </div>
            <div class='form-group'>
                <label for='assistant_selection'>{$xml->registrationPage->assistant[0]}:</label>
                <select class='form-control' name='assistant_selection' id='assistant_selection'>
                    {$assistants}
                </select>
            </div>
            <br>		
            <div class='form-group'>
                <button type='submit' class='btn btn-info' id='viewStudents_$id' name='viewStudents_$id'>{$xml->assistantPage->viewStudentsBtn[0]}</button>
                <button type='submit' class='btn btn-warning' id='edit_$id' name='edit_$id'>{$xml->adminPage->editBtn[0]}</button>
                <button type='submit' class='btn btn-danger' id='cancel' name='cancel'>{$xml->professorPage->cancelBtn[0]}</button>
            </div>
        </form>";
    }

    function startNewSubjectYear($id,$xml,$conn){
        try {


            $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

            $query = sprintf("UPDATE subject SET is_inactive = 0 WHERE subject_id = '%s';"
                            ,mysqli_real_escape_string($conn,$id));
            
            $conn->query($query);

            $conn->commit();
        }
        catch(Exception $e){
            $conn->rollback();
            die($xml->errors->startCSYError[0]);
        }

        echo "<script>setTimeout(function(){
            window.top.location.reload();
        }, 1);</script>";
    }

    function endCurrentSubjectYear($id,$xml,$conn){

        try {
            $conn->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

            $query = sprintf("UPDATE subject SET is_inactive = 1 WHERE subject_id = '%s';"
                        ,mysqli_real_escape_string($conn,$id));

            $conn->query($query);

            $conn->commit();
        }
        catch(Exception $e){
            $conn->rollback();
            die($xml->errors->endCSYError[0]);
        }

        echo "<script>setTimeout(function(){
            window.top.location.reload();
        }, 1);</script>";
    }
    

    function editSubject($id,$xml,$conn){
        if($_POST['assistant_selection'] === ""){
            $query = sprintf("UPDATE subject SET assistant_id = null WHERE subject_id = '%s'",mysqli_real_escape_string($conn,$id));
            $conn->query($query) or die($xml->errors->editSubjectError[0] . "(50001)");
        }
        else {
            $query = sprintf("UPDATE subject SET assistant_id = '%s' WHERE subject_id = '%s'",mysqli_real_escape_string($conn,$_POST['assistant_selection']),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or die($xml->errors->editSubjectError[0] . "(50002)");
        }

        if($_POST["subject_name"] !== ""){ //(preg_match_all("/^[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}A-z]{1,15}\s([\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}A-z\-]{1,15}\s?){1,3}$/",$_POST["subject_name"]))
            $query = sprintf("UPDATE subject SET title = '%s' WHERE subject_id = '%s'",mysqli_real_escape_string($conn,$_POST["subject_name"]),mysqli_real_escape_string($conn,$id));
            $conn->query($query) or die("<i style='color:red;font-size:14px;'> - " . $xml->errors->edit_wrong_sN0[0]. "</i>");
        }
        // else
            // echo "<i style='color:red;font-size:14px;'> - " . $xml->errors->edit_wrong_sN0[0] . "</i><br>";
        
        
        if($_POST["subject_nameEng"] !== "" && preg_match_all("/^[A-Z]{1}([a-z]\\s?)+$/",$_POST["subject_nameEng"])){
            $query = sprintf("UPDATE subject SET titleEnglish = '%s' WHERE subject_id = '%s'",mysqli_real_escape_string($conn,$_POST["subject_nameEng"]),mysqli_real_escape_string($conn,$id));
            
            $conn->query($query) or die("<i style='color:red;font-size:14px;'> - " . $xml->errors->edit_wrong_sN1[0]. "</i>");
        }
        // else 
            // echo "<i style='color:red;font-size:14px;'> - " . $xml->errors->edit_wrong_sN1[0] . "</i>\";
        
        echo "<script>setTimeout(function(){
            window.top.location.reload();
        }, 1);</script>";
    }

    function viewAttendingStudents($id,$xml,$conn){
        $alert = "{$xml->assistantPage->viewStudentsText[0]}";
        $localization = $_SESSION["language"] === "english" ? "titleEnglish" : "title";
        
        $query = sprintf("SELECT DISTINCT student.name_surname, student.indexNo, faculty.{$localization} as faculty, study.{$localization} as study, student.enrolledYear as enrolledYear FROM subject_study
        INNER JOIN study ON subject_study.study_id = study.study_id
        INNER JOIN student ON subject_study.study_id = student.study_id
        INNER JOIN faculty ON study.faculty_id = faculty.faculty_id
        INNER JOIN `subject` ON subject_study.subject_id = subject.subject_id
        WHERE subject_study.subject_id = '%s' AND subject.is_inactive = '0'",mysqli_real_escape_string($conn,$id));

        $data = $conn->query($query) or die($xml->errors->viewAttendingStudentsError[0]);

        while($attendingStudent = $data->fetch_assoc()){
            $alert .= "\\n - {$attendingStudent["name_surname"]} ({$attendingStudent["indexNo"]}) - {$attendingStudent["faculty"]} ({$attendingStudent["study"]})";
        }

        if(!strpos($alert,'-')) $alert = $xml->assistantPage->schoolYearOver[0];
        //returns 1 (true) if - does not exist in the string (i.e. in the case if there are no students or if the subject is inactive - subject.is_inactive = '1'
        
        echo "<script>alert('{$alert}');</script>";
        showDetails($id,$xml,$conn);
    }
    
    function viewLectures($id,$xml,$conn){

        $query = sprintf("SELECT is_inactive FROM subject WHERE subject_id = '%s'",mysqli_real_escape_string($conn,$id));

        $data = $conn->query($query)->fetch_assoc() or die("<i style='color:red;font-size:14px;'> - " . $xml->errors->viewLEError[0]. "</i>");;

        if($data['is_inactive']) 
            die ("
            <script>
            alert('{$xml->assistantPage->schoolYearOver[0]}');
            
            setTimeout(function(){
                window.top.location.reload();
            }, 1);</script>");

        $lectures = "<option value=''>-</option>";

        $query = sprintf("SELECT lecture_id FROM lecture WHERE log_file_name LIKE '%s\_%%' ORDER BY lecture_id"
                    ,mysqli_real_escape_string($conn,$id));

        $data = $conn->query($query) or die("<i style='color:red;font-size:14px;'> - " . $xml->errors->viewLEError[0]. "</i>");

        while($lecture = $data->fetch_assoc()){
            $lectures .= "<option value='{$lecture['lecture_id']}'>ID: {$lecture['lecture_id']}</option>";
        }

        $dateLocal = $_SESSION["language"] === "english" ? date('Y-m-d') : date("d.m.Y");

        $label1 = $dateLocal . ' ' . $xml->professorPage->startTime[0];
        $label2 = $dateLocal . ' ' . $xml->professorPage->endTime[0];

        $action = DIR_ROOT_ONLY . DIR_CORE . "/teaching_subjects_managment.php";
        
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

            function updateLectureInfo(selections){
                if(selections[selections.selectedIndex].value === '') {
                    document.querySelector('#lectureInfo').innerHTML = '';
                    return;
                }
                $.ajax({
                    type: 'POST',
                    url: '{$ajax_url}',
                    data: {'lecture_id':selections[selections.selectedIndex].value}, 
                    success: function(result){
                        document.querySelector('#lectureInfo').innerHTML = result;
                    }
                });
            }
        </script>
        
        <form target='phpIframe' action='{$action}' method='post' style='text-align: center;margin:0px auto; background-color: #cdeaff; height: 100%;'>
            <br>
            <div class='form-group'>
                <label for='lectureSelection'>{$xml->professorPage->lectureSelection[0]}:</label>
                <select class='form-control' name='lectureSelection' id='lectureSelection' onchange='updateLectureInfo(this)'>
                    {$lectures}
                </select>
            </div>
            <div id='lectureInfo'></div>
            <div class='form-group'>
                <label for='start_time'>{$label1}</label>
                <input type='time' id='start_time' name='start_time'>
            </div>
            <div class='form-group'>
                <label for='end_time'>{$label2}</label>
                <input type='time' id='end_time' name='end_time'>
            </div>			
            <div class='form-group'>
                <button type='submit' class='btn btn-success' id='startNL_$id' name='startNL_$id'>{$xml->professorPage->lectureStartBtn[0]}</button>
                <button type='submit' class='btn btn-danger' id='cancel' name='cancel'>{$xml->professorPage->cancelBtn[0]}</button>
            </div>
        </form>
        ";
    }

    function startNewLecture($id,$xml,$conn){

        $lectureLength = date_diff(new DateTime($_POST['start_time']),new DateTime($_POST['end_time']));

        $query = sprintf("SELECT log_file_name, end_time FROM lecture WHERE log_file_name LIKE '%s_%%' 
                                        ORDER BY end_time DESC LIMIT 1", mysqli_real_escape_string($conn,$id));

        $lastLectureData = $conn->query($query)->fetch_assoc() or null;
        $lengthFromLastLecture = date_diff(new DateTime($_POST['start_time']),new DateTime($_POST['start_time']));
        //defaultly set so it'll have invert parametar 0 to pass if test below

        $var = explode("^",file_get_contents(DIR_ROOT . DIR_MISCELLANEOUS . "/" . "lectureLogs/" 
        . $lastLectureData["log_file_name"])); //example of var data [1.2020-12-01,11:30$12:45]

        $dateTime = explode(".",$var[0])[1] . explode("$",$var[1])[1];

        if($lastLectureData != null)
            $lengthFromLastLecture = date_diff(new DateTime($dateTime),new DateTime($_POST['start_time'])); 

        if($lectureLength->i < 45 && $lectureLength->h == 0 // incorrect: 12:00->12:44;12:00->11:59;12:00->18:01
            || $lectureLength->invert === 1 || $lectureLength->h > 6 || $lengthFromLastLecture->invert === 1) {
                echo "<script>alert('{$xml->errors->LELengthInvalid[0]}');</script>";
                viewLectures($id,$xml,$conn);
                exit;
            }

        do {
            $log_file_name = $id . "_" . random_int($id, 100*$id) . '.log'; //generate unique log filename (up to 100 lectures per subject)
        } while(file_exists(DIR_ROOT . DIR_MISCELLANEOUS . "/lectureLogs/" . $log_file_name));

        $query = sprintf("INSERT INTO lecture (log_file_name,start_time,end_time) VALUES ('%s','%s','%s')",
                        mysqli_real_escape_string($conn,$log_file_name),
                        mysqli_real_escape_string($conn,$_POST['start_time']),
                        mysqli_real_escape_string($conn,$_POST['end_time']));

        $conn->query($query) or die("<script>alert('{$xml->errors->startLEError[0]}');</script>");

        $query = sprintf("SELECT count(lecture_id) as count FROM lecture WHERE log_file_name LIKE '%s_%%'"
                    ,mysqli_real_escape_string($conn,$id));

        $count = $conn->query($query)->fetch_assoc()["count"];

        $data = $count . "." . date("Y-m-d") . '^' . $_POST['start_time'] . '$' . $_POST['end_time'] . "\n";

        file_put_contents(DIR_ROOT . DIR_MISCELLANEOUS . "/lectureLogs/" . $log_file_name, $data, LOCK_EX);

        echo("<script>alert('{$xml->professorPage->startNewLectureSuccessfull[0]}');</script>");

        viewLectures($id,$xml,$conn);
    }
?>