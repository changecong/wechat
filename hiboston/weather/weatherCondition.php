<?php
/*
 * @file  wechat/hiboston/weather/weatherCondition.php
 * @brief a class used to get world wild weather conditions
 */

// a class for weather condition
class weatherCondition  // only return the weather condition for Boston
{
    public function getWeather($location) {
        if(empty($location)) {
            $WOEID = "2367105"; // the code of Boston
        } else {
            $geoObj = new geoUtilities();
            $WOEID = $geoObj->getWoeidFromName($location);
        }
        $array = $this->decodeYahooAPI($WOEID);
        return $array;
    }

    private function decodeYahooAPI($WOEID) {
        $url = "http://weather.yahooapis.com/forecastrss?w=".$WOEID;
        
        // read xml
        $weather_feed = file_get_contents($url);
        if(!$weather_feed) {
            return "Weather info is unavilable now...";
        }

        $weather = simplexml_load_string($weather_feed);

        $channel_yweather = $weather->channel->children("http://xml.weather.yahoo.com/ns/rss/1.0");
        
        // channel
        foreach($channel_yweather as $x => $channel_item) {
            foreach($channel_item->attributes() as $k => $attr) {
		$yw_channel[$x][$k] = $attr;
            }
        }


        // item
        $item_yweather = $weather->channel->item->children("http://xml.weather.yahoo.com/ns/rss/1.0");
        $i=0; 
        foreach($item_yweather as $x => $yw_item) {
            foreach($yw_item->attributes() as $k => $attr) {
                if($k == 'day') {
                    $day = $attr;
                }
                if($x == 'forecast') { 
                    $yw_forecast[$x][$i][$k] = $attr;	
                } else { 
                    $yw_forecast[$x][$k] = $attr; 
                }
            }
            $i += 1;
        }
        
        $location = $yw_channel["location"];
        $condition = $yw_forecast["condition"];
        $forecast = $yw_forecast["forecast"];
        $today = $forecast[1];
        $tomorrow = $forecast[2];
        $code = sprintf("%d", $condition["code"][0]);

        $conditionImg = $this->getFromConditionCode($code);
        $picUrl = "http://changecong.com/wechat/hiboston/img/weather/".$conditionImg.".jpg";
        // $picUrl = "http://changecong.com/wechat/hiboston/img/weather/".$code.".jpg";
        $array = array(
            array("title"=>$location["city"][0].", ".$location["region"][0].", ".$location["country"][0], "pic"=>$picUrl, "url"=>$picUrl),
            array("title"=>"Current condition:\n".$condition["text"][0]." ".$condition["temp"][0]."F"),
            array("title"=>"Today: ".$today["text"][0]."\nhigh: ".$today["high"][0]."F low: ".$today["low"][0]."F"),
            array("title"=>"Tomorrow: ".$tomorrow["text"][0]."\nhigh: ".$tomorrow["high"][0]."F low: ".$tomorrow["low"][0]."F")
            );

        return $array;

    }

    private function getFromConditionCode($code) {
        include "weatherConditionTable.php";
        return $weatherConditionTable[$code];
    }

}


?>