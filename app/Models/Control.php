<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class Control extends Model
{
    use HasFactory, Auditable;

    public static $searchable = [
        'name',
        'clause',
        'objective',
        'input',
        'attributes',
        'model',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'domain_id',
        'clause',
        'name',
        'objective',
        'attributes',
        'input',
        'model',
        'indicator',
        'action_plan',
    ];

    // Return the domain associated to this control
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    // Return the completed measures associated to this control
    public function measures(): BelongsToMany
    {
        return $this->belongsToMany(Measure::class)
            ->whereNotNull('realisation_date')->orderBy('realisation_date');
    }

    // Return all measures associated to this control (including pending ones, for API sync)
    public function allMeasures(): BelongsToMany
    {
        return $this->belongsToMany(Measure::class);
    }

    // Check if there is an active (pending) measure associated with this control
    public function isActive(): bool
    {
        return DB::table('control_measure')
            ->where('control_id', $this->id)
            ->join('measures', 'measures.id', '=', 'control_measure.measure_id')
            ->whereNull('measures.realisation_date')
            ->exists();
    }

    // Check if all associated measures are done (none pending)
    public function isDisabled(): bool
    {
        return DB::table('control_measure')
            ->where('control_id', $this->id)
            ->exists()
        &&
            ! DB::table('control_measure')
                ->where('control_id', $this->id)
                ->join('measures', 'measures.id', '=', 'control_measure.measure_id')
                ->whereNull('measures.realisation_date')
                ->exists();
    }
}
