<?php


class Controller
{
    public function process()
    {
        mb_internal_encoding('UTF-8');
        
        require 'FchanFetcher.php';
        require 'TableExtractor.php';
        require 'DataProcessor.php';
        
        //Fetch HTML of https://4chan.org/f/
        $html = FchanFetcher::getIndex();
        
        //Extract the data from the HTML table
        $extractor = new TableExtractor($html);
        $data = $extractor->extractData();
        
        //Process and save data
        $processor = new DataProcessor();
        $processor->process($data);
    }
}
