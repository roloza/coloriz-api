<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Category as Category;
use \App\Tag as Tag;

class CategoriesController extends Controller
{
    public function index() {
        $results = [];
        $categories = Category::with('tags')->get();
        foreach($categories as $categorie) {
            $results[] = [
                'id'    => $categorie->id,
                'name'  => $categorie->name,
                'slug'  => $categorie->slug,
                'count' => $categorie->tags->count()
            ];
        }
        return response()->json($results);
    }

    public function store(Request $request) {
        $this->addCategories();
    }

    public function addCategories() {
        $categories = ['fruits', 'légumes', 'voitures', 'métiers', 'monnaies', 'véhicules', 
            'outils', 'pays', 'pokémons', 'sports', 'animaux', 'arbres', 'capitales', 'couleurs', 'départements', 'dinosaures', 'éléments chimiques', 
            'fleurs', 'super héros'];

        foreach ($categories as $categorie) {
            if (Category::where(['name' => $categorie])->first() == null) {
                Category::insert(['name' => $categorie, 'slug' => str_slug($categorie)]);
            }
        }
    }
}
