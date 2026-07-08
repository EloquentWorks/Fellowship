# Events

Fellowship dispatches lifecycle events when this config value is enabled:

```php
'dispatch_events' => true,
```

Disable events with:

```php
'dispatch_events' => false,
```

## Available events

| Event | When it fires |
| --- | --- |
| `FellowshipRequestSent` | A friend request is sent or resent |
| `FellowshipRequestAccepted` | A pending request is accepted |
| `FellowshipRequestDenied` | A pending request is denied |
| `FellowshipRequestCanceled` | A sent pending request is canceled |
| `FellowshipRequestExpired` | A pending request expires |
| `FellowshipRemoved` | An accepted friendship is removed |
| `UserBlocked` | A user is blocked |
| `UserUnblocked` | A user is unblocked |

Each event receives the related Fellowship model:

```php
public function __construct(
    public Fellowship $fellowship
) {
}
```

## Listening for events

Register listeners in your application:

```php
use EloquentWorks\Fellowship\Events\FellowshipRequestSent;
use App\Listeners\SendFriendRequestNotification;

protected $listen = [
    FellowshipRequestSent::class => [
        SendFriendRequestNotification::class,
    ],
];
```

## Example listener

```php
<?php

namespace App\Listeners;

use EloquentWorks\Fellowship\Events\FellowshipRequestSent;

class SendFriendRequestNotification
{
    public function handle(FellowshipRequestSent $event): void
    {
        $sender = $event->fellowship->sender;
        $recipient = $event->fellowship->recipient;

        // Send a notification...
    }
}
```

## Event imports

```php
use EloquentWorks\Fellowship\Events\FellowshipRemoved;
use EloquentWorks\Fellowship\Events\FellowshipRequestAccepted;
use EloquentWorks\Fellowship\Events\FellowshipRequestCanceled;
use EloquentWorks\Fellowship\Events\FellowshipRequestDenied;
use EloquentWorks\Fellowship\Events\FellowshipRequestExpired;
use EloquentWorks\Fellowship\Events\FellowshipRequestSent;
use EloquentWorks\Fellowship\Events\UserBlocked;
use EloquentWorks\Fellowship\Events\UserUnblocked;
```
