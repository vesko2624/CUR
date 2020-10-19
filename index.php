<?php
set_time_limit(0);

function get_web_page( $url )
{
    $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

    $options = array(

        CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
        CURLOPT_POST           =>false,        //set to GET
        CURLOPT_USERAGENT      => $user_agent, //set user agent
        CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
        CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
        CURLOPT_RETURNTRANSFER => true,     // return web page
        CURLOPT_HEADER         => false,    // don't return headers
        CURLOPT_FOLLOWLOCATION => true,     // follow redirects
        CURLOPT_ENCODING       => "",       // handle all encodings
        CURLOPT_AUTOREFERER    => true,     // set referer on redirect
        CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
        CURLOPT_TIMEOUT        => 120,      // timeout on response
        CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
}

function get_cities()
{
    $page = get_web_page("https://en.wikipedia.org/wiki/Lists_of_schools_in_England")['content'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($page);
    libxml_clear_errors();
    $finder = new DomXPath($doc);
    $lis = $finder->query("//*[@id='mw-content-text']//li/a[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'list')]");
    $arr = [];
    foreach($lis as $i)
        array_push($arr, "https://en.wikipedia.org".$i->getAttribute("href"));
    return $arr;
}

function keyword_lookup($class){
    return "contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), translate('$class', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'))";
}
function get_schools($city_link, $classes = 0)
{
    $page = get_web_page($city_link)['content'];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($page);
    libxml_clear_errors();
    $finder = new DomXPath($doc);
    $keywordCheck = "";
    if(is_string($classes)) $classes = [$classes];
    if(is_array($classes))
    {
        $count = count($classes);
        $keywordCheck .= "and ";
        for($i = 0; $i < $count; ++$i)
        {
            $keywordCheck .= keyword_lookup($classes[$i]);
            if($i != $count - 1) $keywordCheck .= " or ";
        }
    }
    $h3 = $finder->query("//span[@class='mw-headline' $keywordCheck ]/../following-sibling::*[1]//li/a[1][not(contains(@href, 'redlink'))]");
    $arr = [];
    foreach($h3 as $i)
    {
        $href = "https://en.wikipedia.org".$i->getAttribute("href");
        if(!isset($arr[$href])){
            $arr[$href] = 1;
        }
    }
    return array_keys($arr);
}
function get_website($school_link)
{
    if($school_link == "") return [];
    $page = get_web_page($school_link)['content'];
    if($page == "") return [];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML($page);
    libxml_clear_errors();
    $finder = new DomXPath($doc);
    
    $th = $finder->query("//th[contains(translate(text(), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'website')]/following-sibling::*[1]//a");
    $arr = [];
    foreach($th as $i)
    {
        $href = $i->getAttribute("href");
        if(!isset($arr[$href])){
            $arr[$href] = 1;
        }
    }
    return array_keys($arr);
}



$cities = get_cities();
foreach($cities as $i)
{
    $schools = get_schools($i, "secondary");
    foreach($schools as $j)
    {
        $websites = get_website($j);
        foreach($websites as $k)
        {
            echo $k."</br>";
        }
    }
}
?>