<?php

namespace app\extensions\esbjerg_bindninger\models;

use app\inc\Model;

class Search extends Model
{
    public function go($id, $overwrite = true)
    {
        $bindings = array();

        $query = "SELECT * FROM kommuneplan18.bindninger_planid WHERE planid=:id";
        $res = $this->prepare($query);
        try {
            $res->execute(array("id" => $id));
        } catch (\PDOException $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
            $response['code'] = 400;
            return $response;
        }
        $row = $this->fetchRow($res);
        $exist = (bool)$row;


        if (($overwrite) || (!$exist)) {
            $query = "SELECT ST_Astext(the_geom) as wkt FROM kommuneplan18.kpplandk2 WHERE planid=:id";
            $res = $this->prepare($query);
            try {
                $res->execute(array("id" => $id));
            } catch (\PDOException $e) {
                $response['success'] = false;
                $response['message'] = $e->getMessage();
                $response['code'] = 400;
                return $response;
            }
            $row = $this->fetchRow($res);
            $response['success'] = true;
            $response['data'] = $row["wkt"];

            $service = "https://webkort.esbjergkommune.dk/cbkort?";
            $qstr = "page=fkgws1-konflikt&sagstype=std_soegning&outputformat=json&raw=false&geometri=" . urlencode($response['data']);

            $url = $service . $qstr;

            //die($url);

            //$res = json_decode(Util::wget($url), true);

            $ch = curl_init($service);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 0);


            $res = json_decode(curl_exec($ch), true);

            curl_close($ch);

            //die($res);


            $Arealbindninger = new Arealbindninger();

            $themes = $Arealbindninger->get();


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
                                if ($theme["sps_themename"] == "theme-" . $targetname && (isset($theme["bindattribut"]) && isset($theme["bindvalue"]) && $theme["bindattribut"] != "" && $theme["bindvalue"] != "")) {
                                    // print_r($r);
                                    $attrs[] = $r["value"];
                                }
                            }
                        }
                    }
                    if (sizeof($attrs) > 0) {
                        //echo $theme["sps_themename"] . " -> " . $theme["bindattribut"] . "\n";
                        //print_r($attrs);
                        //print_r($resArr);

                    }

                    if ($theme["sps_themename"] == "theme-" . $targetname && $count > 0) {

                        if (isset($theme["bindattribut"]) && isset($theme["bindvalue"]) && $theme["bindattribut"] != "" && $theme["bindvalue"] != "") {

                            foreach ($attrs as $k => $v) {

                                if ($v == $theme["bindvalue"]) {
                                    $bindings[$theme["rammefelt"]] = $theme["rammevalue"];
                                }

                            }

                        } else {
                            $bindings[$theme["rammefelt"]] = $theme["rammevalue"];

                        }

                    } else if ($theme["sps_themename"] == "theme-" . $targetname && $count == 0) {
                        $bindings[$theme["rammefelt"]] = null;
                    }
                }
            }

            arsort($bindings);


            $query = "DELETE FROM kommuneplan18.bindninger_planid WHERE planid=:id";
            $res = $this->prepare($query);
            try {
                $res->execute(array("id" => $id));
            } catch (\PDOException $e) {
                echo "Fandtes ikke\n";
            }

            $query = "INSERT INTO kommuneplan18.bindninger_planid (planid,bindninger) VALUES (:id, :bindninger)";
            $res = $this->prepare($query);
            try {
                $res->execute(array("id" => $id, "bindninger" => json_encode($bindings)));
            } catch (\PDOException $e) {
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