<?php

namespace App\Http\Controllers;

use App\SearchImages;
use Illuminate\Http\Request;
use Storage;

class SearchImagesController extends Controller
{

    public function index(Request $request)
    {
        $results = [];
        $params = $request->all();
        $type = isset($params['type']) ? $params['type'] : '';
        switch($type) {
            case "last-queries":
                $results = $this->getLastQueries();
                break;
            case "best-queries":
                $results = $this->getBestQueries();
                break;
        }
        
        return response()->json([
            'state'     => 'success',
            'results'   => $results
        ]);
    }

    private function getLastQueries() {
        $results = [];
        $keywords = SearchImages::select(['query'])->orderBy('updated_at', 'DESC')->groupBy('query')->take(3)->get();
        foreach ($keywords as $keyword) {
            $results[] = SearchImages::where('query', $keyword->query)->first();
        }
        return $results;
    }

    private function getBestQueries() {
        $results = [];
        $keywords = SearchImages::select(['query'])->orderBy('count', 'DESC')->groupBy('query')->take(3)->get();
        foreach ($keywords as $keyword) {
            $results[] = SearchImages::where('query', $keyword->query)->first();
        }
        return $results;
    }

    public function store(Request $request)
    {
        $datas = [];
        $params = $request->all();
        $query = isset($params['q']) ? $params['q'] : '';
        $results = SearchImages::select()->where('query', $query)->get();
        
        if($results->isEmpty()) {
            $vqd = $this->getDuckVqdParam($query);
            $countResults = $this->getDuckImages($vqd, $query);
            if ($countResults > 0) {
                $results = SearchImages::select()->where('query', $query)->get();
            }
        }
        if(!$results->isEmpty()) {
            foreach($results as $k => $result) {
                $datas[$k] = $result->getAttributes();
                $datas[$k]['path'] = url('storage/'.$result->images->path);
                $datas[$k]['name'] = $result->images->name; 
                $datas[$k]['color'] = $result->images->color; 
                $datas[$k]['palette'] = $result->images->palette; 
                $datas[$k]['colorName'] = $result->images->color_name; 
                $datas[$k]['colorFullname'] = $result->images->color_fullname; 
            }
        }
        
        return response()->json([
            'type'      => 'store',
            'query'     => $query,
            'slug'      => str_slug($query),
            'results'   => $datas
        ]);
    }

    public function show($query)
    {
        $results = SearchImages::where('query', $query)->get();
        if(!$results->isEmpty()) {
            SearchImages::where('query', $query)->update([
                'count'         => $results[0]->count + 1,
                'updated_at'    => date("Y-m-d H:i:s")
            ]);
        }
        foreach ($results as $result) {
            $result['color'] = json_decode($result['color']);
            $result['palette'] = json_decode($result['palette']);
            $result['path'] = url('storage/'.$result->images->path);
            $result['name'] = $result->images->name; 
        }
        return response()->json([
            'type'      => 'show',
            'query'     => $query,
            'slug'      => str_slug($query),
            'results'   => $results
        ]);
    }

    private function getDuckVqdParam($query) {
        $vqd = null;
        $url = 'https://duckduckgo.com/?t=hg&iar=images&iax=images&ia=images&q='.urlencode($query);
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

    private function getDuckImages($vqd, $query) {
        $listImages = [];
        $images = [];
        if ($vqd == null) {
            return $images;
        }
        $querySlug = str_slug($query);
        $url = 'https://duckduckgo.com/i.js?l=fr-fr&o=json&q='.$query.'&vqd='.$vqd.'&f=,,,&p=1';
        $content = json_decode($this->Download($url));
        foreach($content->results as $k => $result) {
            $images[] = [
                'path'  => $querySlug.'/'.$querySlug.'-'.$k,
                'name'  => $querySlug.'-'.$k,
                'url'   => $result->thumbnail
            ];
        }
        $i = 0;
        $datas = [];
        foreach ($images as $image) {
            if ($i > 11) {
                break;
            }
            $contents = file_get_contents($image['url']);
            $imagesController = new ImagesController();
            $imageDatas = $imagesController->storeImage($contents, 'coloriz', $image);
            if ($imageDatas['size'] > 0) {
                $i++;
                $datas[] = [
                    'id_image'      => $imageDatas['id'],
                    'query'         => $query,
                    'slug'          => $querySlug,
                    'count'         => 1,
                    'created_at'    => date("Y-m-d H:i:s"),
                    'updated_at'    => date("Y-m-d H:i:s")
                ];
            }
        }
        SearchImages::insert($datas);
        return sizeof($datas);
    }
}
