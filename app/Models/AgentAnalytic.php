<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentAnalytic extends Model
{
    protected $fillable = ['agent_name', 'tokens_used', 'cost', 'model_used'];
}