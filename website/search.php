<?php
    ini_set('memory_limit', '2048M');
    ini_set('max_execution_time', 300);
    include 'SpellCorrector.php';
    error_reporting(E_ALL ^ E_WARNING);

    function url($doc, $dict) {
       $val = $doc->og_url;
       return $val == "" ? $dict[extractID($doc->id)] : $val;
    }

    function extractID($id) {
      $prefix = "C:\\Users\\anind\\Downloads\\NBC_News-20180407T052036Z-001\\NBC_News\\HTML files\\HTML files\\";
      return substr($id, strlen($prefix));   
    }

    function spell_check($query) {
        $arr = explode(" ", $query);
        $result = array();
        foreach($arr as $word)
           array_push($result, SpellCorrector::correct($word));
        $result_string = implode(" ", $result);
        return $result_string;
    }

    function bold($query, $snippet) {
        $snip_arr = preg_split('/([-.,;:]|\s+)/i', $snippet, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $query_arr = explode(" ", $query);
        for($i=0; $i<count($snip_arr); $i++) {
            for($j=0; $j<count($query_arr); $j++) {
                $len = strlen($query_arr[$j]);
                $index = strpos(strtolower($snip_arr[$i]), strtolower($query_arr[$j]));
                
                if($index !== false) {;
                    $snip_arr[$i] = substr($snip_arr[$i], 0, $index)
                                    ."<b>".substr($snip_arr[$i], $index, $len)."</b>"
                                    .substr($snip_arr[$i], $index+$len);
                }
            }
        }
        
        return implode("", $snip_arr);
    }

    function full_query($text, $query) {
        return strpos($text, $query);
    }

    function all_terms($text, $query) {
        $sentences = explode(". ", $text);
        $query_arr = explode(" ", $query);
        
        foreach($sentences as $line) {
            $positions = [];
            foreach($query_arr as $word) {
                $exists = full_query($line, $word);
                if($exists) {
                    $positions[$word] = $exists;
                }
            }
            if(count($positions) == count($query_arr)) {
                asort($positions);
                return ['line'=> $line, 'positions' => $positions];
            }
        }
        
        return false;
    }

    function first_term($text, $query) {
         $sentences = explode(". ", $text);
         $query_arr = explode(" ", $query);
        
          foreach($sentences as $line) {
              foreach($query_arr as $word) {
                 $exists = full_query($line, $word);
                 if($exists) {
                     return $line;
                }
             }
          }
        return false;
    }

    function generate_snippet($line, $positions) {
        $first_index = min($positions);
        $last_index = max($positions) + strlen(array_search(max($positions), $positions));
        
        $dist = $last_index - $first_index;
        if($dist < 160) {
            //all words fit into snippet and nothing needs to be truncated in between.
            //we need to add extra characters on left.
            $padding = (160 - $dist) / 2;
            return "...".substr($line, max(0, $first_index - $padding+1), 160)."...";
        } else if($dist == 160) {
            return "...".substr($line, $first_index, 160)."...";
        } else {
            //length>160. So we need to show all words and also truncate some stuff in between.
            $truncate_length = $dist - 160;
            $noWords = count($positions);
            $truncate_per_word = $truncate_length/$noWords;
            
            $snippet_arr = [];
            
            foreach($positions as $key=>$value) {
                array_push($snippet_arr, "... ");
                array_push($snippet_arr, $key." ");
                
                $start_after_truncation = $value+strlen($key)+$truncate_per_word;
                array_push($snippet_arr, substr($line, $start_after_truncation, 40));
            }
            
            
            array_push($snippet_arr, "...");
            return implode("", $snippet_arr);
        }    
    }

    function snippet($url, $query) {
        $d = new DOMDocument;
        $d->loadHTML(file_get_contents($url));
        $body = $d->getElementsByTagName('body')->item(0);
        $snippet = "";
        foreach ($body->childNodes as $child){
            $html = strip_tags($child->C14N());
            $text = strtolower($html);
            //1. find with all terms together
            $pos = full_query($text, $query);
            if($pos) {
                $snippet = "...".substr($html, max(0,$pos-80), 160)."...";
                break;
            } else {
                //2. find sentence with all terms
               
                $list_pos = all_terms($text, $query);
                
                if($list_pos !== false) {
                    $line = $list_pos['line'];
                    $positions = $list_pos['positions'];
                    $snippet = generate_snippet($line, $positions);
                    return bold($query, trim($snippet));
                } else {
                    //3. first sentence with one query term.
                    $sentence = first_term($text, $query);
                    if($sentence !== false) {
                        $snippet = $sentence;
                    } else {
                        //4. no snippet.
                        $snippet = "N/A";
                    }
                }
            }   
        }
        return bold($query, trim($snippet));
    }
    
    function main() {
        header('Content-Type: application/json; charset=utf-8');
        $limit = 10;
        $orig_query = isset($_REQUEST['q']) ? $_REQUEST['q'] : false;
        $algo = $_GET['algo'];
        $results = false;
        
        if ($orig_query) {
            require_once('solr-php-client/Apache/Solr/Service.php');
            $solr = new Apache_Solr_Service('localhost', 8983, '/solr/anindya/');
            if (get_magic_quotes_gpc() == 1) {
                $orig_query = stripslashes($orig_query);
            }
            
            $suggested = spell_check($orig_query);
            
            $query = $_GET['force'] == 'false' ? $suggested : $orig_query;
            
            try {
                if($algo == "lucene") {
                    $results = $solr->search($query, 0, $limit);
                } else {
                    $addParams = array('sort' => 'pageRankFile desc');
                    $results = $solr->search($query, 0, $limit, $addParams);
                }
            } catch (Exception $e) {
                die("Error");
            }
        }
        
        if ($results) {
            if (($handle = fopen("UrlToHtml_NBCNews.csv", "r")) !== FALSE) {
                $dict = [];
                while (($data = fgetcsv($handle, ",")) !== FALSE) {
                    $dict[$data[0]] = $data[1];
                }
                fclose($handle);
            }

            $total = (int) $results->response->numFound;
            $data = array();

            foreach ($results->response->docs as $doc) {  
                $snippet = snippet($doc->id, strtolower($query));
                //echo $snippet."\n\n";
                $snippet = preg_replace('!\s+!', ' ', $snippet);
                $snippet = preg_replace('/[[:^print:]]/', '', $snippet);

                $reqd = [ 'id' => $doc->id, 
                         'title' => $doc->title, 
                         'url' => url($doc, $dict), 
                         'desc' => $doc->og_description,
                         'snippet' => $snippet];
                array_push($data, $reqd);
            }
            
            $result = ['start' =>  min(1, $total), 
                       'end' => min($limit, $total), 
                       'total' => $total, 
                       'data' => $data];
            
            
            //spell-check for suggested:
            //1. if words are different:
            if(strtolower($query) !== strtolower($orig_query))
                $result['suggested'] = $query;
            else if ($_GET['force'] == 'true')
                $result['suggested'] = $suggested;
            else 
               $result['suggested'] = "";
            
            
           // print_r($result);
            $json = json_encode($result);
           // echo "hi: ".$json;
            
           print_r(json_encode($result));
        }
    }

   main();
?>
