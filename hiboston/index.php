<?php
/**
 * @file wechat/hiboston/index.php 
 */

//define your token
define("TOKEN", "hiboston");

$Obj = new Callback();
$Obj->responseMsg();



// mbta callback
class Callback
{
  public function responseMsg() {
    //get post data, May be due to the different environments
    $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

    //extract post data
    if (!empty($postStr)){
                
      $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
      $MsgType = $postObj->MsgType;

      $fromUsername = $postObj->FromUserName;
      $toUsername = $postObj->ToUserName;
      $keyword = trim($postObj->Content);
      $time = time();

         

      if(!empty( $keyword )) {
        if ($keyword == "Hello2BizUser") { // new user 
          $contentStr = "Hi Bostonian!";
          setPlantTextResponse($fromUsername, $toUsername, $time, $contentStr);
        } else {

          // pre process
          // split
          $keywords = splitStringToTwo($keyword);
              
          if (count($keywords) > 2) {
            $contentStr = "Wrong Format!\n[category]: [keywords]\nor\n[category]\ni.e.weather\nor\nweather: Boston";
            setPlantTextResponse($fromUsername, $toUsername, $time, $contentStr);
            exit;
          } else if (count($keywords) == 1) { // [category]

            // remove spaces
            $param = trim($keywords[0]);

            // weather
            if (strtolower($param) == "weather") {
              $weather = new weatherCondition();
              $contentStr = $weather->getWeather();
                    
              setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
                    
            } else {
              $contentStr = $this->getHelp();
              setPlantTextResponse($fromUsername, $toUsername, $time, $contentStr);
            }

          } else if (count($keywords) == 2) {  // [category]: [keyword]
            // remove spaces
            $param = array();
            $param[] = trim($keywords[0], " ");
            $param[] = trim($keywords[1], " ");
            
            // weather
            if (strtolower($param[0]) == "weather") {
              $weather = new weatherCondition();
              $contentStr = $weather->getWeather($param[1]);
                    
              setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
            } else {
              $contentStr = $this->getHelp();
              setPlantTextResponse($fromUsername, $toUsername, $time, $contentStr);
            }                
          }                 
        } 
      } else if ($MsgType == "location") {

        $Location_X = $postObj->Location_X;
        $Location_Y = $postObj->Location_Y;

        $stops = new mbtaSubwayStop;

        $stopArray = $stops->getStops($Location_X, $Location_Y);
        $stopNumber = count($stopArray);

        if ($stopNumber == 1) {
          $contentStr = "Sorry, there is no subway station around...";
          setPlantTextResponse($fromUsername, $toUsername, $time, $contentStr);
        } else {
                    
          setRichMediaResponse($fromUsername, $toUsername, $time, $stopArray);
        }
      }else {
        echo "input something";
      }

    }else {
      echo "";
      exit;
    }
  }



  // help
  private function getHelp() {
    $help = "1. For nearby subway stops, share your current location with me.\n2. Sent 'weather' to get Boston weather condition.\n3. Other info is coming soon...";
    return $help;
  }
}  // callback end

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
            if($station->distance < 0.7 && !in_array($stopName, $stops)) {
                $pair[] = array("title"=>$stopName, "desc"=>$color, "pic"=>"http://changecong.com/wechat/hiboston/img/".$color.".jpg", "url"=>$mbtaurl);
                $stops[] = $stopName;
            }

            /*
             * @todo: handle when there is a transfer station. 
             */
      	}
    
        return $pair;
    }
}

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
}

// utilities
function setRichMediaResponse($fromUsername, $toUsername, $createTime, $content)
{
    $textHeaderTpl = "<xml>
                              <ToUserName><![CDATA[%s]]></ToUserName>
                              <FromUserName><![CDATA[%s]]></FromUserName>
                              <CreateTime>%s</CreateTime>
                              <MsgType><![CDATA[news]]></MsgType>
                              <ArticleCount>%d</ArticleCount>
                              <Articles>";
    $textContentTpl = "<item>
                               <Title><![CDATA[%s]]></Title> 
                               <Description><![CDATA[%s]]></Description>
                               <PicUrl><![CDATA[%s]]></PicUrl>        
                               <Url><![CDATA[%s]]></Url>                       
                               </item>";
    $textFooterTpl = "</Articles>
                              <FuncFlag>1</FuncFlag>
                              </xml> ";

    $headerStr = sprintf($textHeaderTpl, $fromUsername, $toUsername, $createTime, count($content));
		
    foreach($content as $key=>$value) {
        $contentStr .= sprintf($textContentTpl, $value["title"], $value["desc"], $value["pic"], $value["url"]);
    }			     

    $footerStr = sprintf($textFooterTpl);
                
    echo $resultStr = $headerStr,$contentStr,$footerStr;                
}

function setPlantTextResponse($fromUsername, $toUsername, $createTime, $content)
{
    // text
    $textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[text]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                            </xml>";   

    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $createTime, $content);
    
    echo $resultStr; 
}

// "I am good" -> "I" "am good"
function splitStringToTwo($str)
{
  $strs = explode(":", $str);
  return $strs;
}

?>
