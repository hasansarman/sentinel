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

namespace Cartalyst\Sentinel\Activations;

use Carbon\Carbon;
use Cartalyst\Sentinel\Users\UserInterface;
use Cartalyst\Support\Traits\RepositoryTrait;

class IlluminateActivationRepository implements ActivationRepositoryInterface
{
    use RepositoryTrait;

    /**
     * The Eloquent activation model name.
     *
     * @var string
     */
    protected $model = 'Cartalyst\Sentinel\Activations\EloquentActivation';

    /**
     * The activation expiration time, in seconds.
     *
     * @var int
     */
    protected $expires = 259200;

    /**
     * Create a new Illuminate activation repository.
     *
     * @param  string  $model
     * @param  int  $expires
     * @return void
     */
    public function __construct($model = null, $expires = null)
    {
        if (isset($model)) {
            $this->model = $model;
        }

        if (isset($expires)) {
            $this->expires = $expires;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function create(UserInterface $user)
    {
        $activation = $this->createModel();

        $code = $this->generateActivationCode();

        $activation->fill(compact('CODE'));

        $activation->USER_ID = $user->getUserId();

        $activation->save();

        return $activation;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(UserInterface $user, $code = null)
    {
        $expires = $this->expires();

        $activation = $this
            ->createModel()
            ->newQuery()
            ->where('USER_ID', $user->getUserId())
            ->where('COMPLETED', false)
            ->where('IDATE', '>', $expires);

        if ($code) {
            $activation->where('CODE', $code);
        }

        return $activation->first() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(UserInterface $user, $code)
    {
        $expires = $this->expires();

        $activation = $this
            ->createModel()
            ->newQuery()
            ->where('USER_ID', $user->getUserId())
            ->where('CODE', $code)
            ->where('COMPLETED', false)
            ->where('IDATE', '>', $expires)
            ->first();

        if ($activation === null) {
            return false;
        }

        $activation->fill([
            'COMPLETED'    => true,
            'COMPLETED_AT' => Carbon::now(),
        ]);

        $activation->save();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function completed(UserInterface $user)
    {
        $activation = $this
            ->createModel()
            ->newQuery()
            ->where('USER_ID', $user->getUserId())
            ->where('COMPLETED', true)
            ->first();

        return $activation ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(UserInterface $user)
    {
        $activation = $this->completed($user);

        if ($activation === false) {
            return false;
        }

        return $activation->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function removeExpired()
    {
        $expires = $this->expires();

        return $this
            ->createModel()
            ->newQuery()
            ->where('COMPLETED', false)
            ->where('IDATE', '<', $expires)
            ->delete();
    }

    /**
     * Returns the expiration date.
     *
     * @return \Carbon\Carbon
     */
    protected function expires()
    {
        return Carbon::now()->subSeconds($this->expires);
    }

    /**
     * Return a random string for an activation code.
     *
     * @return string
     */
    protected function generateActivationCode()
    {
        return str_random(32);
    }
}
