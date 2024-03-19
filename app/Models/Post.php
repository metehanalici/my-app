<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = 'posts'; // Veritabanında kullanılan tablo adı
    protected $primaryKey = 'id'; // Birincil anahtar alanı
    public $timestamps = false; // Zaman damgası sütunlarının kullanılıp kullanılmayacağı
}
