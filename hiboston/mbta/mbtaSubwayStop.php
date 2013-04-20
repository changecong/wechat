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

        $distance =  $this->regionDecide($location_x, $location_y);

        $pair = array();
        $pair[] = array("title"=>"MBTA subway stops within ".$distance." mile", "pic"=>"http://changecong.com/wechat/hiboston/img/mbta/mbta.jpg");
        $stops = array();  // "name"=>"", "color"=>""
        $lines = array();

        foreach($obj as $unit) {
            $station = $unit->station;  // get station

            $color = $station->line;  // get the line color
            $stopName = $station->stop_name;  // get the stop's name   
           

            // if the stop is within 0.7 miles
            // and the combination of stopname and line number have appearred
            if($station->distance < $distance && 
               !in_array(array("name"=>$stopName, "color"=>$color), $stops)) {
                    
                $stops[] = array("name"=>$stopName, "color"=>$color);  // store it      
            }
        }
       

        // assume 2 stations
        $newStops = array();
        $temp = current($stops);
        $end = end($stops);
        reset($stops);
        while($temp != $end && !is_null($temp)) {
            $next = next($stops);  // get the next
            if($temp["name"] == $next["name"]) {  // same with the next
                $newStops[] = array("name"=>$temp["name"], "color"=>$temp["color"].$next["color"]);
                // new temp, skip the next
                $temp = next($stops);
            } else { // different with the next
                $newStops[] = array("name"=>$temp["name"], "color"=>$temp["color"]);
                $temp = current($stops);
            }
            if($temp == $end) {
                $newStops[] = array("name"=>$temp["name"], "color"=>$temp["color"]);
            }
        }

        foreach($newStops as $item) {
                    
            $pair[] = array("title"=>$item["name"], "desc"=>$itme["color"], "pic"=>"http://changecong.com/wechat/hiboston/img/mbta/".$item["color"].".png", "url"=>$mbtaurl);
        }
    
        return $pair;
    }

    /*
     * @ todo create a picture basic on the information about the line color, and dierection
     */
    private function drawDirectionPic() {

    }

    /*
     * @brief a function used to decide where the location is, and the range of subway search
     */
    private function regionDecide($x, $y) {
      // Boston Downtown
      // draw and
      $distance = 0.0;
      if($x > 42.347920 && $y > -71.071636 && 
         $x < 42.367423 && $y < -71.051001) {
        $distance = 0.4;
      } else {
        $distance = 0.7;
      }

      return $distance;
    }
}  // end class

?>