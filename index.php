<!DOCTYPE html>
<html>
    <head>
        <meta charset='utf-8'>
        <meta http-equiv='X-UA-Compatible' content='IE=edge'>
        <title>ISEPS</title>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    </head>
    <body style="background-color: #d3fff8;">
        <?php

            session_start();          
            
            require "constants.php";
            require DIR_CORE . "/database_connection.php";

            if(@$_SESSION["language"] !== "english" && 
                    @$_SESSION["language"] !== "serbianCyrilic" 
                            && @$_SESSION["language"] !== "serbianLatin") 
                $_SESSION["language"] = "serbianCyrilic";
            else if(isset($_GET["language"])) @$_SESSION["language"] = $_GET["language"];

            $xml = @simplexml_load_file(DIR_LANGUAGES . "/{$_SESSION["language"]}.xml") or die(file_get_contents("error404.html"));

            if($_SESSION["loggedInAs"] !== "admin"  && $_SESSION["loggedInAs"] !== "professor"  
                && $_SESSION["loggedInAs"] !== "assistant") {
                        $_SESSION["page"] = "login";
                        $_SESSION["loggedInAs"] = null;
                    }

            if(@$_GET["page"] !== null) $_SESSION["page"] = $_GET["page"];
            else header("Location:index.php?language={$_SESSION["language"]}&page={$_SESSION["page"]}", true, 307);
            
            echo initiateHeader($xml);
                
            if($_SESSION["loggedInAs"] === "professor"){
                switch($_SESSION["page"]){
                    case "teaching_subject_management": echo initiateProfessorPage1($xml, $conn); break;
                    case "add_new_subject": echo initiateProfessorPage2($xml, $conn); break;
                    case "professor_reports": echo initiateProfessorPage3($xml, $conn); break;
                    default: echo file_get_contents("error404.html"); break;
                }
            }

            else if($_SESSION["loggedInAs"] === "assistant"){
                switch($_SESSION["page"]){
                    case "teaching_exercises": echo initiateAssistantPage1($xml, $conn); break;
                    case "exercises_reports": echo initiateAssistantPage2($xml, $conn); break;
                    default: echo file_get_contents("error404.html"); break;
                }
            }

            else if($_SESSION["loggedInAs"] === "admin"){
                switch($_SESSION["page"]){
                    case "staff_registration": echo initiateAdminPage1($xml); break;
                    case "staff_management": echo initiateAdminPage2($xml, $conn); break;
                    case "students_management": echo initiateAdminPage3($xml, $conn); break;
                    case "admin_reports": echo initiateAdminPage4($xml, $conn); break;
                    default: echo file_get_contents("error404.html"); break;
                }
            }

            else {
                switch(@$_SESSION["page"]){
                    case "login": echo initiateLoginPage($xml); break;
                    case "admin_access": echo initiateAdminAccessPage($xml, $conn); break;
                    default: echo file_get_contents("error404.html"); break;
                }
            }   
    
            echo initiateFooter($xml);
        ?>    
    </body>
</html>

<?php
    
    function initiateHeader($xml){
        $header = file_get_contents(DIR_TEMPLATES . "/header.html");
        
        $links = "";
        $pageName = "";
        $linkID = 1;
        $logoutHref = DIR_CORE . '/logout.php';

        if($_SESSION["loggedInAs"] === null)
            $pageName = "home";
        else {
            $pageName = $_SESSION["loggedInAs"];
            $links .= "<a class='navbar-brand mr-2 mr-md-2' style='font-size: 25px !important; margin-top: 0.8em; color: red;'
                            href={$logoutHref} class=''>". $_SESSION['loggedInUser'] . "</a>";
        }
        
        do {   
            $links .= "
                <a class='navbar-brand mr-2 mr-md-2' 
                style='font-size: 25px !important; margin-top: 0.8em;' 
                href='index.php?language={$_SESSION["language"]}&page={$xml->navigation->{$pageName . "Page"}->{"link" . $linkID}->href[0]}'>
                {$xml->navigation->{$pageName . "Page"}->{"link" . $linkID}->name[0]}</a>
            ";
            $linkID++;
        } while(isset($xml->navigation->{$pageName . "Page"}->{"link" . $linkID}));

        $header = str_replace("{IMAGE_SRC}", DIR_MISCELLANEOUS . "/logo.png", $header);
        $header = str_replace("{LINKS}", $links, $header);

        return $header;
    }

    function initiateAdminAccessPage($xml, $conn){
        if(@$_SESSION["isAdminLoggedOut"]){
            $_SERVER["PHP_AUTH_USER"] = null;
            $_SERVER["PHP_AUTH_PW"] = null;
            $_SESSION["isAdminLoggedOut"] = false;
        }
        authenticateAdmin($xml,$conn);
    }

    function initiateLoginPage($xml){
        $page_context = file_get_contents(DIR_TEMPLATES . "/login.html");
        $page_context = str_replace("{FORM_ACTION}", DIR_CORE . "/login.php", $page_context);
        $page_context = str_replace("{IMAGE_SRC}", DIR_CORE . "/captcha.php", $page_context);
        $page_context = str_replace("{HEADER_TITLE}",$xml->loginPage->headerTitle[0], $page_context);
        $page_context = str_replace("{id}",$xml->loginPage->id[0], $page_context);
        $page_context = str_replace("{password}",$xml->registrationPage->password[0], $page_context);
        $page_context = str_replace("{admin}",$xml->loginPage->admin[0], $page_context);
        $page_context = str_replace("{professor}",$xml->registrationPage->professor[0], $page_context);
        $page_context = str_replace("{assistant}",$xml->registrationPage->assistant[0], $page_context);
        $page_context = str_replace("{captcha}",$xml->registrationPage->captcha[0], $page_context);
        $page_context = str_replace("{LOGIN}",$xml->loginPage->login[0], $page_context);
        $page_context = str_replace("{RESET}",$xml->registrationPage->reset[0], $page_context);
        
        return $page_context;
    }

    function initiateProfessorPage1($xml,$conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/teachingSubjectsManagement.html");
        $page_context = str_replace("{FORM_ACTION}", DIR_CORE . "/teaching_subjects_managment.php", $page_context);
        $page_context = str_replace("{ASSISTENT_TITLE}",$xml->registrationPage->assistant[0], $page_context);
        $page_context = str_replace("{SUBJECT_TITLE}",explode("(",$xml->professorPage->subject_name[0])[0], $page_context);
        
        $tBody = "";
        
        $query = sprintf("SELECT subject_id, title, titleEnglish, staff.name_surname as assistant_name_surname, is_inactive FROM `subject`
        LEFT JOIN staff ON `subject`.assistant_id = staff.staff_id
        WHERE professor_id = '%s';", mysqli_real_escape_string($conn,$_SESSION['loggedInId']));

        $data = $conn->query($query);

        while($subject = $data->fetch_assoc()){
            $subjectName = $_SESSION["language"] === "english" ? $subject["titleEnglish"] : $subject["title"];

            $startYearButton = "<input type='submit' id='startSY_{$subject['subject_id']}' name='startSY_{$subject['subject_id']}'class='btn btn-success' value='{$xml->professorPage->startSYBtn[0]}'></input>";
            $endYearButton = "<input type='submit' id='endSY_{$subject['subject_id']}' name='endSY_{$subject['subject_id']}'class='btn btn-danger' value='{$xml->professorPage->endSYBtn[0]}'></input>";

            $sYInput = $subject["is_inactive"] === '1' ? $startYearButton : $endYearButton;

            $tBody .= "
                <tr id='tr_{$subject['subject_id']}'>
                    <td>{$subject["assistant_name_surname"]}</td>
                    <td>$subjectName</td>
                    <td>
                    <input type='submit' id='lectures_{$subject['subject_id']}' name='lectures_{$subject['subject_id']}' class='btn btn-primary' value='{$xml->professorPage->lecturesBtn[0]}'></input>&nbsp;
                        <input type='submit' id='details_{$subject['subject_id']}' name='details_{$subject['subject_id']}' class='btn btn-info' value='{$xml->professorPage->detailsBtn[0]}'></input>&nbsp;
                        {$sYInput}
                    </td>
                </tr>
            ";
        }

        $page_context = str_replace("{tBody}",$tBody, $page_context);

        return $page_context;
    }

    function initiateProfessorPage2($xml,$conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/addNewSubject.html");
        $page_context = str_replace("{FORM_ACTION}", DIR_CORE . "/addNewSubject.php", $page_context);
        $page_context = str_replace("{SUBJECT_NAME_TITLE}",$xml->professorPage->subject_name[0], $page_context);
        $page_context = str_replace("{ASSISTANT_SELECTION_TITLE}",$xml->professorPage->assistant_selection[0], $page_context);
        
        $assistants = "<option value=''>-</option>";
        $studies = "";

        $data = $conn->query("SELECT name_surname, staff_id FROM staff WHERE role = 'assistant';");
        
        while($assistant = $data->fetch_assoc()){
            $assistants .= "
                <option value='{$assistant["staff_id"]}'>{$assistant["name_surname"]}</option>
            ";
        }

        $titleLanguage = $_SESSION["language"] === "english" ? "titleEnglish" : "title";

        $data = $conn->query("SELECT $titleLanguage, taughtIn, study_id FROM study;");
        $numberOfStudies = 0;    


        while($study = $data->fetch_assoc()){
            for($i = 1; $i < 5; $i++){
                
                if($_SESSION["language"] === "english")
                    $localization = $study["taughtIn"] === "srpski" ? "Serbian language" : "English language";
                else 
                    $localization = $study["taughtIn"] === "srpski" ? "Српски језик" : "Енглески језик";
                
                $formatedValue = $study["study_id"] . '_' . $i;
                $formatedName = $study["$titleLanguage"] . " - " . $localization . " ($i)";
                
                $studies .= "
                    <option value='{$formatedValue}'>{$formatedName}</option>
                ";
                $numberOfStudies++;
            }
        }

        $page_context = str_replace("{ASSISTANT_SELECTION_VALUES}",$assistants, $page_context);
        $page_context = str_replace("{STUDY_TITLE}",$xml->professorPage->study[0], $page_context);
        $page_context = str_replace("{STUDY_SIZE}",$numberOfStudies, $page_context);
        $page_context = str_replace("{STUDY_VALUES}",$studies, $page_context);
        $page_context = str_replace("{ADD_NEW_SUBJECT}",$xml->professorPage->add_new_subject[0], $page_context);
        $page_context = str_replace("{RESET}",$xml->registrationPage->reset[0], $page_context);

        return $page_context;
    }

    function initiateProfessorPage3($xml,$conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/professorReports.html");
        $page_context = str_replace("{LABEL_SUBJECTS}",$xml->professorPage->selectSubject[0], $page_context);
        $page_context = str_replace("{FORM_ACTION}",DIR_CORE . "/getGraphs.php", $page_context);
        $page_context = str_replace("{AJAX_URL}",DIR_CORE . "/getSubjectInfo.php", $page_context);
        $page_context = str_replace("{GENERATE_BAR_GRAPH}",$xml->professorPage->generateBGraph[0], $page_context);
        $page_context = str_replace("{GENERATE_PIE_GRAPH}",$xml->professorPage->generatePGraph[0], $page_context);
        $page_context = str_replace("{RESET}",$xml->registrationPage->reset[0], $page_context);

        $subjects = "<option value=''>-</option>";

        $query = $query = sprintf("SELECT subject_id FROM subject WHERE professor_id = '%s'"
        ,mysqli_real_escape_string($conn,$_SESSION['loggedInId']));

        $data = $conn->query($query) or die(file_get_contents("error404.html"));

        while($subject_id = $data->fetch_assoc()){
            $subjects .= "<option value='{$subject_id["subject_id"]}'>ID: {$subject_id["subject_id"]}</option>";
            
        }

        $page_context = str_replace("{SUBJECTS}",$subjects, $page_context);

        return $page_context;
    }

    function initiateAssistantPage1($xml,$conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/teachingExercises.html");
        $page_context = str_replace("{FORM_ACTION}", DIR_CORE . "/teaching_exercises.php", $page_context);
        $page_context = str_replace("{PROFESSOR_TITLE}",$xml->registrationPage->professor[0], $page_context);
        $page_context = str_replace("{SUBJECT_TITLE}",explode("(",$xml->professorPage->subject_name[0])[0], $page_context);
        
        $tBody = "";
        
        $query = sprintf("SELECT subject_id, title, titleEnglish, staff.name_surname as professor_name_surname FROM `subject`
        INNER JOIN staff ON `subject`.professor_id = staff.staff_id
        WHERE assistant_id = '%s';", mysqli_real_escape_string($conn,$_SESSION['loggedInId']));
   
        $data = $conn->query($query);

        while($subject = $data->fetch_assoc()){
            $subjectName = $_SESSION["language"] === "english" ? $subject["titleEnglish"] : $subject["title"];
        
            $tBody .= "
                <tr id='tr_{$subject['subject_id']}'>
                    <td>{$subject["professor_name_surname"]}</td>
                    <td>$subjectName</td>
                    <td>
                        <input type='submit' id='exercises_{$subject['subject_id']}' name='exercises_{$subject['subject_id']}' class='btn btn-primary' value='{$xml->assistantPage->exercisesBtn[0]}'></input>&nbsp;
                        <input type='submit' id='details_{$subject['subject_id']}' name='details_{$subject['subject_id']}' class='btn btn-info' value='{$xml->professorPage->detailsBtn[0]}'></input>&nbsp;
                    </td>
                </tr>
            ";
        }

        $page_context = str_replace("{tBody}",$tBody, $page_context);

        return $page_context;
    }

    function initiateAssistantPage2($xml, $conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/assistantReports.html");
        $page_context = str_replace("{LABEL_SUBJECTS}",$xml->professorPage->selectSubject[0], $page_context);
        $page_context = str_replace("{FORM_ACTION}",DIR_CORE . "/getGraphs.php", $page_context);
        $page_context = str_replace("{AJAX_URL}",DIR_CORE . "/getSubjectInfo.php", $page_context);
        $page_context = str_replace("{GENERATE_BAR_GRAPH}",$xml->professorPage->generateBGraph[0], $page_context);
        $page_context = str_replace("{RESET}",$xml->registrationPage->reset[0], $page_context);

        $subjects = "<option value=''>-</option>";

        $query = $query = sprintf("SELECT subject_id FROM subject WHERE assistant_id = '%s'"
        ,mysqli_real_escape_string($conn,$_SESSION['loggedInId']));

        $data = $conn->query($query) or die(file_get_contents("error404.html"));

        while($subject_id = $data->fetch_assoc()){
            $subjects .= "<option value='{$subject_id["subject_id"]}'>ID: {$subject_id["subject_id"]}</option>";
            
        }

        $page_context = str_replace("{SUBJECTS}",$subjects, $page_context);

        return $page_context;
    }

    function initiateAdminPage1($xml){
        $page_context = file_get_contents(DIR_TEMPLATES . "/registration.html");
        $page_context = str_replace("{FORM_ACTION}", DIR_CORE . "/register.php", $page_context);
        $page_context = str_replace("{IMAGE_SRC}", DIR_CORE . "/captcha.php", $page_context);
        $page_context = str_replace("{HEADER_TITLE}",$xml->registrationPage->headerTitle[0], $page_context);
        $page_context = str_replace("{nameSurname}",$xml->registrationPage->nameSurname[0], $page_context);
        $page_context = str_replace("{email}",$xml->registrationPage->email[0], $page_context);
        $page_context = str_replace("{password}",$xml->registrationPage->password[0], $page_context);
        $page_context = str_replace("{passwordConfirm}",$xml->registrationPage->passwordConfirm[0], $page_context);
        $page_context = str_replace("{professor}",$xml->registrationPage->professor[0], $page_context);
        $page_context = str_replace("{assistant}",$xml->registrationPage->assistant[0], $page_context);
        $page_context = str_replace("{captcha}",$xml->registrationPage->captcha[0], $page_context);
        $page_context = str_replace("{REGISTER}",$xml->registrationPage->register[0], $page_context);
        $page_context = str_replace("{RESET}",$xml->registrationPage->reset[0], $page_context);
        $page_context = str_replace("{CSV_MESSAGE}",$xml->registrationPage->csvMessage[0], $page_context);
        
        return $page_context;
    }

    function initiateAdminPage2($xml,$conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/staffManagement.html");
        $page_context = str_replace("{FORM_ACTION}", DIR_CORE . "/staff_managment.php", $page_context);
        $page_context = str_replace("{NS_TITLE}",$xml->registrationPage->nameSurname[0], $page_context);
        $page_context = str_replace("{EMAIL_TITLE}",$xml->registrationPage->email[0], $page_context);
        $page_context = str_replace("{PASSWORD_TITLE}",$xml->adminPage->passwordTitle[0], $page_context);
        $page_context = str_replace("{ROLE_TITLE}",$xml->adminPage->roleTitle[0], $page_context);
        
        $tBody = "";
        
        $data = $conn->query("SELECT * FROM staff WHERE NOT staff_id = 1"); //not showing admin data

        while($staff_member = $data->fetch_assoc()){
            $email = explode("@",$staff_member["email"])[0];

            $tBody .= "
                <tr>
                    <td><input type='text' id='newNS_{$staff_member['staff_id']}' name='newNS_{$staff_member['staff_id']}' placeholder='{$staff_member['name_surname']}'></td>
                    <td><input type='text' id='newUE_{$staff_member['staff_id']}' name='newUE_{$staff_member['staff_id']}' placeholder='{$email}'></input>@universityMail.rs</td>
                    <td><input type='password' id='newPASS_{$staff_member['staff_id']}' name='newPASS_{$staff_member['staff_id']}' placeholder='********'></input></td>
                    <td><input type='text' id='oldRole_{$staff_member['staff_id']}' name='oldRole_{$staff_member["staff_id"]}' value='{$staff_member["role"]}' readonly></input></td>
                    <td>
                        <input type='submit' id='switchRole_{$staff_member['staff_id']}' name='switchRole_{$staff_member['staff_id']}' class='btn btn-info' value='{$xml->adminPage->switchRoleBtn[0]}'></input>&nbsp;
                        <input type='submit' id='edit_{$staff_member['staff_id']}' name='edit_{$staff_member['staff_id']}' class='btn btn-warning' value='{$xml->adminPage->editBtn[0]}'></input>&nbsp;
                        <input type='submit' id='delete_{$staff_member['staff_id']}' name='delete_{$staff_member['staff_id']}'class='btn btn-danger' value='{$xml->adminPage->deleteBtn[0]}'></input>
                    </td>
                </tr>
            ";
        }

        $page_context = str_replace("{tBody}",$tBody, $page_context);
        return $page_context;
    }

    function initiateAdminPage3($xml, $conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/studentsManagment.html");
        $page_context = str_replace("{FORM_ACTION}", DIR_CORE . "/students_managment.php", $page_context);
        $page_context = str_replace("{NS_TITLE}",$xml->registrationPage->nameSurname[0], $page_context);
        $page_context = str_replace("{INDEX_NO}",$xml->adminPage->indexNumber[0], $page_context);
        $page_context = str_replace("{PASSWORD_TITLE}",$xml->adminPage->passwordTitle[0], $page_context);
        $page_context = str_replace("{EMAIL_TITLE}",$xml->registrationPage->email[0], $page_context);
        $page_context = str_replace("{FACULTY_TITLE - STUDY_TITLE(YEAR)}",$xml->adminPage->facultySY[0], $page_context);
        
        $tBody = "";
        
        $data = $conn->query("SELECT * FROM student");

        while($student = $data->fetch_assoc()){
            $email = explode("@",$student["studentEmail"])[0];
            $studentEnrollment_temp = getEnrollment($student['student_id'], $conn);
            $studentEnrollment = explode("-",$studentEnrollment_temp)[0] . "<br>" . explode("-",$studentEnrollment_temp)[1];

            $tBody .= "
                <tr>
                    <td><input type='text' id='newNS_{$student['student_id']}' name='newNS_{$student['student_id']}' placeholder='{$student['name_surname']}'></td>
                    <td><input type='text' id='newIX_{$student['student_id']}' name='newIX_{$student['student_id']}' placeholder='{$student['indexNo']}'></td>
                    <td><input type='password' id='newPASS_{$student['student_id']}' name='newPASS_{$student['student_id']}' placeholder='********'></input></td>
                    <td><input type='text' id='newUE_{$student['student_id']}' name='newUE_{$student['student_id']}' placeholder='{$email}'></input>@universityMail.rs</td>
                    <td>$studentEnrollment</td>
                    <td>
                        <input type='submit' id='edit_{$student['student_id']}' name='edit_{$student['student_id']}' class='btn btn-warning' value='{$xml->adminPage->editBtn[0]}'></input>&nbsp;
                        <input type='submit' id='delete_{$student['student_id']}' name='delete_{$student['student_id']}'class='btn btn-danger' value='{$xml->adminPage->deleteBtn[0]}'></input>
                    </td>
                </tr>
            ";
        }

        $page_context = str_replace("{tBody}",$tBody, $page_context);
        return $page_context;
    }

    function initiateAdminPage4($xml, $conn){
        $page_context = file_get_contents(DIR_TEMPLATES . "/adminReports.html");
        $page_context = str_replace("{LABEL_SUBJECTS}",$xml->professorPage->selectSubject[0], $page_context);
        $page_context = str_replace("{FORM_ACTION}",DIR_CORE . "/getGraphs.php", $page_context);
        $page_context = str_replace("{AJAX_URL}",DIR_CORE . "/getSubjectInfo.php", $page_context);
        $page_context = str_replace("{GENERATE_BAR_GRAPH}",$xml->professorPage->generateBGraph[0], $page_context);
        $page_context = str_replace("{GENERATE_PIE_GRAPH}",$xml->professorPage->generatePGraph[0], $page_context);
        $page_context = str_replace("{RESET}",$xml->registrationPage->reset[0], $page_context);

        $subjects = "<option value=''>-</option>";

        $query = $query = sprintf("SELECT subject_id FROM subject");

        $data = $conn->query($query) or die(file_get_contents("error404.html"));

        while($subject_id = $data->fetch_assoc()){
            $subjects .= "<option value='{$subject_id["subject_id"]}'>ID: {$subject_id["subject_id"]}</option>";
            
        }

        $page_context = str_replace("{SUBJECTS}",$subjects, $page_context);

        return $page_context;
    }

    function getEnrollment($student_id, $conn){
        $titleLanguage = $_SESSION["language"] === "english" ? "titleEnglish" : "title";
        $query = sprintf("SELECT concat(faculty.$titleLanguage, ' - ', study.$titleLanguage, ' (', student.enrolledYear, ')') as enrollmentData
            FROM faculty 
            INNER JOIN study ON study.faculty_id = faculty.faculty_id
            INNER JOIN student ON student.study_id = study.study_id
            WHERE student.student_id = '%s';", mysqli_real_escape_string($conn, $student_id));
        
        return $conn->query($query)->fetch_assoc()["enrollmentData"];
    }

    function initiateFooter($xml){
        $footer = file_get_contents(DIR_TEMPLATES . "/footer.html");
        $footer = str_replace("{TITLE}",$xml->navigation->title[0], $footer);
        $footer = str_replace("{COPYRIGHT}",$xml->footer->copyright[0], $footer);
        $footer = str_replace("{ENGLISH}",$xml->footer->language1[0], $footer);
        $footer = str_replace("{SERBIAN_CYRILIC}",$xml->footer->language2[0], $footer);
        $footer = str_replace("{SERBIAN_LATIN}",$xml->footer->language3[0], $footer);
        $footer = str_replace("{PAGE}",$_GET["page"], $footer);
        
        return $footer;
    }

    function authenticateAdmin($xml,$conn){
        $adminPass = $conn->query("SELECT password_hash FROM staff where staff_id = 1")->fetch_assoc()["password_hash"];
        
        header("WWW-Authenticate: Basic realm=\"Administrator panel\"");
        header("HTTP/1.0 401 Unauthorized");
        if (@$_SERVER['PHP_AUTH_USER'] === 'Administrator' && password_verify($_SERVER['PHP_AUTH_PW'], $adminPass)){
            $_SESSION["loggedInAs"] = "admin";
            $_SESSION['loggedInUser'] = $conn->query("SELECT name_surname FROM staff WHERE staff_id = 1")->fetch_assoc()["name_surname"];
            header("Location:index.php?language={$_SESSION["language"]}&page=staff_registration",true, 301);
        }

        echo "<script>window.location = 'index.php?language={$_SESSION["language"]}&page=login';</script>";
    }

    $conn->close();
?>