<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $table = 'user';

    protected $primaryKey = 'user_id';

    public $timestamps = false;

    protected $fillable = [
        'user_email',
        'user_first_name',
        'user_last_name',
        'user_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'user_password',
    ];

    public function getAuthPassword()
    {
        return $this->user_password;
    }

    public function nccClient()
    {
        return $this->hasOne(NccClient::class, 'user_id', 'user_id');
    }

    public function checkPlainOrLegacyPassword(string $plain): bool
    {
        $stored = $this->user_password;

        // If looks like MD5 (32 hex), try md5 check
        if (strlen($stored) === 32 && ctype_xdigit($stored)) {
            return md5($plain) === strtolower($stored);
        }

        // Fallback: plain text comparison
        return hash_equals((string) $stored, (string) $plain);
    }
}
