<?php

namespace app\extensions\esbjerg_bindninger\models;

use app\inc\Model;

class Arealbindninger extends Model
{

    public function get(): array
    {
        $query = "SELECT * FROM arealbindninger.tforms120870101008319_join";
        $res = $this->prepare($query);
        try {
            $res->execute();
        } catch (\PDOException $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response['code'] = 400;
            return $response;
        }
        while ($row = $this->fetchRow($res)){
            $response['data'][] =  $row;
        }
        $response['success'] = true;
        return $response;
    }

}