<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ColorsName extends Model
{
    public function Colors() {
        return $this->belongsTo('App\Colors');
    }
}
