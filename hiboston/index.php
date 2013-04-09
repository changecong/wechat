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
            }else{
                echo "Input something...";
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
