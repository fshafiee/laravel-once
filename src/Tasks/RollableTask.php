<?php

namespace LaravelOnce\Tasks;

use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Queue\QueueableEntity;
use Illuminate\Contracts\Queue\QueueableCollection;
use Illuminate\Queue\SerializesModels;

abstract class RollableTask
{
    use SerializesModels;

    abstract public function perform();

    /**
     * Get the property value prepared for serialization, except
     * for model relations.
     *
     * @param  mixed  $value
     * @return mixed
     */
    protected function getSerializedPropertyValue($value)
    {
        if ($value instanceof QueueableCollection) {
            return new ModelIdentifier(
                $value->getQueueableClass(),
                $value->getQueueableIds(),
                [],
                $value->getQueueableConnection()
            );
        }

        if ($value instanceof QueueableEntity) {
            return new ModelIdentifier(
                get_class($value),
                $value->getQueueableId(),
                [],
                $value->getQueueableConnection()
            );
        }

        return $value;
    }
}
