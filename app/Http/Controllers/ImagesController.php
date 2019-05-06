<?php

namespace App\Http\Controllers;

use App\Images;
use Illuminate\Http\Request;
use Storage;
use File;
use Intervention\Image\ImageManager;

class ImagesController extends Controller
{

    public function show($id) {
        $image = Images::where('id', $id)->first();
        $image->url = url('storage/'.$image->path);
        return response()->json([
            'state'     => 'success',
            'results'   => $image
        ]);
    }
    public function storeImage($contents, $folder = '', $image = [], $resize = false) {
        // Par défaut les images sont sauvegardées dans un répertoire commons
        if ($folder == '') {
            $folder = 'commons';
        }

        $datas = [];
        $name = '';
        $img = 'fileupload/'. $folder . '/'; // Répertoire ou sont stockées les images
        $name = isset($image['name']) ? $image['name'] : md5(uniqid(rand(), true));
        $img .= isset($image['path']) ? $image['path'] : $name;
        $publicImg = 'public/'. $img; // Répertoire publique (accès image côté serveur)
        
        // Test si le nom de l'image existe déja
        $imageData = $this->imageData($name);
        if(!empty($imageData)) {
            $datas = $imageData;
            $datas['state'] = 'error';
            $datas['message'] = 'Le fichier existe déja';
            return $datas;
        }

        // Enregistre l'image
        Storage::put($publicImg, $contents);
        $storagePath = storage_path('app/'.$publicImg);
        
        // Identification du format de l'image
        $mimetype = File::mimeType($storagePath);
        $extension = $this->getExtension($mimetype);

        if (!Storage::exists($publicImg . '.'. $extension)) {
            // Ajout de l'extension au nom de l'image
            $newpath = Storage::move($publicImg, $publicImg . '.'. $extension);

            if ($resize) {
                /* Resize image */
                $manager = new ImageManager();
                $image = $manager->make($storagePath. '.'. $extension);
                $image->resize(470, null, function ($constraint) {
                    $constraint->aspectRatio(); // Respect du ratio initial
                    $constraint->upsize(); // N'agrandi pas l'image si elle est très petite
                });
                $image->save();
            }
        }
        $data = getimagesize($storagePath . '.'. $extension);
        $size = File::size($storagePath . '.'. $extension);
        $datas = [
            'path'          => $img . '.'. $extension,
            'name'          => $name,
            'width'         => (sizeof($data) > 0) ? $data[0] : null,
            'height'        => (sizeof($data) > 0) ? $data[1] : null,
            'size'          => $size,
            'type'          => $mimetype,
            'color'         => null,
            'palette'       => null,
            'created_at'    => date("Y-m-d H:i:s"),
            'updated_at'    => date("Y-m-d H:i:s") 
        ];
        

        try {
            $datas['id'] = Images::insertGetId($datas);
            $datas['state'] = 'new';
        } catch (\Illuminate\Database\QueryException $e) {
            $imageData = $this->imageData($img . '.'. $extension);
            $datas = $imageData;
            $datas['state'] = 'error';
            $datas['message'] = $e->getMessage();
        }

        $datas['url'] =  url('storage/'.$datas['path']);
        return $datas;
    }

    private function getExtension ($mime_type){

        $extensions = [
            'image/gif' => 'gif', 
            'image/png' => 'png', 
            'image/jpeg' => 'jpg'
        ];    
        return $extensions[$mime_type];
    }

    private function imageData($name) {
        $res = Images::where(['name' => $name])->first();
        if ($res !== null) {
            return $res->getAttributes();
        }
        return null;
    }
}
