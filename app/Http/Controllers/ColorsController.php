<?php

namespace App\Http\Controllers;

use App\Images;
use Illuminate\Http\Request;
use Storage;
use ColorThief\ColorThief;
use App\SearchImages;
use Intervention\Image\ImageManager;
use \App\Colors as Colors;
use \App\ColorsName as ColorsName;
use App\Coloriz;

use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;
use App\CustomClass\ColorName;

class ColorsController extends Controller
{

    public function show($id) {
        $colors = [];
        $image = Images::where('id', $id)->first();
        $image->url = url('storage', $image->path);

        $manager = new ImageManager();
        //dd(storage_path('app/public/'.$image->path));
        $imageResized = $manager->make(storage_path('app/public/'.$image->path));
        $imageResized->resize(100, null, function ($constraint) {
            $constraint->aspectRatio(); // Respect du ratio initial
            $constraint->upsize(); // N'agrandi pas l'image si elle est très petite
        });
        $imageResized->save('/tmp/' . $imageResized->basename);

        $palette = Palette::fromFilename('/tmp/' . $imageResized->basename);
        $cpt = 0;
        foreach($palette as $color => $count) {
            $cpt++;
            if ($cpt > 1000) {
                break;
            }
            $currentColor = Color::fromIntToHex($color);
            if (strpos($currentColor, '#FFF') === 0) {
                continue;
            }
            if (strpos($currentColor, '#000') === 0) {
                continue;
            }
            $colors[] = [
                //'name' => '', //$currentColor,
                'value' => $count,
                'color' => $currentColor,
            ];
        }

        return response()->json([
            'state'     => 'success',
            'results'   => [
                'image'     => $image,
                'colors'    => $colors
            ]
        ]);

        $extractor = new ColorExtractor($palette);
        $representativesPalette = $extractor->extract(5);
        $representativesColors = [];
        foreach($representativesPalette as $representativeColor) {
            $representativesColors[] = Color::fromIntToHex($representativeColor);
        }
        return response()->json([
            'url'       => url('storage/'.$image->path),
            'colors'    => $representativesColors
        ]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        $params = $request->all();
        $imgName = isset($params['img']) ? $params['img'] : '';
        $slug = isset($params['slug']) ? $params['slug'] : '';

        $result = Images::where('name', $imgName)->first();
        if ($result == null) {
            return response()->json([
                'state'     => "error",
                'message'   => "no results"
            ]);
        }

        $imgPath = 'public/'.$result['path'];
        if (Storage::size($imgPath) <= 0 ) {
            return response()->json([
                'state'     => "error",
                'message'   => "file doesn't exist"
            ]);
        }
        $img = Storage::get($imgPath);
        $color = $this->rgb2hex(ColorThief::getColor($img));
        $name = (new ColorName($this->setNames(), $color))->getName();
        $paletteRgb = ColorThief::getPalette($img, 6);
        $palette = [];
        foreach($paletteRgb as $colorRgb) {
            $palette[] = $this->rgb2hex($colorRgb);
        }
        $colorName = '';
        $colorFullname = '';
        if ($name['state'] === 'success') {
            $colorName = $name['datas']['color'];
            $colorFullname = $name['datas']['colorName'];
        }
        Images::where('name', $imgName)->update([
            'color'             => $color,
            'palette'           => json_encode($palette),
            'color_name'        => $colorName,
            'color_fullname'    => $colorFullname
        ]);

        // On cherche la couleur la plus fréquente
        if ($slug != '') {
            $colors = [];
            $query = Coloriz::where('slug', $slug)->first();
            $imageDatas = Images::whereIn('id', json_decode($query->images))->get();
            foreach($imageDatas as $imageData) {
                if (!array_key_exists($imageData->color_name, $colors)) {
                    $colors[$imageData->color_name] = [
                        'name'  => $imageData->color_name,
                        'count' => 1
                    ];
                } else {
                    $colors[$imageData->color_name] = [
                        'name'  => $imageData->color_name, 
                        'count' => $colors[$imageData->color_name]['count'] + 1
                    ];
                }
            }
            usort($colors, function($a, $b) {
                return $b['count'] - $a['count'];
            });
            $bestColor = $colors[0]['name'];
            Coloriz::where('id', $query->id)->update(['color_name' => $bestColor]);
        }
        

        return response()->json([
            'state'             => "success",
            'img'               => $imgName,
            'color'             => $color,
            'palette'           => $palette,
            'color_name'        => $colorName,
            'color_fullname'    => $colorFullname
        ]);
    }

    private function sort($a, $b) {
        return $a['order'] - $b['order'];
    }

    private function rgb2hex(array $rgb): string {
        return '#'
            . sprintf('%02x', $rgb[0])
            . sprintf('%02x', $rgb[1])
            . sprintf('%02x', $rgb[2]);
    }

    public function addColors() {
        $categories = ['Violet', 'Orange', 'Marron', 'Rose', 'Vert', 'Bleu', 'Jaune', 'Rouge', 'Gris', 'Noir', 'Blanc'];
        $names = [
            ['370028', 'Violet', 'Aubergine'],
            ['6600FF', 'Violet', 'Bleu persan'],
            ['BD33A4', 'Violet', 'Byzantin'],
            ['702963', 'Violet', 'Byzantium'],
            ['DE3163', 'Violet', 'Cerise'],
            ['6A455D', 'Violet', 'Colombin'],
            ['FD3F92', 'Violet', 'Fushia'],
            ['C9A0DC', 'Violet', 'Glycine'],
            ['D2CAEC', 'Violet', 'Gris de lin'],
            ['DF73FF', 'Violet', 'Héliotrope'],
            ['791CF8', 'Violet', 'Indigo'],
            ['2E006C', 'Violet', 'Indigo'],
            ['9683EC', 'Violet', 'Lavande'],
            ['AC1E44', 'Violet', 'Lie de vin'],
            ['B666D2', 'Violet', 'Lilas'],
            ['FF00FF', 'Violet', 'Magenta'],
            ['800080', 'Violet', 'Magenta foncé'],
            ['DB0073', 'Violet', 'Magenta fushia'],
            ['D473D4', 'Violet', 'Mauve'],
            ['DA70D6', 'Violet', 'Orchidée'],
            ['CFA0E9', 'Violet', 'Parme'],
            ['9E0E40', 'Violet', 'Pourpre'],
            ['811453', 'Violet', 'Prune'],
            ['F9429E', 'Violet', 'Rose bonbon'],
            ['FF007F', 'Violet', 'Rose vif'],
            ['C71585', 'Violet', 'Rouge-violet'],
            ['723E64', 'Violet', 'Violet d\'évêque'],
            ['A10684', 'Violet', 'Violine'],
            ['6C0277', 'Violet', 'Zinzolin'],
            ['ED7F10', 'Orange', 'Orange'],
            ['E67E30', 'Orange', 'Abricot'],
            ['FFCB60', 'Orange', 'Aurore'],
            ['F1E2BE', 'Orange', 'Bis'],
            ['FFE4C4', 'Orange', 'Bisque'],
            ['F4661B', 'Orange', 'Carotte'],
            ['DF6D14', 'Orange', 'Citrouille'],
            ['E73E01', 'Orange', 'Corail'],
            ['B36700', 'Orange', 'Cuivre'],
            ['EF9B0F', 'Orange', 'Gomme-gutte'],
            ['FEA347', 'Orange', 'Mandarine'],
            ['DE9816', 'Orange', 'Melon'],
            ['FAA401', 'Orange', 'Orangé'],
            ['CC5500', 'Orange', 'Orange brûlée'],
            ['AD4F09', 'Orange', 'Roux'],
            ['F3D617', 'Orange', 'Safran'],
            ['F88E55', 'Orange', 'Saumon'],
            ['FF7F00', 'Orange', 'Tangerine'],
            ['A75502', 'Orange', 'Tanné'],
            ['E1CE9A', 'Orange', 'Vanille'],
            ['E9C9B1', 'Orange', 'Ventre de biche'],
            ['5B3C11', 'Marron', 'Brun'],
            ['88421D', 'Marron', 'Acajou'],
            ['A76726', 'Marron', 'Alezan'],
            ['F0C300', 'Marron', 'Ambre'],
            ['9D3E0C', 'Marron', 'Auburn'],
            ['8B6C42', 'Marron', 'Basané'],
            ['C8AD7F', 'Marron', 'Beige'],
            ['F5F5DC', 'Marron', 'Beige clair'],
            ['AFA778', 'Marron', 'Beigeasse'],
            ['3D2B1F', 'Marron', 'Bistre'],
            ['856D4D', 'Marron', 'Bistre'],
            ['4E3D28', 'Marron', 'Bitume'],
            ['5B3C11', 'Marron', 'Blet'],
            ['842E1B', 'Marron', 'Brique'],
            ['614E1A', 'Marron', 'Bronze'],
            ['3F2204', 'Marron', 'Brou de noix'],
            ['3F2204', 'Marron', 'Bureau'],
            ['614B3A', 'Marron', 'Cacao'],
            ['2F1B0C', 'Marron', 'Cachou'],
            ['462E01', 'Marron', 'Café'],
            ['785E2F', 'Marron', 'Café au lait'],
            ['7E5835', 'Marron', 'Cannelle'],
            ['7E3300', 'Marron', 'Caramel'],
            ['806D5A', 'Marron', 'Châtaigne'],
            ['8B6C42', 'Marron', 'Châtain'],
            ['85530F', 'Marron', 'Chaudron'],
            ['5A3A22', 'Marron', 'Chocolat'],
            ['DF6D14', 'Marron', 'Citrouille'],
            ['AD4F09', 'Marron', 'Fauve'],
            ['99512B', 'Marron', 'Feuille-morte'],
            ['BBAE98', 'Marron', 'Grège'],
            ['685E43', 'Marron', 'Gris de maure'],
            ['8F5922', 'Marron', 'Lavallière'],
            ['582900', 'Marron', 'Marron'],
            ['87591A', 'Marron', 'Mordoré'],
            ['955628', 'Marron', 'Noisette'],
            ['CC5500', 'Marron', 'Orange brûlée'],
            ['4E1609', 'Marron', 'Puce'],
            ['A5260A', 'Marron', 'Rouge bismarck'],
            ['AE4A34', 'Marron', 'Rouge tomette'],
            ['985717', 'Marron', 'Rouille'],
            ['730800', 'Marron', 'Sang de boeuf'],
            ['8D4024', 'Marron', 'Senois'],
            ['A98C78', 'Marron', 'Sépia'],
            ['AE8964', 'Marron', 'Sépia'],
            ['9F551E', 'Marron', 'Tabac'],
            ['8E5434', 'Marron', 'Terre de Sienne'],
            ['625B48', 'Marron', 'Terre d\'ombre'],
            ['926D27', 'Marron', 'Terre d\'ombre'],
            ['E1CE9A', 'Marron', 'Vanille'],
            ['FD6C9E', 'Rose', 'Rose'],
            ['FFE4C4', 'Rose', 'Bisque'],
            ['DE3163', 'Rose', 'Cerise'],
            ['FEC3AC', 'Rose', 'Chair'],
            ['FDE9E0', 'Rose', 'Coquille d\'oeuf'],
            ['FEE7F0', 'Rose', 'Cuisse de nymphe'],
            ['C72C48', 'Rose', 'Framboise'],
            ['FD3F92', 'Rose', 'Fushia'],
            ['DF73FF', 'Rose', 'Héliotrope'],
            ['FE96A0', 'Rose', 'Incarnadin'],
            ['FF00FF', 'Rose', 'Magenta'],
            ['800080', 'Rose', 'Magenta foncé'],
            ['DB0073', 'Rose', 'Magenta fushia'],
            ['D473D4', 'Rose', 'Mauve'],
            ['FDBFB7', 'Rose', 'Pêche'],
            ['C4698F', 'Rose', 'Rose balais'],
            ['F9429E', 'Rose', 'Rose bonbon'],
            ['FEBFD2', 'Rose', 'Rose dragée'],
            ['997A8D', 'Rose', 'Rose Mountbatten'],
            ['FF866A', 'Rose', 'Rose thé'],
            ['FF007F', 'Rose', 'Rose vif'],
            ['F88E55', 'Rose', 'Saumon'],
            ['00FF00', 'Vert', 'Vert'],
            ['79F8F8', 'Vert', 'Aigue-marine'],
            ['7BA05B', 'Vert', 'Asperge'],
            ['008E8E', 'Vert', 'Bleu sarcelle'],
            ['048B9A', 'Vert', 'Canard'],
            ['83A697', 'Vert', 'Céladon'],
            ['80D0D0', 'Vert', 'Givré'],
            ['649B88', 'Vert', 'Glauque'],
            ['1B4F08', 'Vert', 'Hooker'],
            ['87E990', 'Vert', 'Jade'],
            ['94812B', 'Vert', 'Kaki'],
            ['16B84E', 'Vert', 'Menthe'],
            ['54F98D', 'Vert', 'Menthe à l\'eau'],
            ['149414', 'Vert', 'Sinople'],
            ['25FDE9', 'Vert', 'Turquoise'],
            ['7FDD4C', 'Vert', 'Vert absinthe'],
            ['82C46C', 'Vert', 'Vert amande'],
            ['18391E', 'Vert', 'Vert anglais'],
            ['9FE855', 'Vert', 'Vert anis'],
            ['568203', 'Vert', 'Vert avocat'],
            ['096A09', 'Vert', 'Vert bouteille'],
            ['C2F732', 'Vert', 'Vert chartreuse'],
            ['00FF00', 'Vert', 'Vert citron'],
            ['18391E', 'Vert', 'Vert de chrome'],
            ['95A595', 'Vert', 'Vert de gris'],
            ['22780F', 'Vert', 'Vert de vessie'],
            ['B0F2B6', 'Vert', 'Vert d\'eau'],
            ['01D758', 'Vert', 'Vert émeraude'],
            ['00561B', 'Vert', 'Vert empire'],
            ['175732', 'Vert', 'Vert épinard'],
            ['3A9D23', 'Vert', 'Vert gazon'],
            ['00561B', 'Vert', 'Vert impérial'],
            ['798933', 'Vert', 'Vert kaki'],
            ['85C17E', 'Vert', 'Vert lichen'],
            ['9EFD38', 'Vert', 'Vert lime'],
            ['1FA055', 'Vert', 'Vert malachite'],
            ['386F48', 'Vert', 'Vert mélèse'],
            ['596643', 'Vert', 'Vert militaire'],
            ['679F5A', 'Vert', 'Vert mousse'],
            ['708D23', 'Vert', 'Vert olive'],
            ['97DFC6', 'Vert', 'Vert opaline'],
            ['3AF24B', 'Vert', 'Vert perroquet'],
            ['01796F', 'Vert', 'Vert pin'],
            ['BEF574', 'Vert', 'Vert pistache'],
            ['4CA66B', 'Vert', 'Vert poireau'],
            ['34C924', 'Vert', 'Vert pomme'],
            ['57D53B', 'Vert', 'Vert prairie'],
            ['4CA66B', 'Vert', 'Vert prasin'],
            ['00FE7E', 'Vert', 'Vert printemps'],
            ['095228', 'Vert', 'Vert sapin'],
            ['689D71', 'Vert', 'Vert sauge'],
            ['01D758', 'Vert', 'Vert smaragdin'],
            ['A5D152', 'Vert', 'Vert tilleul'],
            ['586F2D', 'Vert', 'Vert véronèse'],
            ['40826D', 'Vert', 'Vert viride'],
            ['0000FF', 'Bleu', 'Bleu'],
            ['79F8F8', 'Bleu', 'Aigue-marine'],
            ['F0FFFF', 'Bleu', 'Azur brume'],
            ['007FFF', 'Bleu', 'Azur'],
            ['1E7FCB', 'Bleu', 'Azur'],
            ['74D0F1', 'Bleu', 'Azur clair'],
            ['A9EAFE', 'Bleu', 'Azurin'],
            ['3A8EBA', 'Bleu', 'Bleu clair'],
            ['686F8C', 'Bleu', 'Bleu ardoise'],
            ['5472AE', 'Bleu', 'Bleur barbeau'],
            ['5472AE', 'Bleu', 'Bleu bleuet'],
            ['0095B6', 'Bleu', 'Bleu bondi'],
            ['26C4EC', 'Bleu', 'Bleu céleste'],
            ['0F9DE8', 'Bleu', 'Bleu céruléen'],
            ['357AB7', 'Bleu', 'Bleu céruléen'],
            ['8EA2C6', 'Bleu', 'Bleu charette'],
            ['17657D', 'Bleu', 'Bleu charron'],
            ['8EA2C6', 'Bleu', 'Bleu charron'],
            ['77B5FE', 'Bleu', 'Bleu ciel'],
            ['22427C', 'Bleu', 'Bleu cobalt'],
            ['24445C', 'Bleu', 'Bleu de berlin'],
            ['318CE7', 'Bleu', 'Bleu de france'],
            ['003366', 'Bleu', 'Bleu de minuit'],
            ['24445C', 'Bleu', 'Bleu de Prusse'],
            ['1560BD', 'Bleu', 'Bleu denim'],
            ['00CCCB', 'Bleu', 'Mers du sud'],
            ['DFF2FF', 'Bleu', 'Bleu dragées'],
            ['1034A6', 'Bleu', 'Bleu égyptien'],
            ['2C75FF', 'Bleu', 'Bleu électrique'],
            ['56739A', 'Bleu', 'Bleu guède'],
            ['7F8FA6', 'Bleu', 'Bleu horizon'],
            ['6050DC', 'Bleu', 'Bleu majorelle'],
            ['03224C', 'Bleu', 'Bleu marine'],
            ['73C2FB', 'Bleu', 'Bleu maya'],
            ['24445C', 'Bleu', 'Bleu minéral'],
            ['0F056B', 'Bleu', 'Bleu nuit'],
            ['1B019B', 'Bleu', 'Bleu outremer'],
            ['2B009A', 'Bleu', 'Bleu outremer'],
            ['067790', 'Bleu', 'Bleu paon'],
            ['6600FF', 'Bleu', 'Bleu persan'],
            ['1D4851', 'Bleu', 'Bleu pétrole'],
            ['318CE7', 'Bleu', 'Bleu roi'],
            ['0131B4', 'Bleu', 'Bleu saphir'],
            ['008E8E', 'Bleu', 'Bleu sarcelle'],
            ['003399', 'Bleu', 'Bleu smalt'],
            ['0ABAB5', 'Bleu', 'Bleu tiffany'],
            ['425B8A', 'Bleu', 'Bleu turquin'],
            ['26C4EC', 'Bleu', 'Caeruléum'],
            ['048B9A', 'Bleu', 'Canard'],
            ['74D0F1', 'Bleu', 'Cérulé'],
            ['00FFFF', 'Bleu', 'Cyan'],
            ['2BFAFA', 'Bleu', 'Cyan'],
            ['BBD2E1', 'Bleu', 'Fuméee'],
            ['80D0D0', 'Bleu', 'Givré'],
            ['791CF8', 'Bleu', 'Indigo'],
            ['2E006C', 'Bleu', 'Indigo'],
            ['4B0082', 'Bleu', 'Indigo du web'],
            ['002FA7', 'Bleu', 'Klein'],
            ['21177D', 'Bleu', 'Klein'],
            ['26619C', 'Bleu', 'Lapislazuli'],
            ['9683EC', 'Bleu', 'Lavande'],
            ['56739A', 'Bleu', 'Pastel'],
            ['CCCCFF', 'Bleu', 'Pervenche'],
            ['25FDE9', 'Bleu', 'Turquoise'],
            ['FFFF00', 'Jaune', 'Jaune'],
            ['F0C300', 'Jaune', 'Ambre'],
            ['FFCB60', 'Jaune', 'Aurore'],
            ['F0E36B', 'Jaune', 'Beurre'],
            ['FFF48D', 'Jaune', 'Beurre frais'],
            ['E8D630', 'Jaune', 'Blé'],
            ['E2BC74', 'Jaune', 'Blond'],
            ['FCDC12', 'Jaune', 'Boutton d\'or'],
            ['EDD38C', 'Jaune', 'Bulle'],
            ['CDCD0D', 'Jaune', 'Caca d\'oie'],
            ['D0C07A', 'Jaune', 'Chamois'],
            ['FBF2B7', 'Jaune', 'Champagne'],
            ['EDFF0C', 'Jaune', 'Chrome'],
            ['FFFF05', 'Jaune', 'Chrome'],
            ['F7FF3C', 'Jaune', 'Citron'],
            ['AD4F09', 'Jaune', 'Fauve'],
            ['E6E697', 'Jaune', 'Flave'],
            ['FFFF6B', 'Jaune', 'Fleur de soufre'],
            ['EF9B0F', 'Jaune', 'Gomme-gutte'],
            ['EFD242', 'Jaune', 'Jaune auréolin'],
            ['D1B606', 'Jaune', 'Jaune banane'],
            ['E7F00D', 'Jaune', 'Jaune canari'],
            ['DFFF00', 'Jaune', 'Jaune chartreuse'],
            ['FDEE00', 'Jaune', 'Jaune de cobalt'],
            ['FFF0BC', 'Jaune', 'Jaune de Naples'],
            ['EFD807', 'Jaune', 'Jaune d\'or'],
            ['FFE436', 'Jaune', 'Jaune impérial'],
            ['FEF86C', 'Jaune', 'Jaune mimosa'],
            ['C7CF00', 'Jaune', 'Jaune moutarde'],
            ['F7E269', 'Jaune', 'Jaune nankin'],
            ['808000', 'Jaune', 'Jaune olive'],
            ['FEE347', 'Jaune', 'Jaune paille'],
            ['F7E35F', 'Jaune', 'Jaune poussin'],
            ['FFDE75', 'Jaune', 'Maïs'],
            ['EED153', 'Jaune', 'Mars'],
            ['B3B191', 'Jaune', 'Mastic'],
            ['DAB30A', 'Jaune', 'Miel'],
            ['DFAF2C', 'Jaune', 'Ocre jaune'],
            ['DD985C', 'Jaune', 'Ocre rouge'],
            ['FFD700', 'Jaune', 'Or'],
            ['FCD21C', 'Jaune', 'Orpiment'],
            ['B67823', 'Jaune', 'Poil de chameau'],
            ['C3B470', 'Jaune', 'Queue de vache'],
            ['A89874', 'Jaune', 'Queue de vache'],
            ['E0CDA9', 'Jaune', 'Sable'],
            ['F3D617', 'Jaune', 'Safran'],
            ['FFFF6B', 'Jaune', 'Soufre'],
            ['FAEA73', 'Jaune', 'Topaze'],
            ['E1CE9A', 'Jaune', 'Vanille'],
            ['E7A854', 'Jaune', 'Vénitien'],
            ['FF0000', 'Rouge', 'Rouge'],
            ['91283B', 'Rouge', 'Amarante'],
            ['6D071A', 'Rouge', 'Bordeaux'],
            ['842E1B', 'Rouge', 'Brique'],
            ['BB0B0B', 'Rouge', 'Cerise'],
            ['E73E01', 'Rouge', 'Corail'],
            ['ED0000', 'Rouge', 'Ecarlate'],
            ['BF3030', 'Rouge', 'Fraise'],
            ['A42424', 'Rouge', 'Fraise écrasée'],
            ['C72C48', 'Rouge', 'Framboise'],
            ['FD3F92', 'Rouge', 'Fushia'],
            ['E9383F', 'Rouge', 'Grenadine'],
            ['6E0B14', 'Rouge', 'Grenat'],
            ['FE96A0', 'Rouge', 'Incarnadin'],
            ['FF6F7D', 'Rouge', 'Incarnat'],
            ['FF00FF', 'Rouge', 'Magenta'],
            ['800080', 'Rouge', 'Magenta foncé'],
            ['DB0073', 'Rouge', 'Magenta fushia'],
            ['D473D4', 'Rouge', 'Mauve'],
            ['FC5D5D', 'Rouge', 'Nacarat'],
            ['DD985C', 'Rouge', 'Ocre rouge'],
            ['91283B', 'Rouge', 'Passe-velours'],
            ['9E0E40', 'Rouge', 'Pourpre'],
            ['811453', 'Rouge', 'Prune'],
            ['FF007F', 'Rouge', 'Rose vif'],
            ['D90115', 'Rouge', 'Rouge alizarine'],
            ['F7230C', 'Rouge', 'Rouge anglais'],
            ['A5260A', 'Rouge', 'Rouge bismarck'],
            ['6B0D0D', 'Rouge', 'Rouge bourgogne'],
            ['FF5E4D', 'Rouge', 'Rouge capucine'],
            ['B82010', 'Rouge', 'Rouge cardinal'],
            ['960018', 'Rouge', 'Rouge carmin'],
            ['DB1702', 'Rouge', 'Rouge cinabre'],
            ['FD4626', 'Rouge', 'Rouge cinabre'],
            ['C60800', 'Rouge', 'Rouge coquelicot'],
            ['960018', 'Rouge', 'Rouge cramoisi'],
            ['DC143C', 'Rouge', 'Rouge cramoisi'],
            ['A91101', 'Rouge', 'Rouge Andrinople'],
            ['EB0000', 'Rouge', 'Rouge d\'aniline'],
            ['801818', 'Rouge', 'Rouge de Falun'],
            ['F7230C', 'Rouge', 'Rouge de mars'],
            ['BC2001', 'Rouge', 'Rouge écrevisse'],
            ['FE1B00', 'Rouge', 'Rouge feu'],
            ['FF4901', 'Rouge', 'Rouge feu'],
            ['EE1010', 'Rouge', 'Rouge garance'],
            ['CF0A1D', 'Rouge', 'Rouge groseille'],
            ['C60800', 'Rouge', 'Rouge ponceau'],
            ['E0115F', 'Rouge', 'Rouge rubis'],
            ['850606', 'Rouge', 'Rouge sang'],
            ['DE2916', 'Rouge', 'Rouge tomate'],
            ['AE4A34', 'Rouge', 'Rouge tomette'],
            ['A91101', 'Rouge', 'Rouge turc'],
            ['DB1702', 'Rouge', 'Rouge vermillon'],
            ['FD4626', 'Rouge', 'Rouge vermillon'],
            ['C71585', 'Rouge', 'Rouge-violet'],
            ['985717', 'Rouge', 'Rouille'],
            ['730800', 'Rouge', 'Sang de boeuf'],
            ['8D4024', 'Rouge', 'Senois'],
            ['CC4E5C', 'Rouge', 'Terracotta'],
            ['FF0921', 'Rouge', 'Vermeil'],
            ['6C0277', 'Rouge', 'Zizolin'],
            ['606060', 'Gris', 'Gris'],
            ['5A5E6B', 'Gris', 'Ardoise'],
            ['CECECE', 'Gris', 'Argent'],
            ['EFEFEF', 'Gris', 'Argile'],
            ['766F64', 'Gris', 'Bis'],
            ['3D2B1F', 'Gris', 'Bistre'],
            ['856D4D', 'Gris', 'Bistre'],
            ['4E3D28', 'Gris', 'Bitume'],
            ['83A697', 'Gris', 'Céladon'],
            ['806D5A', 'Gris', 'Châtaigne'],
            ['BABABA', 'Gris', 'Etain oxydé'],
            ['EDEDED', 'Gris', 'Etain pur'],
            ['BBD2E1', 'Gris', 'Fuméee'],
            ['BBAE98', 'Gris', 'Grège'],
            ['AFAFAF', 'Gris', 'Gris acier'],
            ['303030', 'Gris', 'Gris antharcite'],
            ['677179', 'Gris', 'Gris de Payne'],
            ['848484', 'Gris', 'Gris fer'],
            ['7F7F7F', 'Gris', 'Gris fer'],
            ['CECECE', 'Gris', 'Gris Perle'],
            ['C7D0CC', 'Gris', 'Gris Perle'],
            ['9E9E9E', 'Gris', 'Gris souris'],
            ['BBACAC', 'Gris', 'Gris tourterelle'],
            ['B3B191', 'Gris', 'Mastic'],
            ['CCCCCC', 'Gris', 'Pinchard'],
            ['798081', 'Gris', 'Plomb'],
            ['997A8D', 'Gris', 'Rose de Mountbatten'],
            ['463F32', 'Gris', 'Taupe'],
            ['C1BFB1', 'Gris', 'Tourdile'],
            ['000000', 'Noir', 'Noir'],
            ['000000', 'Noir', 'Aile de corbeau'],
            ['3F2204', 'Noir', 'Brou de noix'],
            ['2C030B', 'Noir', 'Cassis'],
            ['3A020D', 'Noir', 'Cassis'],
            ['0B1616', 'Noir', 'Dorian'],
            ['000000', 'Noir', 'Ebène'],
            ['000000', 'Noir', 'Noir animal'],
            ['000010', 'Noir', 'Noir charbon'],
            ['120D16', 'Noir', 'Noir d\'aniline'],
            ['130E0A', 'Noir', 'Noir de carbone'],
            ['130E0A', 'Noir', 'Noir de fumée'],
            ['000000', 'Noir', 'Noir de jais'],
            ['000000', 'Noir', 'Noir d\'encre'],
            ['000000', 'Noir', 'Noir d\'ivoire'],
            ['2F1E0E', 'Noir', 'Noiraud'],
            ['2D241E', 'Noir', 'Réglisse'],
            ['FFFFFF', 'Blanc', 'Blanc'],
            ['FEFEFE', 'Blanc', 'Albâtre'],
            ['EFEFEF', 'Blanc', 'Argile'],
            ['F0FFFF', 'Blanc', 'Azur brume'],
            ['F5F5DC', 'Blanc', 'Beige clair'],
            ['FEFEE2', 'Blanc', 'Blanc cassé'],
            ['FEFEFE', 'Blanc', 'Blanc céruse'],
            ['FDF1B8', 'Blanc', 'Blanc crème'],
            ['FEFEFE', 'Blanc', 'Blanc d\'argent'],
            ['FBFCFA', 'Blanc', 'Blanc de lait'],
            ['FAF0E6', 'Blanc', 'Blanc de lin'],
            ['FAF0C5', 'Blanc', 'Blanc de platine'],
            ['FEFEFE', 'Blanc', 'Blanc de plomb'],
            ['FEFEFE', 'Blanc', 'Blanc de saturne'],
            ['FEFDF0', 'Blanc', 'Blanc de Troyes'],
            ['F6FEFE', 'Blanc', 'Blanc de zinc'],
            ['FEFDF0', 'Blanc', 'Blanc d\'Espagne'],
            ['FFFFD4', 'Blanc', 'Blanc d\'ivoire'],
            ['FEFEE0', 'Blanc', 'Blac écru'],
            ['F4FEFE', 'Blanc', 'Blanc lunaire'],
            ['FEFEFE', 'Blanc', 'Blanc neige'],
            ['F2FFFF', 'Blanc', 'Blanc opalin'],
            ['FEFEFE', 'Blanc', 'Blanc-bleu'],
            ['FDE9E0', 'Blanc', 'Coquille d\'oeuf'],
            ['FEE7F0', 'Blanc', 'Cuisse de nymphe']
        ];
        foreach ($categories as $categorie) {
            if (Colors::where(['name' => $categorie])->first() == null) {
                Colors::insert(['name' => $categorie]);
            }
        }
        foreach ($names as $name) {
            if (ColorsName::where(['code' => $name[0]])->first() == null) {
                $colors = Colors::where(['name' => $name[1]])->first();
                try {
                    ColorsName::insert([
                        'color_id'  => $colors->id,
                        'code'      => $name[0],
                        'name'      => $name[2]
                    ]);
                }
                catch(\Illuminate\Database\QueryException $e){
                    // do what you want here with $e->getMessage();
                }
            }
        }
    }

    public function getColorName($colorCode) {
        return response()->json((new ColorName($this->setNames(), '#'.$colorCode))->getName());
    }

    private function setNames() {
        $names = [];
        $colors = Colors::with('ColorsName')->get();
        foreach ($colors as $color) {
            foreach ($color->ColorsName as $colorName) {
                $names[] = [
                    $colorName->code,
                    $color->name,
                    $colorName->name
                ];
            }
        }
        return $names;
    }

    public function updateImagesWithoutColor() {
        $names = $this->setNames();
        $images = Images::whereNotNull('color')->get();
        foreach ($images as $image) {
            $color = (new ColorName($names, $image->color))->getName();
            if ($color['state'] === 'success') {
                Images::where('id', $image->id)->update([
                    'color_name'        => $color['datas']['color'],
                    'color_fullname'    => $color['datas']['colorName'],
                ]);
            }
        }
    }
}
