<?php

namespace app\extensions\esbjerg_bindninger\models;

use app\inc\Model;
use app\extensions\esbjerg_bindninger\api\Search as ApiSearch;
use PDOException;

class Search extends Model
{
    /**
     * @param string $id
     * @param bool $overwrite
     * @return array<mixed>
     */
    public function go(string $id, bool $overwrite = true): array
    {
        $bindings = array();

        $query = "SELECT * FROM " . ApiSearch::SCHEMA . ".bindninger_planid WHERE planid=:id";
        $res = $this->prepare($query);
        try {
            $res->execute(array("id" => $id));
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response['code'] = 400;
            return $response;
        }
        $row = $this->fetchRow($res);
        $exist = (bool)$row;


        if (($overwrite) || (!$exist)) {
            $query = "SELECT ST_Astext(the_geom) as wkt FROM " . ApiSearch::SCHEMA . ".kommuneplanramme_geom WHERE planid=:id";
            $res = $this->prepare($query);
            try {
                $res->execute(array("id" => $id));
            } catch (PDOException $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                $response['code'] = 400;
                return $response;
            }
            $row = $this->fetchRow($res);
            $response['success'] = true;
            $response['data'] = $row["wkt"];


            $service = "https://webkort.esbjergkommune.dk/cbkort?";
            $qstr = "page=fkgws1-konflikt&sagstype=kommuneplan2022_34&outputformat=json&raw=false&geometri=" . urlencode($response['data']);

            $ch = curl_init($service);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);
            $res = json_decode(curl_exec($ch), true);
            curl_close($ch);

            $Arealbindninger = new Arealbindninger();
            $themes = $Arealbindninger->get();
            $targetname = null;
            $count = null;
            foreach ($themes["data"] as $theme) {
                foreach ($res["row"][0]["row"][0]["row"] as $resArr) {
                    $attrs = array();
                    for ($i = 0; $i < sizeof($resArr["row"]); $i++) {
                        if (isset($resArr["row"][$i]["targetname"])) {
                            $targetname = $resArr["row"][$i]["targetname"];
                        }
                        if (isset($resArr["row"][$i]["count"])) {
                            $count = $resArr["row"][$i]["count"];
                        }
                        if (isset($resArr["row"][$i]["row"])) {
                            foreach ($resArr["row"][$i]["row"] as $r) {
                                if ($theme["sps_themename"] == $targetname && (isset($theme["bindattribut"]) && isset($theme["bindvalue"]) && $theme["bindattribut"] != "" && $theme["bindvalue"] != "")) {
//                                     print_r($r);
                                    $attrs[] = $r["value"];
                                }
                            }
                        }
                    }

                    if ($theme["sps_themename"] == $targetname && $count > 0) {
                        if (isset($theme["bindattribut"]) && isset($theme["bindvalue"]) && $theme["bindattribut"] != "" && $theme["bindvalue"] != "") {
                            foreach ($attrs as $v) {
                                if ($v == $theme["bindvalue"]) {
                                    $bindings[$theme["rammefelt"]] = $theme["rammevalue"];
                                }
                            }
                        } else {
                            $bindings[$theme["rammefelt"]] = $theme["rammevalue"];
                        }
                    } else if ($theme["sps_themename"] == $targetname && $count == 0) {
                        $bindings[$theme["rammefelt"]] = null;
                    }
                }
            }
            arsort($bindings);
            $query = "DELETE FROM " . ApiSearch::SCHEMA . ".bindninger_planid WHERE planid=:id";
            $res = $this->prepare($query);
            try {
                $res->execute(array("id" => $id));
            } catch (PDOException $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                $response['code'] = 400;
                return $response;
            }
            $query = "INSERT INTO " . ApiSearch::SCHEMA . ".bindninger_planid (planid,bindninger) VALUES (:id, :bindninger)";
            $res = $this->prepare($query);
            try {
                $res->execute(array("id" => $id, "bindninger" => json_encode($bindings)));
            } catch (PDOException $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                $response['code'] = 400;
                return $response;
            }
            $response["data"] = $bindings;

        } else {
            $response["message"] = "Skipper";
        }
        return $response;
    }
}