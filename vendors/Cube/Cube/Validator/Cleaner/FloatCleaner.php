<?php

namespace Cube\Validator\Cleaner;

use Cube\Validator\Tool\FilterVarHelper;

class FloatCleaner
    extends CleanerWrapper
{
    use FilterVarHelper;

    /**
     * @return int
     */
    protected function getType()
    {
        return FILTER_SANITIZE_NUMBER_FLOAT;
    }
}