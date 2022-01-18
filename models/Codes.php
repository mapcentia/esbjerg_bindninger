<?php

namespace app\extensions\esbjerg_bindninger\models;

use app\inc\Model;
use app\models\Database;

class Codes extends Model
{
    private $codes;

    public function __construct()
    {
        parent::__construct();
    }

    public function getRefs($varName, $rel = null, $json = null)
    {

        $arr = array(
            "bi2",
            "bebygpct",
            "hoejdebestem",
            "bi_bev_fred",
            "hovedanv",
            "kvalitetsbestem",
            "opholdsareal",
            "raekke",
            "rammeanv",
            "ref_pl_zone",
            "ref_st_zone",
            "reserv",
            "stoejbestem",
            "udvidet_anv",
        );

        if ($rel) {
            $this->getCodes($rel, false);
        } else {

            foreach ($arr as $table) {
                if ($table == "hovedanv") {
                    $this->getCodes($table, false);
                } else {
                    $this->getCodes($table, true);

                }
            }
        }

        if (!$json) {
            print("var {$varName} = ");
        }
        print(json_encode($this->codes));
        die();
    }

    public function getFields($varName, $json = false)
    {
        $query = "SELECT rammefelt, head FROM arealbindninger.tforms120870101008319_join";

        $res = $this->prepare($query);
        try {
            $res->execute();
        } catch (\PDOException $e) {
            throw $e;
        }
        while ($row = $this->fetchRow($res)) {
            $this->codes[$row["rammefelt"]] = $row["head"];
        }

        if (!$json) {
            print("var {$varName} = ");
        }
        print(json_encode($this->codes));

        die();
    }

    private function getCodes($table, $join = true)
    {
        if ($join) {
            $query = "SELECT fieldkey, textvalue||'|'||textvalue2 as text FROM public.{$table}";
        } else {
            $query = "SELECT fieldkey, textvalue as text FROM public.{$table}";

        }
        $res = $this->prepare($query);
        try {
            $res->execute();
        } catch (\PDOException $e) {
            throw $e;
        }
        while ($row = $this->fetchRow($res)) {

            if (isset($this->codes[$row["fieldkey"]])) {
                echo $row["fieldkey"] . ": " . $table . "\n";

            }

            $this->codes[$row["fieldkey"]] = nl2br($row["text"]);
        }
    }

}