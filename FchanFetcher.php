<?php

/**
 * Class downloading the HTML page of the upload board index
 */
class FchanFetcher
{
    private const INDEX_URL = 'https://boards.4chan.org/f/';
    
    /**
     * Fetches and returns the HTML of the index webpage of the /f/ board
     * @return string HTML of the index webpage
     */
    public static function getIndex(): string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::INDEX_URL);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('User-Agent: Shady'));
    
        $response = curl_exec($curl);
        //$response = mb_convert_encoding($response, 'HTML-ENTITIES', "UTF-8");
        return $response;
    }
}
