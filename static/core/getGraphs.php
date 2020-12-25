<?php

    session_start();
    require "../../constants.php";
    require_once "database_connection.php";

    $logFolder = $_SESSION["loggedInAs"] === "professor" ? "lectureLogs" : "exerciseLogs";

    $xml = @simplexml_load_file(DIR_ROOT . DIR_LANGUAGES . "/{$_SESSION["language"]}.xml")  or die(file_get_contents(DIR_ROOT . "/error404.html"));
    $attended = "";
    $notAttended = "";
    $attendancesData = "[";
    $datesData = "[";

    $query = sprintf("SELECT DISTINCT count(student.student_id) as totalStudents FROM subject_study
                INNER JOIN study ON subject_study.study_id = study.study_id
                INNER JOIN student ON subject_study.study_id = student.study_id
                INNER JOIN faculty ON study.faculty_id = faculty.faculty_id
                INNER JOIN `subject` ON subject_study.subject_id = `subject`.subject_id
                WHERE subject_study.subject_id = '%s' AND  subject.is_inactive = '0'",mysqli_real_escape_string($conn,$_POST["subjectSelection"]));

    $totalStudents = $conn->query($query)->fetch_assoc()['totalStudents'];
    $attendedTotalLectures = 0;
    $totalStudentsAllLectures = 0;

    $dir = new DirectoryIterator(DIR_ROOT . DIR_MISCELLANEOUS . "/" . $logFolder);

    foreach ($dir as $fileinfo) {
        if (!$fileinfo->isDot()) {
            if(explode("_",$fileinfo->getFilename())[0] == $_POST["subjectSelection"]){
                $attended .= count(file($fileinfo->getPathname()))-2 . ",";
                $notAttended .= ($totalStudents-(count(file($fileinfo->getPathname()))-2)) . ",";
                $date = explode(".",explode("^",file_get_contents($fileinfo->getPathname()))[0])[1];
                $datesData .= "\"" . $date . "\",";
                $attendedTotalLectures += count(file($fileinfo->getPathname()))-2;
                $totalStudentsAllLectures += $totalStudents;
            }
        }
    }

    $attended = substr($attended,0,strlen($attended)-1); //remove last ,
    $notAttended = substr($notAttended,0,strlen($notAttended)-1);

    $attendancesData = "[{
        label: '{$xml->professorPage->attended[0]}',
        backgroundColor: '#33FF3F',
        data: [$attended]
    },{
        label: '{$xml->professorPage->notAttended[0]}',
        backgroundColor: '#FF3333',
        data: [$notAttended]
    }]";

    $datesData = substr($datesData,0,strlen($datesData)-1) . "]";
    
    if(empty($attended)) die("<i style='color:red;font-size:28px;'>" . $xml->errors->graphNotGenerated[0] . "</i>");

    if($_POST["graphValue"] === "barGraph"){
        echo "
                <script src='https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js'></script>
                <canvas id='myChart' style='position: relative; height:80vh; width:82vw;'></canvas>
                <script>
                var ctx = document.getElementById('myChart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: {$datesData},
                        datasets: {$attendancesData}
                    },
                    options: {
                        scales: {
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        },
                        title: {
                            display: true,
                            text: '{$xml->{$_SESSION["loggedInAs"] . "Page"}->barChartTitle[0]}'
                        }
                    }
                });
                </script>
            ";
    }
    else if($_POST["graphValue"] === "pieGraph"){
        $attendedTotalExercieses = 0;
        $totalStudentsAllExercieses = 0;

        $dir = new DirectoryIterator(DIR_ROOT . DIR_MISCELLANEOUS . "/exerciseLogs");

        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                if(explode("_",$fileinfo->getFilename())[0] == $_POST["subjectSelection"]){
                    $attended .= count(file($fileinfo->getPathname()))-2 . ",";
                    $notAttended .= ($totalStudents-(count(file($fileinfo->getPathname()))-2)) . ",";
                    $date = explode(".",explode("^",file_get_contents($fileinfo->getPathname()))[0])[1];
                    $datesData .= "\"" . $date . "\",";
                    $attendedTotalExercieses += count(file($fileinfo->getPathname()))-2;
                    $totalStudentsAllExercieses += $totalStudents;
                }
            }
        }

        if($attendedTotalExercieses === 0) die("<i style='color:red;font-size:28px;'>" . $xml->errors->graphNotGenerated[0] . "</i>");

        $percentageAL = round($attendedTotalLectures/$totalStudentsAllLectures*100);
        $percentageNAL = 100-$percentageAL;
        $percentageAE = round($attendedTotalExercieses/$totalStudentsAllExercieses*100);
        $percentageNAE = 100-$percentageAE;

        $label1 = $xml->professorPage->attended[0] . " " . strtolower($xml->professorPage->lecturesBtn[0]);
        $label2 = $xml->professorPage->notAttended[0] . " " . strtolower($xml->professorPage->lecturesBtn[0]);
        $label3 = $xml->professorPage->attended[0] . " " . strtolower($xml->assistantPage->exercisesBtn[0]);
        $label4 = $xml->professorPage->notAttended[0] . " " . strtolower($xml->assistantPage->exercisesBtn[0]);

        echo "
                <script src='https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js'></script>
                <canvas id='myChart' style='position: relative; height:81vh; width:164vw;'></canvas>
                <canvas id='myChart2' style='position: relative; height:80vh; width:164vw;'></canvas>
                <script>
                var ctx = document.getElementById('myChart').getContext('2d');
                var myChart = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['$label1','$label2'],
                        datasets: [
                            {backgroundColor: ['#33FF3F','#FF3333'], data : [$percentageAL,$percentageNAL] }
                        ]
                    },
                    options: {
                        title: {
                            display: true,
                            text: '{$xml->professorPage->pieChartTitle[0]}'
                        }
                    }
                });
                var ctx = document.getElementById('myChart2').getContext('2d');
                var myChart2 = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['$label3','$label4'],
                        datasets: [
                            {backgroundColor: ['#99ffa0','#ff8080'], data : [$percentageAE,$percentageNAE] }
                        ]
                    },
                    options: {
                        title: {
                            display: true,
                            text: '{$xml->assistantPage->pieChartTitle[0]}'
                        }
                    }
                });
                </script>
            ";
    }

?>