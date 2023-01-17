<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Password_resets extends Model
{
    use HasFactory;
    protected $primary_key = null;
    public $incrementing = false;
    protected $fillable = ['email','token'];
    protected $table = "password_resets";
}
