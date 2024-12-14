<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;  // Belangrijk! Deze import moet er zijn

class Prompt extends Model
{
    protected $fillable = ['name', 'identifier', 'template', 'description'];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($prompt) {
            // Voor debug, laten we zien wat er gebeurt
            dump("Creating prompt...");
            dump($prompt->name);
            
            if (empty($prompt->identifier)) {
                $prompt->identifier = Str::slug($prompt->name);
                dump("Set identifier to: " . $prompt->identifier);
            }
        });
    }
}

