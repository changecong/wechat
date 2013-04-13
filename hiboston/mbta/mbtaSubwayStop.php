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
        $stops = array();
        $lines = array();
        foreach($obj as $unit) {
            $station = $unit->station;
            $color = $station->line;
            $stopName = $station->stop_name;
            $distance = number_format($station->distance, 1);
            if($distance < 0.7 && !in_array($stopName, $stops)) {
                $pair[] = array("title"=>$stopName." (".$distance." mile)", "desc"=>$color, "pic"=>"http://changecong.com/wechat/hiboston/img/".$color.".jpg", "url"=>$mbtaurl);
                $stops[] = $stopName;
            }

            /*
             * @todo: handle when there is a transfer station. 
             */
      	}
    
        return $pair;
    }
}  // end class

?>