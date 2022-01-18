<?php
namespace app\extensions\esbjerg_bindninger\api;

use app\inc\Controller;
use app\inc\Input;
use app\models\Database;

class Codes extends Controller
{

    private $codes;

    function __construct()
    {
        parent::__construct();
    }

    public function get_ref()
    {
        $varName = Input::get("vn");
        $json = Input::get("json");
        $this->codes = new \app\extensions\esbjerg_bindninger\models\Codes();

        return array("data" => $this->codes->getRefs($varName, null, $json));
    }

    public function get_field()
    {

        $varName = Input::get("vn");
        $json = Input::get("json");
        $this->codes = new \app\extensions\esbjerg_bindninger\models\Codes();

        return array("data" => $this->codes->getFields($varName, $json));
    }

}