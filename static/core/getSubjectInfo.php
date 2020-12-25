<?php
    session_start();
    require "../../constants.php";
    require_once "database_connection.php";

    $temp = $_SESSION["loggedInAs"] == "professor" ? "assistant"  : "professor";
    $primaryKey = $temp . "_id";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));

    $query = sprintf("SELECT title, titleEnglish, staff.name_surname as name_surname FROM `subject`
    LEFT JOIN staff ON `subject`.{$primaryKey} = staff.staff_id
    WHERE subject_id = '%s';", mysqli_real_escape_string($conn,$_POST['subject_id']));

    $data = $conn->query($query)->fetch_assoc();

    echo "
        {$xml->professorPage->subject_name[0]}: <br><br> {$data["title"]}/<br>{$data["titleEnglish"]} <br><br>
        {$xml->registrationPage->{$temp}[0]}: <br><br> {$data["name_surname"]} <br><br>
    ";

?>