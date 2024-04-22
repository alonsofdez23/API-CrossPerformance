<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entreno extends Model
{
    use HasFactory;

    protected $table = 'entrenos';

    protected $fillable = [
        'denominacion',
        'entreno'
    ];

    /**
     * Get all of the clases for the Entreno
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clases()
    {
        return $this->hasMany(Clase::class);
    }
}
