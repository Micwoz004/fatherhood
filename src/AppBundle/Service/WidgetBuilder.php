<?php
/**
 * Created by PhpStorm.
 * User: michal.wozniak
 * Date: 2018-01-15
 * Time: 14:48
 */

namespace AppBundle\Service;

class WidgetBuilder {
    
    public function dbTableRows(Array $db_set)
    {
        $rows = '';
        
        foreach($db_set as $key => $row) {
            $rows .= '<tr><td>'.($key+1).'.</td><td>' . implode('</td><td>', $row) . '</td></tr>';
        }

        return $rows;
    }

    public function dbTableThead(Array $db_schema) : string
    {
        $columns = '';

        foreach($db_schema as $key => $row) {
            $columns = '<tr><th>Lp.</th><th>UID</th><th>Nr sprawy</th><th>LAB</th><th>Rola w sprawie</th><th>Nr próbki</th><th>'.implode('</th><th>', $row).'</th><th>Uwagi</th></tr>';
        }

        return $columns;
    }

}