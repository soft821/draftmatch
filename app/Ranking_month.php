<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ranking_month extends Model
{
     protected $table = 'ranking_months';
     protected $fillable = ['user_id', 'score_month_1', 'score_month_2', 'score_month_3', 'score_month_4', 'score_month_5', 'score_month_6', 'score_month_7', 'score_month_8', 'score_month_9', 'score_month_10', 'score_month_11', 'score_month_12'];
     protected $primaryKey = 'id';
}
