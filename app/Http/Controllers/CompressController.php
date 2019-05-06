<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use ImageOptimizer;
use Storage;
use File;
use App\Images;
use App\Compress;

class CompressController extends Controller
{

    function show($id) {
        $result = Compress::where('id', $id)->first();
        if ($result === null) {
            return response()->json([
                'state'     => "error",
                'message'   => 'id invalide'
            ]); 
        }
        $file = Images::where('id', $result->id_image)->first();
        $file->url = url('storage/'.$file->path);
        $fileCompress = Images::where('id', $result->id_image_compress)->first();
        $fileCompress->url = url('storage/'.$file->path);
        return response()->json([
            'state'     => 'success',
            'results'   => [
                'datas'         => $result,
                'file'          => $file,
                'fileCompress'  => $fileCompress
            ]
        ]);
    }

    function store(Request $request) {

        $uploadedFile = $request->file("file");
        $mimeType = $uploadedFile->getMimeType();

        $lstMimeType = ['image/gif', 'image/png', 'image/jpeg'];
        if (!in_array($mimeType, $lstMimeType)) {
            return response()->json([
                'state'     => 'error',
                'message'   => 'Le format de votre fichier est invalide ('.$mimeType.')'
            ]);
        }

        $imagesController = new ImagesController();
        $content = file_get_contents($uploadedFile);
        $imageDatas = $imagesController->storeImage($content, 'compress');
        $extension = $this->getExtension($imageDatas['type']);
        $compressImgName = $imageDatas['name'] . '_compress';
        $tmpPathCompressImg = '/tmp/'. $compressImgName. '.'. $extension;
        $res = ImageOptimizer::optimize(storage_path('app/public/'.$imageDatas['path']), $tmpPathCompressImg);

        $imagesController = new ImagesController();
        $content = file_get_contents($tmpPathCompressImg);
        $imageDatasCompress = $imagesController->storeImage($content, 'compress', ['name' => $compressImgName]);

        $id = Compress::insertGetId([
            'id_image'          => $imageDatas['id'],
            'id_image_compress' => $imageDatasCompress['id'],
            'original_name'     => $uploadedFile->getClientOriginalName(),
            'gain'              => $imageDatas['size'] - $imageDatasCompress['size'],
            'gain_pct'          => ($imageDatas['size'] - $imageDatasCompress['size']) > 0 ? abs(intval(($imageDatasCompress['size'] - $imageDatas['size']) / $imageDatas['size'] * 100)) : 0,
            'created_at'        => date("Y-m-d H:i:s") ,
            'updated_at'        => date("Y-m-d H:i:s") 
        ]);

        return response()->json([
            'state'             => 'success',
            'results'           => ['id' => $id]
        ]);
    }

    private function getExtension($mimeType) {
        $extension = '';
        switch($mimeType) {
            case 'image/jpeg':
                $extension = 'jpg';
                break;
            case 'image/png':
                $extension = 'png';
                break;
            case 'image/gif':
                $extension = 'gif';
                break;
            case 'image/svg+xml':
                $extension = 'svg';
                break;
            case 'image/webp':
                $extension = 'webp';
                break;
        }
        return $extension;
    }
}
