<?php

namespace app\extensions\esbjerg_bindninger\models;

use app\inc\Model;
use app\extensions\esbjerg_bindninger\api\Search as ApiSearch;
use PDOException;

class Codes extends Model
{
    /**
     * @var array<mixed>
     */
    private $codes;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $varName
     * @param string|null $rel
     * @param bool $json
     * @return void
     */
    public function getRefs(string $varName, string $rel = null, bool $json = false) : void
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
                    $this->getCodes($table);

                }
            }
        }

        if (!$json) {
            print("var $varName = ");
        }
        print(json_encode($this->codes));
        die();
    }

    public function getFields(string $varName, bool $json = false) : void
    {
        $query = "SELECT rammefelt, header FROM " . ApiSearch::SCHEMA . ".arealbindninger_kp22";

        $res = $this->prepare($query);
        try {
            $res->execute();
        } catch (PDOException $e) {
            throw $e;
        }
        while ($row = $this->fetchRow($res)) {
            $this->codes[$row["rammefelt"]] = $row["header"];
        }

        if (!$json) {
            print("var $varName = ");
        }
        print(json_encode($this->codes));

        die();
    }

    /**
     * @param string $table
     * @param bool $join
     * @return void
     */
    private function getCodes(string $table, bool $join = true) : void
    {
        if ($join) {
            $query = "SELECT fieldkey, textvalue||'|'||textvalue2 as text FROM public.$table";
        } else {
            $query = "SELECT fieldkey, textvalue as text FROM public.$table";

        }
        $res = $this->prepare($query);
        try {
            $res->execute();
        } catch (PDOException $e) {
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