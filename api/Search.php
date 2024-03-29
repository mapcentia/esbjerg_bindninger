<?php
/**
 * @author     Martin Høgh <mh@mapcentia.com>
 * @copyright  2013-2022 MapCentia ApS
 * @license    http://www.gnu.org/licenses/#AGPL  GNU AFFERO GENERAL PUBLIC LICENSE 3
 *
 */

namespace app\extensions\esbjerg_bindninger\api;

use app\inc\Controller;
use app\inc\Input;
use app\extensions\esbjerg_bindninger\models\Search as SearchModel;


class Search extends Controller
{
    const SCHEMA = "kommuneplan22";

    function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array<mixed>
     */
    public function get_index(): array
    {
        $search = new SearchModel();
        return array("data" => $search->go(Input::getPath()->part(5)));
    }
}