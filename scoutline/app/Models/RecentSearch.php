<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecentSearch extends Model
{
    protected $fillable = ['member_id', 'category', 'location', 'results'];

    // This ensures the JSON column is automatically converted to an array
    protected $casts = [
        'results' => 'array',
    ];
// most searched categories
 public static function mostSearchedCategories(int $limit = 20)
    {
        return static::select('category')
            ->selectRaw('COUNT(*) as search_count')
            ->groupBy('category')
            ->orderByDesc('search_count')
            ->take($limit)
            ->get();
    }
}