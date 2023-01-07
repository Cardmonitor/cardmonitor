<?php

namespace App\Policies;

use App\Models\Articles\StoringHistory;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoringHistoryPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Articles\StoringHistory  $storing_history
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StoringHistory $storing_history)
    {
        return ($user->id == $storing_history->user_id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Articles\StoringHistory  $storing_history
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StoringHistory $storing_history)
    {
        return ($user->id == $storing_history->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Articles\StoringHistory  $storing_history
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StoringHistory $storing_history)
    {
        return ($user->id == $storing_history->user_id);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Articles\StoringHistory  $storing_history
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StoringHistory $storing_history)
    {
        return ($user->id == $storing_history->user_id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\Models\Articles\StoringHistory  $storing_history
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StoringHistory $storing_history)
    {
        return ($user->id == $storing_history->user_id);
    }
}
