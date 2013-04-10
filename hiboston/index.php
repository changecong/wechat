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

            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $time = time();

            // rich media
            // header
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
                               </item>";
            $textFooterTpl = "</Articles>
                              <FuncFlag>1</FuncFlag>
                              </xml> ";
            // text
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
                }else {
                    $contentStr = $this->getSubway($keyword);
                }
                
                if ($contentStr == "") {
		    $contentStr = $this->getHelp();
                }

                $msgType = "text";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
              
                echo $resultStr;
            
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
			    
                    $headerStr = sprintf($textHeaderTpl, $fromUsername, $toUsername, $time, $stopNumber);		
                    foreach($stopArray as $key=>$value) {
                        $contentStr .= sprintf($textContentTpl, $value["title"], $value["line"], $value["pic"]);
                    }			     
                    $footerStr = sprintf($textFooterTpl);
                
                    echo $resultStr = $headerStr,$contentStr,$footerStr;                
                }
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
        if(empty($file)) {
            return "";
        }
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
        $pair = array();
        $pair[] = array("title"=>"MBTA subway stops nearby", "pic"=>"http://changecong.com/wechat/hiboston/img/mbta.jpg");
        $stops = array();
        $lines = array();
        foreach($obj as $unit) {
            $station = $unit->station;
            $color = $station->line;
            $stopName = $station->stop_name;
            if($station->distance < 0.7 && !in_array($stopName, $stops)) {
                $pair[] = array("title"=>$stopName, "line"=>$color, "pic"=>"http://changecong.com/wechat/hiboston/img/".$color.".jpg");
                $stops[] = $stopName;
            }

            /*
             * @todo: handle when there is a transfer station. 
             */
      	}
    
        // unique
        // $pairUnique = array_unique($pair);

        // return $pairUnique;
        return $pair;
    }

    // help
    function getHelp() {
        $help = "1. For nearby subway stops, share your current location with me.\n2. Other info is coming soon...";
        return $help;
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
