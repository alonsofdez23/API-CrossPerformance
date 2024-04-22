<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    use HasFactory;

    protected $table = 'clases';

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    protected $fillable = [
        'monitor_id',
        'entreno_id',
        'fecha_hora',
        'vacantes',
        'final',
        'idevent'
    ];

    /**
     * The atletas that belong to the Clase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function atletas()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * Get the monitor that owns the Clase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function monitor()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the entreno that owns the Clase
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entreno()
    {
        return $this->belongsTo(Entreno::class);
    }
}
