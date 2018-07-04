<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ranking_week extends Model
{
     protected $table = 'ranking_weeks';
     protected $fillable = ['user_id', 'score_week_1', 'score_week_2', 'score_week_3', 'score_week_4', 'score_week_5', 'score_week_6', 'score_week_7', 'score_week_8', 'score_week_9', 'score_week_10', 'score_week_11', 'score_week_12', 'score_week_13', 'score_week_14', 'score_week_15', 'score_week_16', 'score_week_17', 'score_week_18', 'score_week_19', 'score_week_20'];
     protected $primaryKey = 'id';

   	 // public $incrementing = true;
}
