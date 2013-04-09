<?php
/**
 * @file wechat/hiboston/index.php 
 */

//define your token
define("TOKEN", "hiboston");

$mbtaObj = new mbtaCallback();
$mbtaObj->responseMsg();

// mbta callback
class mbtaCallback
{
    public function responseMsg() {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
        if (!empty($postStr)){
                
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $MsgType = $postObj->MsgType;
            if ($MsgType == "location") {
                $Location_X = $postObj->Location_X;
                $Location_Y = $postObj->Location_Y;  
                $stopNameArray = $this->getStation($Location_X, $Location_Y);
                $stopNumber = count($stopNameArray);
            }
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();
            $textTpl = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							<FuncFlag>0</FuncFlag>
							</xml>";             
            if(!empty( $keyword ))
            {
                if ($keyword == "Hello2BizUser") { 
                    $contentStr = "Hi Bostonian!";
                } 
                else {
                    $contentStr = $this->getSubway($keyword);
                }

                $msgType = "text";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else if ($MsgType == "location") {
                $contentStr = "There is(are) ".$stopNumber." station(s) nearby (less than 1 mile).\n";
                foreach ($stopNameArray as $stopName) {
                    $contentStr .= "* ".$stopName."\n";
                }

                $msgType = "text";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;                
            }else {
                echo "input something";
            }

        }else {
            echo "";
            exit;
        }
    }

    // get mbta
    function getSubway($color) {
        $url = "http://developer.mbta.com/lib/rthr/".$color.".json";
        $file = file_get_contents($url);
        $obj = json_decode($file);

        // decode
        $TripList = $obj->TripList;
        $Line = $TripList->Line;

        return $Line;
    }

    function getStation($location_x, $location_y) {
        $url = "http://mbta-api.heroku.com/mapper/find_closest_stations.json?lat=".$location_x."&lon=".$location_y;
        $file = file_get_contents($url);
        $obj = json_decode($file);

        // decode
        $stopNames = array();
        foreach($obj as $unit) {
            $station = $unit->station;
            if($station->distance < 1.0) {
                $stopNames[] = $station->stop_name;
            }
        }

        /* $stopNames[] = "aaaa"; */
        /* $stopNames[] = "bbbb"; */
        /* $stopNames[] = "eeee"; */
        
        // unique
        $stopNamesUnique = array_unique($stopNames);

        return $stopNamesUnique;
    }
}  // mbta callback end


/* unuseful
   class wechatCallbackapiTest
   {
   public function valid()
   {
   $echoStr = $_GET["echostr"];

   //valid signature , option
   if($this->checkSignature()){
   echo $echoStr;
   exit;
   }
   }

   public function responseMsg()
   {
   //get post data, May be due to the different environments
   $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

   //extract post data
   if (!empty($postStr)){
                
   $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
   $fromUsername = $postObj->FromUserName;
   $toUsername = $postObj->ToUserName;
   $keyword = trim($postObj->Content);
   $time = time();
   $textTpl = "<xml>
   <ToUserName><![CDATA[%s]]></ToUserName>
   <FromUserName><![CDATA[%s]]></FromUserName>
   <CreateTime>%s</CreateTime>
   <MsgType><![CDATA[%s]]></MsgType>
   <Content><![CDATA[%s]]></Content>
   <FuncFlag>0</FuncFlag>
   </xml>";             
   if(!empty( $keyword ))
   {
   $msgType = "text";
   // $contentStr = "You are a colorfull piggy!";
   $contentStr = getSubway($keyword);
   // $contentStr = $keyword;
   $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
   echo $resultStr;
   }else{
   echo "Input something...";
   }

   }else {
   echo "";
   exit;
   }
   }
		
   private function checkSignature()
   {
   $signature = $_GET["signature"];
   $timestamp = $_GET["timestamp"];
   $nonce = $_GET["nonce"];	
        		
   $token = TOKEN;
   $tmpArr = array($token, $timestamp, $nonce);
   sort($tmpArr);
   $tmpStr = implode( $tmpArr );
   $tmpStr = sha1( $tmpStr );
		
   if( $tmpStr == $signature ){
   return true;
   }else{
   return false;
   }
   }
   } 
*/

?>
