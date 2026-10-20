<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Key/value store behind Admin > Settings. Read through App\Services\SettingsRepository.
 */
class Setting extends Model
{
    public const CREATED_AT = null;

    protected $primaryKey = 'setting_key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['setting_key', 'setting_value'];
}
