<?php

namespace Illuminate\Foundation\Http;

class FormRequest
{
    public $attributes;
    public $request;
    public $query;
    public $server;
    public $files;
    public $cookies;
    public $headers;
    public $content;
    public $languages;
    public $charsets;
    public $encodings;
    public $acceptableContentTypes;
    public $pathInfo;
    public $requestUri;
    public $baseUrl;
    public $basePath;
    public $method;
    public $format;
    public $session;
    public $locale;
    public $defaultLocale;
    public $json;
    public $convertedFiles;
    public $userResolver;
    public $routeResolver;
    public $container;
    public $redirector;
    public $redirect;
    public $redirectRoute;
    public $redirectAction;
    public $errorBag;
    public $stopOnFirstFailure;
    public $validator;
}