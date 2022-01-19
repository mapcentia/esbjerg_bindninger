#!/usr/bin/php
<?php

use app\extensions\esbjerg_bindninger\models\Search;
use app\extensions\esbjerg_bindninger\api\Search as ApiSearch;
use \app\models\Database;

header("Content-type: text/plain");
include_once("../../../conf/App.php");
new \app\conf\App();
Database::setDb("esbjerg");
$conn = new \app\inc\Model();
$sql = "SELECT *  FROM " . ApiSearch::SCHEMA . ".kommuneplanramme_geom";
$result = $conn->execQuery($sql);
echo $conn->PDOerror[0];
$count = 0;
$search = new Search();

while ($row = $conn->fetchRow($result)) {
    $res = $search->go($row["planid"]);
//    print_r($res);

}
