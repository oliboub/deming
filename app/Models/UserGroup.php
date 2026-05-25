<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->orderBy('name');
    }

    public function controls()
    {
        return $this->belongsToMany(Measure::class, 'control_user_group', 'user_group_id', 'measure_id')->whereNull('realisation_date')->orderBy('plan_date');
    }
}
