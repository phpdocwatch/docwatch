# DocWatch

Work In Progress: This plugin produces a bit more insight into your eloquent models, and provides systems like Intelephense with more context by generating a separate file that details hidden "magic" methods and properties.


## Installation

```bash
$ composer require docwatch/docwatch --dev
$ artisan vendor:publish --tag=docwatch
```

## Usage

Manually trigger docblock generation:

```bash
$ artisan docwatch:generate

# or
$ artisan docwatch:generate
```

Automatically watch for changes and have it automatically generate docblocks:

```bash
$ artisan docwatch:watch
```

Clear the docwatch file (where all docblocks reside)

```bash
$ artisan docwatch:clear

# it also clears when your clear your application cache:

$ artisan cache:clear
```

4: Get current status of your app incl. DocWatch (version, last generated at, output file, etc):

```bash
$ artisan about
```

## Parsers provide features

Not all parsers are Laravel specific nor do they all require Laravel. Ignore the Laravel namespace for non-Laravel applications.


### > DocWatch\DocWatch\Parse\Laravel\DatabaseColumnsAsProperties

- **Suggests database fields as available properties for each scanned model**
- **Requires:** Laravel 9.3 or whenever `artisan db:table` was added (exactly version to be confirmed)


How this works: For each model it finds it will run `artisan db:table {table}` to get an understanding of each field as they exist in the database. The models' `getCasts()` method is run to get a greater understanding of how the fields are used in your application - any cast definition supersedes the presumed typehint gathered from the database.

Example:

```php
Schema::create('products', function ($table) {
    $table->id();
    $table->string('name');
    $table->decimal('price', 12, 2);
    $table->timestamps();
    $table->softDeletes();
});

class Product
{
    public $casts = [
        'price' => \App\Casts\Price::class
    ];
}

// Straight from DB
$product->id;             // typehint: int
$product->name;           // typehint: string
$product->created_at;     // typehint: \Carbon\Carbon
$product->updated_at;     // typehint: \Carbon\Carbon
$product->deleted_at;     // typehint: \Carbon\Carbon|null

// Custom Casts
$product->price;          // typehint: \App\Casts\Price
```

### > DocWatch\DocWatch\Parse\Laravel\RelationsAsProperties

- **Suggests relations are available properties for each scanned model**
- **Requires:** Laravel
- **Requires:** Relation methods to have a return typehint (of a Relation class)

How it works is it scans all models, reflects all methods, and identifies which methods have a return type hint of a class that is an instanceof Relation.

Example of reqiurement:

```php
class Product
{
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categoris'),
    }
}
```

Does not support union return types yet.

Some relations point to a specific model like `BelongsTo` does ones like `morphTo()` do not as they are polymorphic. The relation types that point to a model will be instantiated in the context of the model (`$model->{$relation}()`) which doesn't run any database queries but will create a relation query builder - with this we grab `$relation->getQuery()->getModel()` to understand the model that will be returned, otherwise standard `Model` class is typehinted as the return type.

Similarly, some relations return "many" records like `hasMany` or `belongsToMany`. These relations are typehinted as `\Illuminate\Support\Collection<{$model}>`.


```php
class Product
{
    public function categories(): BelongsToMany // return typehint is required for reflection :(
    {
        return $this->belongsToMany(Category::class);
    }
}

$product->categories;           // typehint: Collection<Category>
foreach ($product->categories as $category) {
    $category;                  // typehint: Category
}
```


### > DocWatch\DocWatch\Parse\Laravel\AccessorsAsProperties

 - **Converts virtual accessors to readable properties**
 - **Requires:** Laravel

It will suggest virtual accessor fields based on accessor methods that match the `get{Field}Attribute` naming convention (`oldStyle`), or ones that have a return type that inherits `\Illuminate\Database\Eloquent\Casts\Attribute` class (`newStyle`).

Typehints retrieved from the `oldStyle` are preserved and given to the property docblocks. Similarly, typehints on the `get: fn()` callback are also preserved and given tot the property docblocks.

Example:

```php
class Product
{
    public function getPriceFormattedCurrencyAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function nextShipmentAt(): Attribute
    {
        return new Attribute(
            get: fn (): Carbon => $this->last_shipment_at->copy()->addWeek(),
        );
    }
}

// Old-style accessor attribute
$product->price_formatted_currency; // typehint: string

// New-style accessor attribute
$product->next_shipment_at;         // typehint: \Carbon\Carbon
```

If `differentiateReadWrite` is `true` and only a "getter" is found (no setter with the same name is found) then the
docblock will be `@property-read` instead of `@property` indicating that you can only read from the proeprty. Similarly,
the opposite would yield `@property-write` indicating that you may only write to the property. 


### > DocWatch\DocWatch\Parse\Laravel\ScopesAsQueryBuilderMethods

 - **Converts Laravel scopes into static methods that can be seen against the model**
 - **Requires:** Laravel

How this works is it identifies all `scope{Name}` methods that have a first argument of the type `\Illuminate\Database\Eloquent\Builder`. With these methods, it'll "convert them" to static methods and drop the query builder parameter.

The return type is converted to an instance of `\ProxiedQueries\{ModelNamespace}\Builder` if the return type was void/missing or an instanceof `Builder`. Why? Because chaining scopes is impossible without a bit of context. Feel free to turn this option off or recreate your own `ScopesAsQueryBuilderMethods` parser if this annoys you.


```php
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

Category::addedByUser();                 // typehint: addedByUser(User|int|null $user = null): ProxiedQueries\App\Models\Category\Builder
Category::forList();                     // typehint: forList(): array
Category::addedByUser($user)->forList(); // typehint: forList(): array
```


### > DocWatch\DocWatch\Parse\Laravel\RelationsAsQueryBuilderMethods

 - **Converts Laravel relation return types from relation classes to query builders**
 - **Requires:** Laravel

How this works is it find all Relations (just like in `RelationsAsProperties`) except instead of `@property` docblocks it creates `@method` docblocks that tells your IDE that the return type of the relation *methods* (e.g. `$product->categories()`) is an
instance of `\ProxiedQueries\{ModelNamespace}\Builder`, which when run in conjunction with `ScopesAsQueryBuilderMethods` parser allows for cross-model scope knowledge.

```php
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

$product->brand()->active()->search($search); // typehint: search(string $search): Builder
```
Note: This works quite well but isn't perfect. In this scenario what's happening behind the scenes is docwatch tells your IDE that the `brand` relation returns an instance of a `ProxiedQueries\App\Models\Brand\Builder` which extends the standard `Illuminate\Database\Eloquent\Builder` but has accessible methods `active()` and `search(string $search)` of which both return `static`. Your IDE/intelephense will then see two sources of truth: the `brand` method returns an instance of `BelongsTo` AND it returns an instance of `ProxiedQueries\App\Models\Brand\Builder` and will likely **offer you suggestions from both classes**.

You should never reference a `ProxiedQueries\...\Builder` class in your code (typehints, inheritance, instanceof, etc) as the class does not exist except in docblock form!

## TODO

- Add return type of `ProxiedQueries\...\Builder` to all query builder entry points on a model (e.g. `query()`, `where()`, etc)
- Inside a proxied model query, update `first`, `createOrUpdate`, `findOrFail`, etc so that they return the relevant model instead of base `Model`
- Add tests in pest
    - Not sure if every relation currently works. Only tested a few including belongsTo, belongsToMany, hasMany, hasOne, morphMany.
- Add wider Laravel support -- currently restricted to recent versions of Laravel where `artisan db:table` is available.

## Notes

Still a WIP - can't stress that enough. Works, but may not work perfectly.


## Issues or requests


Add an Issue or a PR if you have any issues


## Author

- Bradie Tilley <https://github.com/bradietilley>
