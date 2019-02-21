<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Storage;
use ColorThief\ColorThief;

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
        $uploadedFile->move(public_path('uploads'), $uploadedFile->getClientOriginalName());
        $img = public_path('uploads/'. $uploadedFile->getClientOriginalName());
        $color = $this->rgb2hex(ColorThief::getColor($img));

        $paletteRgb = ColorThief::getPalette($img, 6);
        $palette = [];
        foreach($paletteRgb as $colorRgb) {
            $palette[] = $this->rgb2hex($colorRgb);
        }

        return response()->json([
            'state'     => "success",
            'color'     => $color,
            'palette'   => $palette,
            'filepath'  => '/uploads/' . $uploadedFile->getClientOriginalName()
        ]);
    }

    private function rgb2hex(array $rgb): string {
        return '#'
            . sprintf('%02x', $rgb[0])
            . sprintf('%02x', $rgb[1])
            . sprintf('%02x', $rgb[2]);
    }
}
