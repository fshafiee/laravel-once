<?php

namespace Laravel\Once\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    static $unguarded = true;

    protected $fillable = [
        '_id',
    ];

    public function getQueueableId()
    {
        return $this->_id;
    }

    public function hello()
    {
        return $this->_id;

    }

}
