<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Config;
use File;
use Illuminate\Http\Request;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['avatar_url', 'avatar_path', 'role'];




    public function getAvatarAttribute()
    {
        return \Cache::get('avatar_img_' . $this->id);
    }

    public function setAvatarAttribute($value)
    {
        $this->attributes['avatar'] = $value;
        \Cache::put('avatar_img_' . $this->id, $value, now()->addDays(30));
    }

    public function uploadAvatar(Request $request)
    {
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $fileName = 'user_avatar_' . $this->id . '.' . 'time_' . time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/avatars', $fileName);
            $this->avatar = 'avatars/' . $fileName;
            $this->save();
        }
    }

    public function getAvatarPathAttribute()
    {
        return storage_path('app/public/' . $this->avatar);
    }

    public function getAvatarUrlAttribute()
    {
        return url('storage/' . $this->avatar);
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M Y');
    }

    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d M Y');
    }

    public function hasBeenVerified()
    {
        return \Cache::get('user_verified_' . $this->id, $this->email_verified_at !== null);
    }

    public function verify()
    {
        $this->email_verified_at = now();
        $this->save();
        \Cache::put('user_verified_' . $this->id, true, now()->addDays(30));
    }

    public function getRoleAttribute()
    {
        if ($this->role) {
            return $this->role;
        }
        return cache('user_role_' . $this->id, 'user');
    }

    public function setRoleAttribute($value)
    {
        $this->attributes['role'] = $value;
        \Cache::put('user_role_' . $this->id, $value);
    }


}
