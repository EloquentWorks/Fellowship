<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use PixelError\Friendships\Traits\HasFriendships;

class User extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract
{
    use Authenticatable;
    use Authorizable;
    use CanResetPassword;
    use HasFriendships;

    /** @var string $table The database table used by the model. */
    protected $table = 'users';

    /** @var array $fillable The attributes that are mass assignable. */
    protected $fillable = ['name', 'email', 'password'];

    /** @var array $hidden The attributes excluded from the model's JSON form. */
    protected $hidden = ['password', 'remember_token'];
}
