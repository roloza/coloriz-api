<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage;
use ColorThief\ColorThief;
use App\Images;

class FileUploadController extends Controller
{
    public function store(Request $request) {
        
        $uploadedFile = $request->file("file");
        $mimeType = $uploadedFile->getMimeType();
        $lstMimeType = ['image/gif', 'image/png', 'image/jpeg'];
        if (!in_array($mimeType, $lstMimeType)) {
            return response()->json([
                'state'     => 'error',
                'message'   => 'Invalid file type'
            ]);
        }

        $imagesController = new ImagesController();
        $content = file_get_contents($uploadedFile);
        $imageDatas = $imagesController->storeImage($content, 'uploads', [], true);

        $colorController = new ColorsController();
        $imageRequest = new Request();
        $imageRequest->setMethod('POST');
        $imageRequest->request->add(['img' => $imageDatas['name']]);
        $colors = $colorController->store($imageRequest)->getData();
        if ($colors->state !== 'success') {
            return response()->json([
                'state'     => 'error',
                'message'   => 'Erreur lors de la récupération des couleurs'
            ]);
        }        

        return response()->json([
            'state'     => "success",
            'color'     => $colors->color,
            'palette'   => $colors->palette,
            'filepath'  => url('storage/'.$imageDatas['path']),
            'image_id'  => $imageDatas['id']
        ]);
    }

    private function rgb2hex(array $rgb): string {
        return '#'
            . sprintf('%02x', $rgb[0])
            . sprintf('%02x', $rgb[1])
            . sprintf('%02x', $rgb[2]);
    }
}
