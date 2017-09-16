<?php

namespace Jenssegers\AB\Models;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        // Set the connection based on the config.
        $this->connection = Config::get('ab.connection');
    }

    protected $table = 'ab_goals';

    protected $primaryKey = 'id';

    protected $fillable = ['name', 'experiment_id', 'count'];

    public function experiment()
    {
        return $this->belongsTo('Jenssegers\AB\Models\Experiment', 'experiment_id', 'id');
    }

}
