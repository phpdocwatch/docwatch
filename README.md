# DocWatch

This plugin produces a bit more insight into your eloquent models, and provides systems like Intelephense with more context.


## Installation

```bash
$ composer require docwatch/docwatch --dev
$ artisan vendor:publish --tag=docwatch
```

## Usage

1a: Manually trigger docblock generation:

```bash
$ artisan docwatch:generate

# or
$ artisan docwatch:generate app/My/Custom/Models/Directory
```

1b: Automatically watch for changes and have it automatically generate docblocks:

```bash
$ node ./vendor/docwatch/watch.js
```

2: Or you may wish to view docblock information without generating anything:

```bash
$ artisan docwatch:info

# or
$ artisan docwatch:info app/My/Custom/Models/Directory
```

3: Clear the docwatch file (where all docblocks reside)

```bash
$ artisan docwatch:clear
```

4: Get current status of your app incl. DocWatch (version, last generated at, output file, etc):

```bash
$ artisan about
```

## Features


### Suggests database fields

It will suggest all available database fields based on `artisan db:table` in conjunction with custom casts specified (via `getCasts()`).

Example:

```
class Product
{
    public $casts = [
        'price' => \App\Casts\Price::class
    ];
}

// Straight from DB
$product->id; // knows this is: int
$product->name; // knows this is: string

// Custom Casts
$product->price; // knows this is: \App\Casts\Price
```


### Suggests virtual accessor fields

It will suggest virtual accessor fields based on accessor methods that match the `get{Field}Attribute` naming convention, or ones that have a return type that inherits `\Illuminate\Database\Eloquent\Casts\Attribute` class (required for reflection), and, if the `get: fn()` has a return type that will be suggested.

Example:

```
class Product
{
    public function getPriceFormattedCurrencyAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function priceFormatted(): Attribute
    {
        return new Attribute(
            get: fn (): string => number_format($this->price, 2),
        );
    }
}

// Old-style accessor attribute
$product->price_formatted_currency; // knows this is: string

// New-style accessor attribute
$product->price_formatted; // knows this is: string
```


### Suggests relation fields

It will suggest relation fields based on relation methods that have a return type that inherits `\Illuminate\Database\Eloquent\Relations\Relation` class (required for reflection).

Example:

```
class Product
{
    public function categories(): BelongsToMany // return required :/
    {
        return $this->belongsToMany(Category::class);
    }
}

$product->categories; // knows this is: Collection<int,Category>

foreach ($product->categories as $categories) {
    $category; // knows this is: Category
}
```


### Suggests scoped methods

It will suggest statically-accessible scopes based on methods that match the format `scope{Blah}` format that have a first parameter that inherits `\Illuminate\Database\Eloquent\Builder`.

Example:

```
class Category
{
    public function scopeAddedByUser(Builder $query, User|int|null $user = null)
    {
        $user = ($user === null) ? Auth::user() : $user;
        $user = ($user instanceof User) ? $user->id : (int) $user;

        $query->where('user_id', $user);
    }

    public function scopeForList(Builder $query): array
    {
        return $query->get()->pluck('name', 'id')->toArray();
    }
}

Category::addedByUser(); // knows this is: static::addedByUser(User|int|null $user = null): Builder
Category::forList(); // knows this is: static::forList(): array
```


### (Optional): Helps with related query scopes

It can, depending on `docwatch.useProxiedQueryBuilders` config value, help "trick" your IDE/intelephense into knowing what scopes are available.

Example:

```
class Product
{
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}

class Brand
{
    public function scopeActive(Builder $query)
    {
        $query->where('active', true);
    }

    public function scopeSearch(Builder $query, string $search)
    {
        $query->where('name', 'LIKE', "%{$search}%");
    }
}

$product->brand()->active()->search($search); // knows this is: search(string $search): static
```

Note: This works quite well but isn't perfect. In this scenario what's happening behind the scenes is docwatch tells your IDE that the `brand` relation returns an instance of a `ProxiedQueries\App\Models\Brand\Builder` which extends the standard `Illuminate\Database\Eloquent\Builder` but has accessible methods `active()` and `search(string $search)` of which both return `static`. Your IDE/intelephense will then see two sources of truth: the `brand` method returns an instance of `BelongsTo` AND it returns an instance of `ProxiedQueries\App\Models\Brand\Builder` and offers you suggestions from both classes.

You should never reference a `ProxiedQueries\...\Builder` class in your code (typehints, inheritance, instanceof, etc) as the class does not exist except in docblock form!



## TODO

- Improve `static::query()` methods (like `static::where()`) so that it returns a `ProxiedQueries\...\Builder` instance
- Inside a proxied model query, update `first`, `createOrUpdate`, `findOrFail`, etc so that they return the relevant model instead of base `Model`
- Add tests in pest
    - Not sure if every relation currently works. Only tested a few including belongsTo, belongsToMany, hasMany, hasOne, morphMany.
- Add wider Laravel support -- currently restricted to recent versions of Laravel where `artisan db:table` is available.

## Notes

Still a WIP



## Issues or requests


Add an Issue or a PR


## Author

- Works great? Bradie Tilley
- Buggy? Not Bradie Tilley