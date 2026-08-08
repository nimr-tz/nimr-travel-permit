<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a travel request changed state between the time a page was
 * rendered and the time its form was submitted — for example a request that
 * was approved or cancelled in another tab while an edit was open.
 */
class StaleRequestException extends RuntimeException
{
}
