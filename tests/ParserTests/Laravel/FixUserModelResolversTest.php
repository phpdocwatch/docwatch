<?php

use DocWatch\Parsers\Laravel\FixUserModelResolvers;

test('parser can return the user model class for request user method', function () {
    $parser = new FixUserModelResolvers();
    $docs = $parser->standalone();
    
    $expect = <<<EOL
namespace Illuminate\Http;
/**
 * @method \App\Models\User|null user() // [via FixUserModelResolvers]
 */
class Request
{
}
EOL;

    expect((string) $docs)->toBe($expect);
});

test('parser can return the user model class for auth user method', function () {
    $parser = new FixUserModelResolvers();
    $parser->withConfig([
        'request' => 'Illuminate\Support\Facades\Auth',
        'static' => true,
    ]);
    $docs = $parser->standalone();
    
    $expect = <<<EOL
namespace Illuminate\Support\Facades;
/**
 * @method static \App\Models\User|null user() // [via FixUserModelResolvers]
 */
class Auth
{
}
EOL;

    expect((string) $docs)->toBe($expect);
});


test('parser can return the user model class for custom request user method', function () {
    $parser = new FixUserModelResolvers();
    $parser->withConfig([
        'request' => 'App\\Http\\Requests\\AdminRequest',
        'model' => 'App\\Models\\Admin',
        'nullable' => false,
    ]);
    $docs = $parser->standalone();
    
    $expect = <<<EOL
namespace App\Http\Requests;
/**
 * @method \App\Models\Admin user() // [via FixUserModelResolvers]
 */
class AdminRequest
{
}
EOL;

    expect((string) $docs)->toBe($expect);
});
