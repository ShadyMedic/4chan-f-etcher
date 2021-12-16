<?php

/**
 * Class extracting data from the downloaded HTML table and sorting them into associative array
 */
class TableExtractor
{
    private $input;
    
    public function __construct(string $inputHtml)
    {
        $this->input = $inputHtml;
    }
    
    public function extractData(): array
    {
        $startPos = strpos($this->input, '<table class="flashListing">');
        $endPos = strpos(substr($this->input, $startPos), '</table>');
        $table = substr($this->input, $startPos, $endPos);
        
        error_reporting(E_ALL & ~E_WARNING); //Turn of warnings for the cases of unescaped '&' in the document
        $dom = new DOMDocument();
        $dom->loadHTML($table);
        $cells = $dom->getElementsByTagName('td');
        error_reporting(E_ALL);
        
        $headerGone = false;
        $i = 0;
        $j = 0;
        $data = array();
        $header = array();
        foreach ($cells as $cell) {
            if (!$headerGone) {
                if (is_numeric($cell->textContent)) {
                    $headerGone = true;
                } else {
                    $txt = strtolower(trim($cell->textContent));
                    $header[$j] = (mb_strlen($txt) !== 0) ? $txt : (($j === 8) ? 'openlink' : 'embedlink');
                    $j++;
                    continue;
                }
            }
            
            $data[$i / $j][$header[$i % $j]] = trim($cell->textContent);
            
            if ($header[$i % $j] === 'file') {
                //Extract download link and full file name
                $a = new SimpleXMLElement($dom->saveHTML($cell->childNodes[1]));
                $data[$i / $j]['file'] = $a['title'];
                $data[$i / $j]['download'] = str_replace('\'', '%26%23039%3B', substr($a['href'], 2));
            }
    
            if ($header[$i % $j] === 'subject') {
                //Extract full subject
                $a = new SimpleXMLElement($dom->saveHTML($cell->childNodes[0]));
                $data[$i / $j]['subject'] = $a['title'];
            }
            
            if ($header[$i % $j] === 'openlink') {
                //Extract source (reply) link
                $a = new SimpleXMLElement($dom->saveHTML($cell->childNodes[1]));
                $data[$i / $j]['source'] = $a['href'];
            }
            $i++;
        }
        return $data;
    }
}