<?php

/*
 * @file   wechat/hiboston/utils/msgUtilities.php
 * @brief  commen used utilities
 * @detail -# setRichMediaResponse: sent a rich media message
 *         -# setPlainTextResponse: sent a plain text message
 *         -# splitStringToTwo: split our keyword by ':', to match our keywords rule.
 */


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

<<<<<<< HEAD
    $headerStr = sprintf($textHeaderTpl, $fromUsername, $toUsername, $createTime, count($content));

    $url = appSite();
=======
    if (count($content) > 10) {
        $count = 10;
    } else {
        $count = count($content);
    }
    $headerStr = sprintf($textHeaderTpl, $fromUsername, $toUsername, $createTime, $count);
		
>>>>>>> 2ce49ad940be502c19443bff6460cf56364f1786
    foreach($content as $key=>$value) {
        $contentStr .= sprintf($textContentTpl, $value["title"], $value["desc"], $value["pic"], $url);
    }			     

    $footerStr = sprintf($textFooterTpl);
                
    echo $resultStr = $headerStr,$contentStr,$footerStr;                
}

function setPlainTextResponse($fromUsername, $toUsername, $createTime, $content)
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

// 
function appSite()
{
  $url = "http://goo.gl/c0vNk";  // short url
  return $url;
}
?>