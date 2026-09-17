<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasUuid;

class Department extends Model
{
    //
    use SoftDeletes;
    use HasUuid;

    protected $fillable = [
        'name',
        'code',
    ];

    public function users(): HasMany{
        return $this->hasMany(User::class);
    } 
}
