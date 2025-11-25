<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['user-auth']], static function () {
    Route::get('/device/{device_id}/geofence', \App\Domains\Geofence\Controller\Index::class)->name('device.geofence.index');
    Route::post('/device/{device_id}/geofence/{geofence_id}/attach', \App\Domains\Geofence\Controller\Attach::class)->name('device.geofence.attach');
    Route::post('/device/{device_id}/geofence/{geofence_id}/detach', \App\Domains\Geofence\Controller\Detach::class)->name('device.geofence.detach');
    
    Route::get('/geofence/create', \App\Domains\Geofence\Controller\Create::class)->name('geofence.create');
    Route::post('/geofence/create', \App\Domains\Geofence\Controller\Store::class)->name('geofence.store');
    Route::get('/geofence/{id}', \App\Domains\Geofence\Controller\Update::class)->name('geofence.update');
    Route::patch('/geofence/{id}', \App\Domains\Geofence\Controller\Patch::class)->name('geofence.patch');
});
