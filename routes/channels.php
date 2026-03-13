<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('vendor.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id && $user->role === 'vendor';
});

Broadcast::channel('customer.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id && $user->role === 'customer';
});
