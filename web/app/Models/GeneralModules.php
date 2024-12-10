<?php

namespace App\Models;

use App\Models\Modules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneralModules extends Model
{
    use HasFactory;
    
    protected $fillable=['name','handle','description','settings','image'];
    // Mutator to automatically handle image upload
    public function setImageAttribute($value)
    {
        if (is_file($value)) {
            // Store image in the 'public' disk, under 'images/general_modules' directory
            $path = $value->store('images/general_modules', 'public');
            $this->attributes['image'] = $path; // Save the relative path in the database
        }
    }

    // Accessor to retrieve the full URL of the image
    public function getImageAttribute($value)
    {
        if ($value) {
            // Convert the stored relative path to the full URL
            return asset(Storage::url($value)); // Returns the full URL of the image
        }
        return null; // Return null if no image is set
    }
    public function module()
    {
        return $this->hasOne(Modules::class, 'general_module_id');
    }
}
