<?php

namespace App\Http\Controllers;

use App\Images;
use Illuminate\Http\Request;
use Storage;

class ImagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $results = [];
        $queries = Images::select('query')->groupBy('query')->take(50)->get();
        foreach ($queries as $query) {
            $results[] = $query->query;
        }
        return response()->json($results);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $params = $request->all();
        $query = isset($params['q']) ? $params['q'] : '';
        $results = Images::select(['query', 'slug', 'img_path', 'img_name', 'color', 'palette'])->where('query', $query)->get();
        if($results->isEmpty()) {
            $vqd = $this->getDuckVqdParam($query);
            $results = $this->getDuckImages($vqd, $query);
        }
        return response()->json([
            'type'      => 'store',
            'query'     => $query,
            'slug'      => str_slug($query),
            'results'   => $results
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Images  $images
     * @return \Illuminate\Http\Response
     */
    public function show($query)
    {
        $results = Images::select(['query', 'slug', 'img_path', 'img_name', 'color', 'palette'])->where('query', $query)->get();
        foreach ($results as $result) {
            $result['palette'] = json_decode($result['palette']);
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
                'path' => $querySlug.'/'.$querySlug.'-'.$k,
                'name' => $querySlug.'-'.$k,
                'url' => $result->thumbnail
            ];
        }
        $i = 0;
        $datas = [];
        foreach ($images as $image) {
            if ($i > 11) {
                break;
            }
            $contents = file_get_contents($image['url']);
            $img = 'public/'.$image['path'];
            Storage::put($img, $contents);
            $imgSize = Storage::size($img);
            if ($imgSize > 0) {
                $i++;
                $datas[] = [
                    'query'     => $query,
                    'slug'      => $querySlug,
                    'img_path'  => $image['path'],
                    'img_name'  => $image['name'],
                    'color'     => null,
                    'palette'   => null
                ];
            }
        }
        Images::insert($datas);
        return $datas;
    }
}
