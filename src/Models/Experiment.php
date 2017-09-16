<?php

namespace Jenssegers\AB\Models;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

class Experiment extends Model 
{
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        // Set the connection based on the config.
        $this->connection = Config::get('ab.connection');
    }

    protected $table = 'ab_experiments';

    protected $primaryKey = 'id';

    protected $fillable = ['name', 'visitors', 'engagement'];

    public function goals()
    {
        return $this->hasMany('Jenssegers\AB\Models\Goal', 'experiment_id', 'id');
    }

}
