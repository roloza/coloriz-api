<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class colors extends Model
{
    public function ColorsName() {
        return $this->hasMany('App\ColorsName', 'color_id');
    }
}
