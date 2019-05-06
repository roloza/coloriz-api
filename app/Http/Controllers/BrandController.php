<?php

namespace App\Http\Controllers;

use App\Brand;
use App\Images;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $brands = Brand::where('type', 1)->orderBy('id', 'desc')->with('images')->get();
        foreach($brands as $brand) {
            $brand->url = url('storage/'.$brand->images->path);
        }
        return response()->json($brands);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $datas = [];
        $params = $request->all();
        $query = isset($params['q']) ? $params['q'] : '';
        $brandQuery = "logo ". $query;
        $type = isset($params['type']) ? $params['type'] : 0;
        $slug = str_slug($query);
        $result = Brand::select()->where('slug', $slug)->first();
        if(!$result) {
            $vqd = $this->getDuckVqdParam($brandQuery);
            $images = $this->getDuckImages($vqd, $brandQuery);
            if(sizeof($images) === 0) {
                return response()->json([
                    'state'     => "error",
                    'message'   => "aucune image trouvée"
                ]);
            }
            $content = file_get_contents(current($images));
            $imagesController = new ImagesController();
            $imageDatas = $imagesController->storeImage($content, 'brand');

            $colorController = new ColorsController();
            $imageRequest = new Request();
            $imageRequest->setMethod('POST');
            $imageRequest->request->add(['img' => $imageDatas['name']]);
            $colors = $colorController->store($imageRequest)->getData();

            $imageDatas['color'] = $colors->color;
            $imageDatas['palette'] = json_encode($colors->palette);

            $id = Brand::insertGetId([
                'id_image'      => $imageDatas['id'],
                'name'          => $query,
                'slug'          => $slug,
                'type'          => $type,
                'created_at'    => date("Y-m-d H:i:s"),
                'updated_at'    => date("Y-m-d H:i:s")

            ]);
            return response()->json([
                'state'     => "success",
                'message'   => "new",
                'results'   => ['id' => $id]
            ]);
        }
        return response()->json([
            'state'     => "success",
            'message'   => "new",
            'results'   => ['id' => $result->id]
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $brand = Brand::where('id', $id)->with('images')->first();
        $brand->url = url('storage/'.$brand->images->path);
        return response()->json($brand);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function edit(Brand $brand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Brand $brand)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Brand  $brand
     * @return \Illuminate\Http\Response
     */
    public function destroy(Brand $brand)
    {
        //
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
            $images[] = $result->thumbnail;
        }
        return $images;
    }
}
