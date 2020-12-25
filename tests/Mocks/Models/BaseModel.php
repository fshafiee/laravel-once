<?php

namespace LaravelOnce\Tests\Mocks\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
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
