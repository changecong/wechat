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

         

            if(!empty( $keyword ))
            {
	
                if ($keyword == "Hello2BizUser") { 
                    $contentStr = "Hi Bostonian!";
                    setPlantTextResponse($fromUsername, $toUsername, $time, $constentStr);
                } else if (strtolower($keyword) == "weather") {
                    $weather = new weatherCondition();
                    $content = $weather->getWeather();
                    
                    setRichMediaResponse($fromUsername, $toUsername, $time, $content);
                    
                } else {
		    $contentStr = $this->getHelp();
                    die("shit");
                    setPlantTextResponse($fromUsername, $toUsername, $time, $constentStr);
                }
              
            
            } else if ($MsgType == "location") {

                $Location_X = $postObj->Location_X;
                $Location_Y = $postObj->Location_Y;  
                $stopArray = $this->getStation($Location_X, $Location_Y);
                $stopNumber = count($stopArray);

                if ($stopNumber == 1) {
                    $contentStr = "Sorry, there is no subway station around...";
                    $msgType = "text";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;  
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

    function getStation($location_x, $location_y) {
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

    // help
    function getHelp() {
        $help = "1. For nearby subway stops, share your current location with me.\n2. Other info is coming soon...";
        return $help;
    }
}  // mbta callback end

// a class for weather condition
class weatherCondition  // only return the weather condition for Boston
{
    public function getWeather() {
        $WOEID = "2367105"; // the code of Boston
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

        foreach($item_yweather as $x => $yw_item) {
            foreach($yw_item->attributes() as $k => $attr) {
		if($k == 'day') {
                    $day = $attr;
                }
		if($x == 'forecast') { 
                    $yw_forecast[$x][$day . ''][$k] = $attr;	
                } else { 
                    $yw_forecast[$x][$k] = $attr; 
                }
            }
        }
        
        $location = $yw_channel["location"];
        $condition = $yw_forecast["condition"];
        $forecast = $yw_forecast["forecast"];
        $today = $forecast[date("D")];
        $tomorrow = $forecast[date("D",mktime()+86400)];
        $code = $condition["code"][0];

        $conditionImg = $this->getFromConditionCode($code);
        $picUrl = "http://changecong.com/wechat/hiboston/img/weather/".$conditionImg.".jpg";

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

// utilities
function setRichMediaResponse($fromUsername, $toUsername, $createTime, $constent)
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

    $headerStr = sprintf($textHeaderTpl, $fromUsername, $toUsername, $createTime, count($constent));
		
    foreach($constent as $key=>$value) {
        $contentStr .= sprintf($textContentTpl, $value["title"], $value["desc"], $value["pic"], $value["url"]);
    }			     

    $footerStr = sprintf($textFooterTpl);
                
    echo $resultStr = $headerStr,$contentStr,$footerStr;                
}

function setPlantTextResponse($fromUsername, $toUsername, $createTime, $constent)
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

    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $createTime, $contentStr);
    
    echo $resultStr; 
}

?>
