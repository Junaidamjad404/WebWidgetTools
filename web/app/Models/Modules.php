<?php

namespace App\Models;

use App\Models\GeneralModules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Modules extends Model
{
    use HasFactory;
    protected $fillable = [ 'general_module_id', 'shop_id', 'custom_settings'];
    
    protected $guarded=['status'];
    
    protected $casts = [
        'status' => 'boolean',
    ];
     public function getCustomSettingsAttribute($value)
    {
        return json_decode($value, true); // Decode the JSON string into an array
    }
    
    public function generalModule(){
        return $this->belongsTo(GeneralModules::class, 'general_module_id');
    }
}
