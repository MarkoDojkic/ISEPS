<?php

    session_start();
    require "../../constants.php";
    require_once "database_connection.php";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));

    $table = $_SESSION['loggedInAs'] === 'professor' ? 'lecture' : 'exercise';
    $primaryKey = $table . '_id';

    $query = sprintf("SELECT start_time, end_time, log_file_name FROM $table WHERE
                    $primaryKey = '%s'", mysqli_real_escape_string($conn,$_POST["$primaryKey"]));

    $data = $conn->query($query)->fetch_assoc() or die("<i style='color:red;font-size:14px;'> - " . $xml->errors->getLEInfoError[0] . "</i><br>");

    $logLocation = DIR_ROOT . DIR_MISCELLANEOUS . "/" . $table . "Logs/" . $data["log_file_name"];
    $dateLocale = $_SESSION["language"] === "english" ? "Y-m-d " : "d.m.Y ";
    $date = date($dateLocale, strtotime(explode(".",explode("^",file_get_contents($logLocation))[0])[1])); //1. 2020-12-01 ^11:30$12:45

    $theader = ucfirst($table) . " #" . explode(".",file_get_contents($logLocation))[0] . " ( " . $date . ")";

    $attendance = $xml->{$_SESSION['loggedInAs'] . "Page"}->notOverYet[0]; 

    if(date_diff(new DateTime(), new DateTime($date . $data["end_time"]))->invert === 1) {//time in past => lecture/exercise finished

        $query = sprintf("SELECT DISTINCT count(student.student_id) as totalStudents FROM subject_study
                INNER JOIN study ON subject_study.study_id = study.study_id
                INNER JOIN student ON subject_study.study_id = student.study_id
                INNER JOIN faculty ON study.faculty_id = faculty.faculty_id
                INNER JOIN `subject` ON subject_study.subject_id = `subject`.subject_id
                WHERE subject_study.subject_id = '%s' AND  subject.is_inactive = '0'",mysqli_real_escape_string($conn,explode("_",$data["log_file_name"])[0]));

        $totalStudents = $conn->query($query)->fetch_assoc()['totalStudents']; 
        $percetage = round( (count(file($logLocation))-2) /$totalStudents *100 );
        
        $attendance = $percetage . "% (" . (count(file($logLocation))-2) . "/" . $totalStudents . ")";
    }

    echo "<table class='table table-bordered table-inverse table-responsive' style='font-size: 13px;'>
            <thead class='thead-inverse bg-primary'>
                <tr>
                    <th colspan='2' style='text-align: center;'>{$theader}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style='text-align: right;'>{$xml->assistantPage->startTime[0]}</td>
                    <td style='text-align: left;'>{$data["start_time"]}</td>
                </tr>
                <tr>
                    <td style='text-align: right;'>{$xml->assistantPage->endTime[0]}</td>
                    <td style='text-align: left;'>{$data["end_time"]}</td>
                </tr>
                <tr>
                    <td style='text-align: right;'>{$xml->assistantPage->attendance[0]}</td>
                    <td style='text-align: left;'>{$attendance}</td>
                </tr>
            </tbody>
        </table>";
?>