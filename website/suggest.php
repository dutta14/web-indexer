<?php
    ini_set('memory_limit', '2048M');
    include 'SpellCorrector.php';
    $arr = explode(' ', $_GET['q']);
    header('Content-Type: application/json; charset=utf-8');
    $output = [];

    for($i=0; $i<count($arr)-1; $i++) {
        array_push($output, SpellCorrector::correct($arr[$i]));
    }

    $word = $arr[count($arr)-1];
    $curl = curl_init('http://localhost:8983/solr/anindya/suggest?q='.$word);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $val =  curl_exec($curl);
    curl_close($curl);
    
    $result = [
        'prefix' => implode(" ",$output),
        'suggest' => json_decode($val)
    ];
    print_r(json_encode($result));
?>