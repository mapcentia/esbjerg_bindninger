<?php

use app\inc\Route;
use app\models\Database;

Database::setDb("esbjerg");
Route::add("extensions/esbjerg_bindninger/api/search");
Route::add("extensions/esbjerg_bindninger/api/codes");
