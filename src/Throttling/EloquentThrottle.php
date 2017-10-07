<?php

/**
 * Part of the Sentinel package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Sentinel
 * @version    2.0.15
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011-2017, Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Cartalyst\Sentinel\Throttling;

use Illuminate\Database\Eloquent\Model;

class EloquentThrottle extends Model
{
    /**
     * {@inheritDoc}
     */
    protected $table = 'throttle';
    const CREATED_AT = 'IDATE';
    const UPDATED_AT = 'UDATE';
    protected $primaryKey="ID";
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'IP',
        'TYPE',
    ];
}
