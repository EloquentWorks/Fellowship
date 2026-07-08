# Route Snippet

Copy this into `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::fellowship();
```

Custom example:

```php
Route::fellowship([
    'prefix' => 'connections',
    'name' => 'connections.',
    'middleware' => ['web', 'auth', 'verified'],
]);
```
