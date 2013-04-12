<?php
/*
 * @file  wechat/hiboston/geoUtilities
 * @brief use cities' name to get ther corresponding woeid
 */

// class
class geoUtilities
{
    public function getWoeidFromName($location) {

        $woeid = $this->name2Woeid($location);
        return $woeid;

    } 
  
    private function name2Woeid($location) {
  
        // yahoo app id
        $appid = "GrUp8FnV34H7F4v98cRFVehsrsbfj0oV1Rf0dj1W804167.0oigtAH1cLtnqkUPYiV7incDXUoTbUHfAWsuzhJy5Do4_J9Q-";
        $url = "http://where.yahooapis.com/v1/places.q('".rawurlencode($location)."')?appid=[".$appid."]";

        // decode xml
        $geo_feed = file_get_contents($url);

        $geo = simplexml_load_string($geo_feed);

        $woeid = $geo->place->woeid;

        return $woeid;
    
    }
}  // end class

?>