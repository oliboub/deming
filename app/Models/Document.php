<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property bool $file_exists
 * @property int  $link_count
 * @property bool $hash_valid
 */
class Document extends Model
{
    use HasFactory;


    public function measure()
    {
        return $this->belongsTo(Measure::class, 'measure_id');
    }
}
