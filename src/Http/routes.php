<?php

use Illuminate\Support\Facades\Route;
use NiklasSchmitt\Saml2\Http\Controllers\Saml2Controller;

Route::group([
    'prefix' => config('saml2.routesPrefix'),
    'middleware' => array_merge(['saml2.resolveTenant'], (array) config('saml2.routesMiddleware', [])),
], function () {
    Route::get('/{uuid}/logout', [Saml2Controller::class, 'logout'])->name('saml.logout');
    Route::get('/{uuid}/login', [Saml2Controller::class, 'login'])->name('saml.login');
    Route::get('/{uuid}/metadata', [Saml2Controller::class, 'metadata'])->name('saml.metadata');
    Route::post('/{uuid}/acs', [Saml2Controller::class, 'acs'])->name('saml.acs');
    Route::match(['GET', 'POST'], '/{uuid}/sls', [Saml2Controller::class, 'sls'])->name('saml.sls');
});
