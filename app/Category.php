<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public function tags() {
        return $this->hasMany('App\Tag');
    }
}
