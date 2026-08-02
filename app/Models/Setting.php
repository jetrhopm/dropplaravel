<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'configuracion';

    protected $primaryKey = 'clave';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['clave', 'valor'];

    public static function value(string $key, string $default = ''): string
    {
        return Cache::rememberForever('setting.'.$key, fn () => static::find($key)?->valor ?? $default);
    }

    public static function put(string $key, string $value): void
    {
        static::updateOrCreate(['clave' => $key], ['valor' => $value]);
        Cache::forget('setting.'.$key);
    }
}
