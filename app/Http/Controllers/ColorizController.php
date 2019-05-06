<?php

namespace App\Http\Controllers;

use App\Coloriz;
use Illuminate\Http\Request;
use Storage;
use \App\CustomClass\DuckDuckGo as DuckDuckGo;
use App\Images;

class ColorizController extends Controller
{

    public function index(Request $request) {
        $results = [];
        $params = $request->all();
        $type = isset($params['type']) ? $params['type'] : '';
        $color = isset($params['color']) ? $params['color'] : '';
        switch($type) {
            case "last-queries":
                $results = $this->getLastQueries();
                break;
            case "best-queries":
                $results = $this->getBestQueries();
                break;
            case "colors":
                $results = $this->getColors($color);
                break;
        }
        
        return response()->json([
            'state'     => 'success',
            'results'   => $results
        ]);
    }

    private function getLastQueries() {
        $results = [];
        $keywords = Coloriz::select(['query'])->orderBy('updated_at', 'DESC')->take(3)->get();
        return $keywords;
    }

    private function getBestQueries() {
        $keywords = [];
        $keywords = Coloriz::select(['query', 'count', 'color_name'])->orderBy('count', 'DESC')->take(3)->get();
        return $keywords;
    }

    private function getColors($colorName) {
        $keywords = [];
        $colors = Coloriz::select(['color_name'])->groupBy('color_name')->get();
        $colorNames = [];
        foreach ($colors as $color) {
            if ($color['color_name'] !== null) {
                $colorNames[] = $color['color_name'];
            }
        }
        if ($colorName !== '') {
            $keywords = Coloriz::where('color_name', $colorName)->select(['query', 'count'])->orderBy('count', 'DESC')->take(3)->get();
        }
        return [
            'colors'        => $colorNames,
            'currentColor'  => $colorName,
            'keywords'      => $keywords
        ];
    }

    public function show($query) {
        $result = Coloriz::where('query', $query)->first();
        if ($result === null) {
            return response()->json([
                'state'     => "error",
                'message'   => "Aucun résultats trouvés"
            ]);
        }

        Coloriz::where('query', $query)->update([
            'count'         => $result->count + 1,
            'updated_at'    => date("Y-m-d H:i:s")
        ]);
        
        $imagesIds = json_decode($result->images);
        $imageDatas = Images::whereIn('id', $imagesIds)->get();
        foreach ($imageDatas as $datas) {
        $datas['url'] =  url('storage/'.$datas['path']);

        }

        return response()->json([
            'type'      => 'show',
            'query'     => $query,
            'id'        => $result->id,
            'slug'      => str_slug($query),
            'results'   => $imageDatas
        ]);
    }

    public function store(Request $request) {
        $imageDatas = [];
        $params = $request->all();
        $query = isset($params['q']) ? $params['q'] : '';
        $slug = str_slug($query);

        $results = Coloriz::where('slug', $slug)->get();

        // La requête existe déjà
        if(!$results->isEmpty()) {
            return response()->json([
                'state'     => "error",
                'message'   => "Requête déjà effectuée"
            ]);
        }

        $duckDuckGo = new DuckDuckGo($query);
        $images = $duckDuckGo->getImages();

        // Aucun résultat trouvé
        if(sizeof($images) === 0) {
            return response()->json([
                'state'     => "error",
                'message'   => "aucune image trouvée"
            ]);
        }
        $imgIds = [];
        foreach($images as $k => $image) {
            if ($k >= 12) {
                break;
            }
            $content = file_get_contents($image);
            $imagesController = new ImagesController();
            $imageDatas[$k] = $imagesController->storeImage($content, 'coloriz');
            $imgIds[] = $imageDatas[$k]['id'];
        }

        $colorizId = Coloriz::insertGetId([
            'images'        => json_encode($imgIds),
            'query'         => $query,
            'slug'          => str_slug($query),
            'count'         => 1,
            'color_name'    => '',
            'created_at'    => date("Y-m-d H:i:s"),
            'updated_at'    => date("Y-m-d H:i:s")
        ]);
           
        return response()->json([
            'type'      => 'store',
            'query'     => $query,
            'id'        => $colorizId,
            'slug'      => $slug,
            'results'   => $imageDatas
        ]);
    }
}
