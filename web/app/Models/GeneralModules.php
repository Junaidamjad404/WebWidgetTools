<?php

namespace App\Models;

use App\Models\Modules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneralModules extends Model
{
    use HasFactory;
    
    protected $fillable=['name','handle','description','settings'];

    public function module()
    {
        return $this->hasOne(Modules::class, 'general_module_id');
    }
}
