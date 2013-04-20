<?php
/**
 * @file wechat/hiboston/index.php 
 */

// includes
include "./utils/geoUtilities.php";  // geo
include "./utils/msgUtilities.php";  // commen used
include "./weather/weatherCondition.php";  // weather
include "./mbta/mbtaSubwayStop.php";  // mbta


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
                    setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                } else {

                    // pre process
                    // split
                    $keywords = splitStringToTwo($keyword);
              
                    if (count($keywords) > 2) {
                        $contentStr = $this->getHelp();
                        setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
                        exit;
                    } else if (count($keywords) == 1) { // [category]

                        // remove spaces
                        $param = trim($keywords[0]);

                        // weather
                        if (strtolower($param) == "weather" || strtolower($param) == "w") {
                            $weather = new weatherCondition();
                            $contentStr = $weather->getWeather("");
                                               
                            setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
                            
                        } else if (strtolower($param) == "help" || strtolower($param) == "h"){
                            $contentStr = $this->getHelp();
                            setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
                        }else {
                            $contentStr = "Uncorrect syntax, sent 'help' or 'h' for how to use.";
                            setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                        }

                    } else if (count($keywords) == 2) {  // [category]: [keyword]
                        // remove spaces
                        $param = array();
                        $param[] = trim($keywords[0], " ");
                        $param[] = trim($keywords[1], " ");
            
                        // weather
                        if (strtolower($param[0]) == "weather" || strtolower($param[0]) == "w") {
                            $weather = new weatherCondition();
                            $contentStr = $weather->getWeather($param[1]);
                            if ( $contentStr == "error" ) {
                                $contentStr = "No valid data, please check your city's name.";
                                setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
                            } else {
                                setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
                            }
                        } else {
                            $contentStr = $this->getHelp();
                            setRichMediaResponse($fromUsername, $toUsername, $time, $contentStr);
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
                    $contentStr = "Sorry, there is no subway station nearby...\nOnly 'Orange', 'Blue, 'Red' lines supported.";
                    setPlainTextResponse($fromUsername, $toUsername, $time, $contentStr);
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
        $help = array(
                      array("title"=>"How To Use", "pic"=>"http://changecong.com/wechat/hiboston/img/help/help.jpg"),
            array("title"=>"Share your location to find nearby subway stops (Orange, Red, Blue line spported).", "desc"=>"help:subway", "pic"=>"http://changecong.com/wechat/hiboston/img/help/location.png"),
            array("title"=>"Use 'weather' or 'w' for weather condition:\n'weather' (Boston default)\n'weather':[city name]\n'w:[city name]'"),
            array("title"=>"Case/Space insensitive:\n'weather:NeW york'\n'W: Xian China'\nFor more accurate result, specify state and country, like:\nRochester, NY\nRochester, MN"),
            array("title"=>"Click here to help site", "pic"=>"http://changecong.com/wechat/hiboston/img/help/website.png"),
            );

        // @todo add help site.
        return $help;
    }
}  // callback end




?>
