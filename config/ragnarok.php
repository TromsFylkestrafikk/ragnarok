<?php

return [
    /*--------------------------------------------------------------------------
     *
     * Max number of errors per batch run (operation) before batch is canceled.
     *
     * --------------------------------------------------------------------------
     */
    'max_batch_errors' => 5,

    /*--------------------------------------------------------------------------
     *
     * If this is '%', the above value is in percentage of total jobs.
     * Otherwise, the above number is the absolute limit of errors.
     *
     * --------------------------------------------------------------------------
     */
    'max_batch_errors_unit' => '%',
];
