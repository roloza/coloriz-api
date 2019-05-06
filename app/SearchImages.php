<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SearchImages extends Model
{
    public function images(){
        return $this->belongsTo('App\Images', 'id_image');
    }
}
