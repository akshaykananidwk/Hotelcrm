<?php
/**
 * Application route table.
 *
 * @var App\Core\Router $router
 */

use App\Middleware\AuthMiddleware;
use App\Middleware\ApiAuthMiddleware;

// ---------------------------------------------------------------------------
// Guest / auth
// ---------------------------------------------------------------------------
$router->get('/', 'AuthController@root');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout');

// ---------------------------------------------------------------------------
// Authenticated admin panel
// ---------------------------------------------------------------------------
$router->group(['middleware' => [AuthMiddleware::class]], function ($r) {

    $r->get('/dashboard', 'DashboardController@index');

    // Hotels
    $r->get('/hotels', 'HotelController@index');
    $r->get('/hotels/create', 'HotelController@create');
    $r->post('/hotels', 'HotelController@store');
    $r->get('/hotels/{id}/edit', 'HotelController@edit');
    $r->put('/hotels/{id}', 'HotelController@update');
    $r->delete('/hotels/{id}', 'HotelController@destroy');

    // Room types
    $r->get('/room-types', 'RoomTypeController@index');
    $r->post('/room-types', 'RoomTypeController@store');
    $r->put('/room-types/{id}', 'RoomTypeController@update');
    $r->delete('/room-types/{id}', 'RoomTypeController@destroy');

    // Rooms
    $r->get('/rooms', 'RoomController@index');
    $r->post('/rooms', 'RoomController@store');
    $r->put('/rooms/{id}', 'RoomController@update');
    $r->put('/rooms/{id}/status', 'RoomController@updateStatus');
    $r->delete('/rooms/{id}', 'RoomController@destroy');

    // Reservations
    $r->get('/reservations', 'ReservationController@index');
    $r->get('/reservations/calendar', 'ReservationController@calendar');
    $r->get('/reservations/create', 'ReservationController@create');
    $r->post('/reservations', 'ReservationController@store');
    $r->get('/reservations/{id}', 'ReservationController@show');
    $r->put('/reservations/{id}', 'ReservationController@update');
    $r->post('/reservations/{id}/cancel', 'ReservationController@cancel');

    // Front office
    $r->get('/frontoffice', 'FrontOfficeController@index');
    $r->post('/frontoffice/{id}/checkin', 'FrontOfficeController@checkin');
    $r->post('/frontoffice/{id}/checkout', 'FrontOfficeController@checkout');
    $r->post('/frontoffice/{id}/room-change', 'FrontOfficeController@roomChange');

    // Housekeeping
    $r->get('/housekeeping', 'HousekeepingController@index');
    $r->post('/housekeeping/{id}/status', 'HousekeepingController@updateStatus');
    $r->post('/housekeeping/assign', 'HousekeepingController@assign');

    // Billing
    $r->get('/billing', 'BillingController@index');
    $r->get('/billing/create', 'BillingController@create');
    $r->post('/billing', 'BillingController@store');
    $r->get('/billing/{id}', 'BillingController@show');
    $r->get('/billing/{id}/pdf', 'BillingController@pdf');
    $r->post('/billing/{id}/payment', 'BillingController@addPayment');
    $r->post('/billing/{id}/email', 'BillingController@email');

    // Guests / CRM
    $r->get('/guests', 'GuestController@index');
    $r->get('/guests/create', 'GuestController@create');
    $r->post('/guests', 'GuestController@store');
    $r->get('/guests/{id}', 'GuestController@show');
    $r->put('/guests/{id}', 'GuestController@update');

    // Reports
    $r->get('/reports', 'ReportController@index');
    $r->get('/reports/{type}', 'ReportController@show');
    $r->get('/reports/{type}/export', 'ReportController@export');

    // Channel manager
    $r->get('/channels', 'ChannelController@index');
    $r->post('/channels/{id}/sync', 'ChannelController@sync');

    // Users & roles
    $r->get('/users', 'UserController@index');
    $r->post('/users', 'UserController@store');
    $r->put('/users/{id}', 'UserController@update');
    $r->delete('/users/{id}', 'UserController@destroy');

    // Settings
    $r->get('/settings', 'SettingsController@index');
    $r->get('/settings/{group}', 'SettingsController@group');
    $r->post('/settings/{group}', 'SettingsController@save');

    // Logs
    $r->get('/logs/audit', 'LogController@audit');
    $r->get('/logs/activity', 'LogController@activity');

    // Backup
    $r->post('/backup', 'SettingsController@backup');
});

// ---------------------------------------------------------------------------
// REST API v1 (token authenticated)
// ---------------------------------------------------------------------------
$router->group(['prefix' => '/api/v1', 'middleware' => [ApiAuthMiddleware::class]], function ($r) {
    $r->get('/rooms', 'Api\\RoomApiController@index');
    $r->get('/rooms/availability', 'Api\\RoomApiController@availability');

    $r->get('/bookings', 'Api\\BookingApiController@index');
    $r->post('/bookings', 'Api\\BookingApiController@store');
    $r->get('/bookings/{id}', 'Api\\BookingApiController@show');
    $r->put('/bookings/{id}', 'Api\\BookingApiController@update');
    $r->delete('/bookings/{id}', 'Api\\BookingApiController@cancel');

    $r->get('/guests', 'Api\\GuestApiController@index');
    $r->post('/guests', 'Api\\GuestApiController@store');

    $r->get('/invoices', 'Api\\BillingApiController@index');
    $r->get('/invoices/{id}', 'Api\\BillingApiController@show');
    $r->post('/payments', 'Api\\BillingApiController@payment');

    $r->get('/reports/{type}', 'Api\\ReportApiController@show');
});

// Public booking-engine + OTA webhook endpoints (no bearer token required;
// OTA webhooks are verified by signature inside the controller).
$router->group(['prefix' => '/api/public'], function ($r) {
    $r->get('/availability', 'Api\\PublicBookingController@availability');
    $r->post('/book', 'Api\\PublicBookingController@book');
    $r->post('/ota/{channel}/webhook', 'Api\\OtaWebhookController@handle');
});
