<?php
//error_reporting(E_ALL);
//ini_set("display_errors", 1);
header("Access-Control-Allow-Origin: *");

require './src/Nanite.php';
require './src/JsonDB.class.php';

$db = new JsonDB("./db/");

$dateLast = $db->selectAll("date");
$timeStampSave = $dateLast[0]["date"] + 86400; //One day, 60 seg * 60 min * 24 hour

date_default_timezone_set('Europe/Madrid');
$now = new DateTime();
$timeStampNow = $now->getTimestamp();

if ($timeStampSave < $timeStampNow) {
    //Reset db
    $db->deleteAll("date");
    $db->insert("date", array("date" => $now->getTimestamp()), true);

    $db->deleteAll("directors");
    $db->insert("directors", array("id"=> 1, "name" => "Alfred Hitchcock", "nationality" => "británico"), true);
    $db->insert("directors", array("id"=> 2, "name" => "Stanley Kubrick","nationality" => "estadounidense"), true);

    $db->deleteAll("movies");
    $db->insert("movies", array("id"=> 1, "director"=>1, "movie" => "La ventana indiscreta", "year"=> 1960), true);
    $db->insert("movies", array("id"=> 2, "director"=>1, "movie" => "Los pajaros", "year"=> 1972), true);
    $db->insert("movies", array("id"=> 3, "director"=>2, "movie" => "La naranja mecanica", "year"=> 1976), true);
    $db->insert("movies", array("id"=> 4, "director"=>2, "movie" => "El resplandor", "year"=> 1982), true);
}


Nanite::get('/', function() {
    showAllDirectors();
});

Nanite::get('/([0-9]+)', function($idDirector){
    showDirector($idDirector);
});

function showAllDirectors()
{ 
    global $db;
    $directorsAll = $db->selectAll("directors");

    header("Content-Type: application/json");
    if (count($directorsAll) == 0) {
        echo json_encode(-1);
    } else {
        echo json_encode($directorsAll);        
    }
    die();
}

function showDirector($idDirector)
{
    global $db;
    $director = $db->select("directors", "id", $idDirector);
    
    header("Content-Type: application/json");
    if (count($director) == 0) {
      echo json_encode(-1);
      die();
    }
    
    $movies = $db->select("movies", "director", $idDirector);
    $msg = $movies;
    echo json_encode($msg);
    die();
}

function lastId($table)
{
    global $db;
    $directorsAll = $db->selectAll($table);
    $lastDirector = $directorsAll[count($directorsAll) - 1];
    return $lastDirector['id'];
}

Nanite::post('/', function(){
    $objData = json_decode(file_get_contents("php://input"));
    
    header("Content-Type: application/json");
    if (!isset($objData->name)) {        
        echo json_encode(-1);
        die();
    }
    
    $nationality = '';
    if (isset($objData->nationality)) {
        $nationality = substr($objData->nationality, 0, 50);
    }


    global $db;
    $db->insert("directors",
            array("id"=> (lastId("directors") + 1),
                "name" => substr($objData->name, 0, 100),
                "nationality" => $nationality),
            true);

    showAllDirectors();
});

Nanite::post('/([0-9]+)', function($idDirector) {
    
    global $db;
    $director = $db->select("directors", "id", $idDirector);
            
    $objData = json_decode(file_get_contents("php://input"));        
    header("Content-Type: application/json");
    if (!isset($objData->movie) || count($director) == 0) {        
        echo json_encode(-1);
        die();
    }
    
    $year = '';
    if (isset($objData->year)) {
        $year = intval($objData->year);
    }
    
    $db->insert("movies",
            array("id"=> (lastId("movies") + 1),
                "director" => $idDirector,
                "movie" => substr($objData->movie, 0, 50),
                "year" => $year),
            true);

    showDirector($idDirector);
});

if (!Nanite::$routeProccessed) {
    header('HTTP/1.1 405 Method Not Allowed');
    header("Content-Type: text/html");

    echo 'GET / : List all directors<br/>'.
          'GET /{id} : List director and movies<br/>'.
          'POST / Request (nameDirector, nationality): Create director<br/>'.
          'POST /{id} Request (nameMovie, year): Create movie<br/>';
}


