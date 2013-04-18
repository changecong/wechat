<?php
/*
 * @file  wechat/hiboston/mbta/mbtaSubwayStop.php
 * @brief use MBTA apis to get information about stops
 */

// mbta
class mbtaSubwayStop
{
    public function getStops($location_x, $location_y) {
        $array = $this->getStation($location_x, $location_y);
        return $array;
    }
    
    private function getStation($location_x, $location_y) {
        $url = "http://mbta-api.heroku.com/mapper/find_closest_stations.json?lat=".$location_x."&lon=".$location_y;
        $file = file_get_contents($url);
        $obj = json_decode($file);

        $mbtaurl = "http://mobile.usablenet.com/mt/www.mbta.com/?un_jtt_v_schedule_choice=subway";
        // decode
        $pair = array();
        $pair[] = array("title"=>"MBTA subway stops nearby", "pic"=>"http://changecong.com/wechat/hiboston/img/mbta.jpg");
        $stops = array();  // "name"=>"", "color"=>""
        $lines = array();

        foreach($obj as $unit) {
            $station = $unit->station;  // get station

            $color = $station->line;  // get the line color
            $stopName = $station->stop_name;  // get the stop's name
    
           

            // if the stop is within 0.7 miles
            // and the combination of stopname and line number have appearred
            if($station->distance < 0.7 && 
               !in_array(array("name"=>$stopName, "color"=>$color), $stops)) {
                    
                $stops[] = array("name"=>$stopName, "color"=>$color);  // store it      
            }
        }
       
        // assume only have a transport station for two lines
        $stopCurrent = current($stops);
        $stopEnd = end($stops);
        reset($stops);
        $newStops = array();
        while ($stopCurrent != $stopEnd) {
            $stopCurrent = current($stops);
            $stopNext = next($stops);
            if($stopCurrent["name"] == $stopNext["name"]) {
                $newStops[] = array("name"=>$stopCurrent["name"], "color"=>$stopCurrent["color"].$stopNext["color"]);
                // skip the next
                next($stops);
            } else {
                $newStops[] = array("name"=>$stopCurrent["name"], "color"=>$stopCurrent["color"]);
            }
        }


        foreach($newStops as $item) {
                    
            $pair[] = array("title"=>$item["name"], "desc"=>$itme["color"], "pic"=>"http://changecong.com/wechat/hiboston/img/".$item["color"].".jpg", "url"=>$mbtaurl);
        }
    
        return $pair;
    }

    /*
     * @ todo create a picture basic on the information about the line color, and dierection
     */
    private function drawDirectionPic() {

    }
}  // end class

?>