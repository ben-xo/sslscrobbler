<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

require_once dirname(__FILE__) . '/SevenDigitalApi/SevenDigitalApi.php';
require_once dirname(__FILE__) . '/echonest/track.php';

class SevenDigital
{
    public function getBuyLink(SSLTrack $track)
    {
        $en_api_key = trim(file_get_contents('SevenDigital.key'));
        $en_url = "http://developer.echonest.com/api/v4/song/search?api_key=%s&format=json&results=1&artist=%s&title=%s&bucket=id:7digital&bucket=id:7digital&limit=true&bucket=tracks";
        $real_en_url = sprintf($en_url, $en_api_key, rawurlencode($track->getArtist()), rawurlencode($track->getTitle()));
        //var_dump($real_en_url);
        $en_result = file_get_contents( $real_en_url );
        
        $en_result = json_decode($en_result, true);
//        var_dump(
//            levenshtein(mb_strtolower($en_result['response']['songs'][0]['title']), mb_strtolower($track->getTitle())),
//            $en_result['response'],
//            $track->getTitle()
//        );
        
        if(levenshtein(mb_strtolower($en_result['response']['songs'][0]['title']), mb_strtolower($track->getTitle())) < 5)
        {
            foreach($en_result['response']['songs'][0]['tracks'] as $track)
            {
                if($track['catalog'] == '7digital')
                {
                    $id_parts = explode(':', $track['foreign_id']);
                    $seven_digital_id = $id_parts[2];
                    break;
                }
            }
        }
        if(!isset($seven_digital_id)) { return false; }
        
        $sd_url = 'http://api.7digital.com/1.2/track/details?trackid=%d&oauth_consumer_key=%s&country=GB';
        $real_sd_url = sprintf($sd_url, $seven_digital_id, 'musichackday');
        $sd_result = @file_get_contents($real_sd_url);
        if(empty($sd_result)) return '';
        
        //var_dump($sd_result);
        $xml = new DOMDocument();
        $xml->loadXML($sd_result);
        $xpath = new DOMXpath($xml);
        $urls = $xpath->query('//track/url/text()');
        /* @var $urls DomNodeList */
        return @$urls->item(0)->textContent;
        
//        $api = new SevenDigitalApi();
//        $api->OutputType = 'json';
//        $api->ConsumerId = 'musichackday';
//        var_dump($track->getArtist() . ' ' . $track->getTitle());
//        $results = $api->GetTracksByTitle($track->getArtist() . ' ' . $track->getTitle());
//        $resultsObj = json_decode($results);
//        var_dump($results);
//        if(isset($resultsObj->response->searchResults->searchResult[0]))
//        {
//            return $resultsObj->response->searchResults->searchResult[0]->release->url;
//        }
//        return false;
    }
    
    public function getQRCodeUrl($url)
    {
        return sprintf('http://chart.apis.google.com/chart?chs=250x250&cht=qr&chl=%s&choe=UTF-8', rawurlencode($url));
    }
    
    /**
     * usage:
		$short = make_bitly_url('http://davidwalsh.name','davidwalshblog','R_96acc320c5c423e4f5192e006ff24980','json');
		echo 'The short URL is:  '.$short; 
		
     * @param $url
     * @param $login
     * @param $appkey
     * @param $format
     * @param $version
     */
    public function make_bitly_url($url,$login,$appkey='fill me in from bit.ly',$format = 'xml',$version = '2.0.1') 
    { 
      //create the URL 
      $bitly = 'http://api.bit.ly/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$login.'&apiKey='.$appkey.'&format='.$format;
      //get the url 
      //could also use cURL here 
      $response = file_get_contents($bitly);
      
      //parse depending on desired format 
      if(strtolower($format) == 'json') 
      { 
        $json = @json_decode($response,true);
        return $json['results'][$url]['shortUrl'];
      } 
      else //xml 
      { 
        $xml = simplexml_load_string($response);
        return 'http://bit.ly/'.@$xml->results->nodeKeyVal->hash;
      } 
    } 
    
    public function shorten($url)
    {
        return make_bitly_url($utl, 'fill me in');
    }
 
}