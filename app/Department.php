<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Course;

class Department extends Model
{
    public function courses(){
        return $this->hasMany('App\Course');
    }
}
