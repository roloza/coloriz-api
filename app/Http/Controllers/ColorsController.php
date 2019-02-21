<?php

namespace App\Http\Controllers;

use App\Images;
use Illuminate\Http\Request;
use Storage;
use ColorThief\ColorThief;

use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;

class ColorsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $params = $request->all();
        $imgName = isset($params['img']) ? $params['img'] : '';

        $result = Images::select(['query', 'slug', 'img_path', 'img_name', 'color', 'palette'])->where('img_name', $imgName)->first();
        if ($result == null) {
            return response()->json([
                'state'     => "error",
                'message'   => "no results"
            ]);
        }

        $imgPath = 'public/'.$result['img_path'];
        if (Storage::size($imgPath) <= 0 ) {
            return response()->json([
                'state'     => "error",
                'message'   => "file doesn't exist"
            ]);
        }
        $img = Storage::get($imgPath);
        $color = $this->rgb2hex(ColorThief::getColor($img));
        $paletteRgb = ColorThief::getPalette($img, 6);
        $palette = [];
        foreach($paletteRgb as $colorRgb) {
            $palette[] = $this->rgb2hex($colorRgb);
        }
        Images::where('img_name', $imgName)->update([
            'color'     => $color,
            'palette'   => json_encode($palette)
        ]);
        

        return response()->json([
            'state'     => "success",
            'img'       => $imgName,
            'color'     => $color,
            'palette'   => $palette
        ]);
    }

    private function rgb2hex(array $rgb): string {
        return '#'
            . sprintf('%02x', $rgb[0])
            . sprintf('%02x', $rgb[1])
            . sprintf('%02x', $rgb[2]);
    }

    public function testColor() {
        $colors = [];
        $img = public_path('uploads/mario-pose2.png');
        $paletteRgb = ColorThief::getPalette($img, 6);
        foreach($paletteRgb as $colorRgb) {
            $colors['thief'][] = $this->rgb2hex($colorRgb);
        }
        $palette = Palette::fromFilename($img);
    
        $palette = $palette->getMostUsedColors(6);
        foreach($palette as $color) {
            $colors['color-extract'][] = Color::fromIntToHex($color);
        }
        dd($colors);
    }
}
