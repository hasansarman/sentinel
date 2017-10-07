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

namespace Cartalyst\Sentinel\Reminders;

use Carbon\Carbon;
use Cartalyst\Sentinel\Users\UserInterface;
use Cartalyst\Sentinel\Users\UserRepositoryInterface;
use Cartalyst\Support\Traits\RepositoryTrait;

class IlluminateReminderRepository implements ReminderRepositoryInterface
{
    use RepositoryTrait;

    /**
     * The user repository.
     *
     * @var \Cartalyst\Sentinel\Users\UserRepositoryInterface
     */
    protected $users;

    /**
     * The Eloquent reminder model name.
     *
     * @var string
     */
    protected $model = 'Cartalyst\Sentinel\Reminders\EloquentReminder';

    /**
     * The expiration time in seconds.
     *
     * @var int
     */
    protected $expires = 259200;

    /**
     * Create a new Illuminate reminder repository.
     *
     * @param  \Cartalyst\Sentinel\Users\UserRepositoryInterface  $users
     * @param  string  $model
     * @param  int  $expires
     * @return void
     */
    public function __construct(UserRepositoryInterface $users, $model = null, $expires = null)
    {
        $this->users = $users;

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
        $reminder = $this->createModel();

        $code = $this->generateReminderCode();

        $reminder->fill([
            'CODE'      => $code,
            'COMPLETED' => false,
        ]);

        $reminder->USER_ID = $user->getUserId();

        $reminder->save();

        return $reminder;
    }

    /**
     * {@inheritDoc}
     */
    public function exists(UserInterface $user, $code = null)
    {
        $expires = $this->expires();

        $reminder = $this
            ->createModel()
            ->newQuery()
            ->where('USER_ID', $user->getUserId())
            ->where('COMPLETED', false)
            ->where('IDATE', '>', $expires);

        if ($code) {
            $reminder->where('CODE', $code);
        }

        return $reminder->first() ?: false;
    }

    /**
     * {@inheritDoc}
     */
    public function complete(UserInterface $user, $code, $password)
    {
        $expires = $this->expires();

        $reminder = $this
            ->createModel()
            ->newQuery()
            ->where('USER_ID', $user->getUserId())
            ->where('CODE', $code)
            ->where('COMPLETED', false)
            ->where('IDATE', '>', $expires)
            ->first();

        if ($reminder === null) {
            return false;
        }

        $credentials = compact('PASSWORD');

        $valid = $this->users->validForUpdate($user, $credentials);

        if ($valid === false) {
            return false;
        }

        $this->users->update($user, $credentials);

        $reminder->fill([
            'COMPLETED'    => true,
            'COMPLETED_AT' => Carbon::now(),
        ]);

        $reminder->save();

        return true;
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
     * Returns a random string for a reminder code.
     *
     * @return string
     */
    protected function generateReminderCode()
    {
        return str_random(32);
    }
}
