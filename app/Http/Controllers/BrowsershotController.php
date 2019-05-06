<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use App\Browsershots;
use App\Images;
use Exception;

class BrowsershotController extends Controller
{
    public function index() {
        $results = Browsershots::get();
        return response()->json($results);
    }

    public function show($id) {
        $params = Browsershots::where('id', $id)->first();
        if ($params === null) {
            return response()->json([
                'state'     => "error",
                'message'   => 'id invalide'
            ]); 
        }
        $image = Images::where('id', $params->id_image)->first();
        if ($image === null) {
            return response()->json([
                'state'     => "error",
                'message'   => 'image invalide'
            ]); 
        }
        $image->url = url('storage/'.$image->path);

        return response()->json([
            'state'     => "success",
            'results'   => [
                'params' => $params,
                'image' => $image
            ]
        ]); 
    }
    
    public function store(Request $request) {

        // Récupération des paramètres        
        $type = $request->type ? $request->type : '';
        $url = $request->url ? $request->url : '';
        $device = $request->device ? $request->device : 'iPhone X';
        $width = $request->width ? $request->width : 1366;
        $height = $request->height ? $request->height : 768;
        $fullPage = $request->fullpage ? true : false;

        $params = [
          'url'       => $url,
          'type'      => $type,
          'device'    => $device,
          'width'     => $width,
          'height'    => $height,
          'fullPage'  => $fullPage
      ];

        // Initialisation du chemin d'accès de l'image
        $path = '/tmp/';
        $filename = md5(uniqid(rand(), true)).'.jpg';
        $pathToImage = $path. '/'.$filename;

        // Si la capture d'écran a déjà été générée
        $res = Browsershots::where($params)->first();
        if ($res) {
            return response()->json([
                'state'     => "success",
                'message'   => "cache",
                'results'   => ['id' => $res->id]
            ]);  
        }

        // Test si le format de l'url est valide
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json([
                'state'     => "error",
                'message'   => "Le format de l'url ($url) n'est pas valide"
            ]);  
        }

        // Test le statut HTTP retourné par le site
        $httpStatus = $this->urlHttpStatus($url);
        if ($httpStatus !== 200) {
            return response()->json([
                'state'     => "error",
                'message'   => "l'url ($url) n'existe pas (statut HTTP : $httpStatus)"
            ]); 
        }
        $res = true;
        if ($type == 'mobile') {
            $validDevices = [
                'Galaxy S5',
                'ipad',
                'iPhone 4',
                'iPhone 5',
                'iPhone 6',
                'iPhone 7',
                'iPhone 8',
                'iPhone SE',
                'iPhone X',
                'Nexus 10',
                'Nexus 4',
                'Nexus 5',
                'Nexus 5X',
                'Nexus 6',
                'Nexus 6P',
                'Nexus 7',
                'Nokia N9',
                'Pixel 2',
                'Pixel 2 XL',
                'Galaxy S5 landscape',
                'ipad landscape',
                'iPhone 4 landscape',
                'iPhone 5 landscape',
                'iPhone 6 landscape',
                'iPhone 7 landscape',
                'iPhone 8 landscape',
                'iPhone SE landscape',
                'iPhone X landscape',
                'Nexus 10 landscape',
                'Nexus 4 landscape',
                'Nexus 5 landscape',
                'Nexus 5X landscape',
                'Nexus 6 landscape',
                'Nexus 6P landscape',
                'Nexus 7 landscape',
                'Nokia N9 landscape',
                'Pixel 2 landscape',
                'Pixel 2 XL landscape'
            ];
            $device = in_array($device, $validDevices) ? $device : 'iPhone X';
            $res = $this->mobileBrowserShot($url, $pathToImage, $params, $device);
        } else {
            $res = $this->browserShot($url, $pathToImage, $params);
        }

        // S'il y a eu une erreur lors de l'execution de browsershot
        if (!$res) {
            return response()->json([
                'state'     => "error",
                'message'   => "Une erreur est survenue"
            ]); 
        }

        // S'il y a eu une erreur lors de l'enregistrement du fichier image
        if (!file_exists($pathToImage)) {
            return response()->json([
                'state'     => "error",
                'message'   => "Erreur lors de la génération de la capture d'écran"
            ]); 
        }

        $imagesController = new ImagesController();
        $content = file_get_contents($pathToImage);
        $imageDatas = $imagesController->storeImage($content, 'browsershot');
        $params['id_image'] = $imageDatas['id'];
        $id = Browsershots::insertGetId($params);
        
        return response()->json([
            'state'     => "success",
            'message'   => "new",
            'results'   => ['id' => $id]
        ]);    
    }

    private function browserShot($url, $pathToImage, $params) {
        $browsershot = new Browsershot($url, true);
        try{            
            $browsershot
                ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.109 Safari/537.36')
                ->windowSize($params['width'], $params['height'])
                ->noSandbox()
                //->waitUntilNetworkIdle()
                //->timeout(1)
                ->dismissDialogs()
                ->setOption('fullPage', $params['fullPage'])
                ->save($pathToImage); 

            return true;
        } catch(Exception $e){
            //dd($e->getMessage());
            return false;
        }
    }

    private function mobileBrowserShot($url, $pathToImage, $params, $device) {
        $browsershot = new Browsershot($url, true);
        try{   
            $browsershot
                ->noSandbox()
                ->setScreenshotType('jpeg', 100)
                ->device($device)
                //->waitUntilNetworkIdle()
                ->setOption('fullPage', $params['fullPage'])
                ->save($pathToImage);
            return true;
        } catch(Exception $e){
            return false;
        }
    }

    private function urlHttpStatus($url, $nbRedirect = 1) {
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if ($httpCode == 301) {
            $last_url = curl_getinfo($handle, CURLINFO_REDIRECT_URL );
            if ($nbRedirect >= 3) {
                return $httpCode;
            }
            $nbRedirect += 1;
            $httpCode =$this->urlHttpStatus($last_url, $nbRedirect);
        }
        return $httpCode;
    }
}
