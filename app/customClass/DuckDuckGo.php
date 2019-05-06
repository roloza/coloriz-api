<?php

namespace App\CustomClass;

class DuckDuckGo {

    private $query;
    private $vqd;

    public function __construct($query) {
        $this->query = $query;
    }

    public function getImages() {
        $this->vqd = $this->getDuckVqdParam();
        return $this->getDuckImages();
    }

    private function getDuckVqdParam() {
        $vqd = null;
        $url = 'https://duckduckgo.com/?t=hg&iar=images&iax=images&ia=images&q='.urlencode($this->query);
        $content = $this->Download($url);
        preg_match_all("/vqd='(.*?)'/", $content, $matches, PREG_PATTERN_ORDER);
        if (sizeof($matches) == 2) {
            $vqd = current($matches[1]);
        }
        return $vqd;
    }

    private function Download($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec ($ch);
        $err = curl_error($ch);  //if you need
        curl_close ($ch);
        return $response;
    }

    private function getDuckImages() {
        $listImages = [];
        $images = [];
        if ($this->vqd === null) {
            return $images;
        }
        $url = 'https://duckduckgo.com/i.js?l=fr-fr&o=json&q='.$this->query.'&vqd='.$this->vqd.'&f=,,,&p=1';
        $content = json_decode($this->Download($url));
        foreach($content->results as $k => $result) {
            $images[] = $result->thumbnail;
        }
        return $images;
    }

}