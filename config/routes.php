<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use App\Middleware\JwtAuthenticationMiddleware;
use Psr\Container\ContainerInterface;
/**
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/:id', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 * 
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */
return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {

    // Auth (public)
    $app->route('/api/auth/token', App\Handler\Auth\TokenHandler::class, ['POST']);
    $app->route('/api/auth/refresh', [App\Handler\Auth\RefreshHandler::class], ['POST']);
    $app->route('/api/auth/logout', [App\Handler\Auth\LogoutHandler::class], ['GET']);
    $app->route('/api/auth/session', [JwtAuthenticationMiddleware::class, App\Handler\Auth\SessionUpdateHandler::class], ['POST']);
    $app->route('/api/auth/resetPassword', [App\Handler\Auth\ResetPasswordHandler::class], ['POST']);
    $app->route('/api/auth/checkResetCode', [App\Handler\Auth\CheckResetCodeHandler::class], ['GET']);
    $app->route('/api/auth/changePassword', [App\Handler\Auth\ChangePasswordHandler::class], ['POST']);
    
    $auth = [
        JwtAuthenticationMiddleware::class,
        Mezzio\Authorization\AuthorizationMiddleware::class,
    ];
    // Account (private)
    $app->route('/api/account/findMe', [...$auth, ...[App\Handler\Account\FindMeHandler::class]], ['GET']);
    $app->route('/api/account/update', [...$auth, ...[App\Handler\Account\UpdateHandler::class]], ['PUT']);
    $app->route('/api/account/updatePassword', [...$auth, ...[App\Handler\Account\UpdatePasswordHandler::class]], ['PUT']);

    // Roles (private)
    $app->route('/api/roles/create', [...$auth, ...[App\Handler\Roles\CreateHandler::class]], ['POST']);
    $app->route('/api/roles/update/:roleId', [...$auth, ...[App\Handler\Roles\UpdateHandler::class]], ['PUT']);
    $app->route('/api/roles/delete/:roleId', [...$auth, ...[App\Handler\Roles\DeleteHandler::class]], ['DELETE']);
    $app->route('/api/roles/findAll', [App\Handler\Roles\FindAllHandler::class], ['GET']);
    $app->route('/api/roles/findAllByPaging', [...$auth, ...[App\Handler\Roles\FindAllByPagingHandler::class]], ['GET']);
    $app->route('/api/roles/findOneById/:roleId', [...$auth, ...[App\Handler\Roles\FindOneByIdHandler::class]], ['GET']);

    // Users (private)
    $app->route('/api/users/create', [...$auth, [App\Handler\Users\CreateHandler::class]], ['POST']);
    $app->route('/api/users/update/:userId', [...$auth, [App\Handler\Users\UpdateHandler::class]], ['PUT']);
    $app->route('/api/users/delete/:userId', [...$auth, [App\Handler\Users\DeleteHandler::class]], ['DELETE']);
    $app->route('/api/users/updatePassword/:userId', [...$auth, [App\Handler\Users\UpdatePasswordHandler::class]], ['PUT']);
    $app->route('/api/users/findAll', [...$auth, [App\Handler\Users\FindAllHandler::class]], ['GET']);
    $app->route('/api/users/findAllByPaging', [...$auth, [App\Handler\Users\FindAllByPagingHandler::class]], ['GET']);
    $app->route('/api/users/findOneById/:userId', [...$auth, [App\Handler\Users\FindOneByIdHandler::class]], ['GET']);

    // Avatars (private)
    $app->route('/api/avatars/findOneById/:userId', [...$auth, [App\Handler\Users\FindOneByIdHandler::class]], ['GET']);
    $app->route('/api/avatars/update/:userId', [...$auth, [App\Handler\Users\UpdateHandler::class]], ['PUT']);

    // Permissions (private)
    $app->route('/api/permissions/create', [...$auth, [App\Handler\Permissions\CreateHandler::class]], ['POST']);
    $app->route('/api/permissions/copy/:permId', [...$auth, [App\Handler\Permissions\CopyHandler::class]], ['POST']);
    $app->route('/api/permissions/update/:permId', [...$auth, [App\Handler\Permissions\UpdateHandler::class]], ['PUT']);
    $app->route('/api/permissions/delete/:permId', [...$auth, [App\Handler\Permissions\DeleteHandler::class]], ['DELETE']);
    $app->route('/api/permissions/findAll', [JwtAuthenticationMiddleware::class, App\Handler\Permissions\FindAllHandler::class], ['GET']);
    $app->route('/api/permissions/findAllByPaging', [...$auth, [App\Handler\Permissions\FindAllByPagingHandler::class]], ['GET']);

    // FailedLogins (private)
    $app->route('/api/failedlogins/findAllByPaging', [...$auth, [App\Handler\FailedLogins\FindAllByPagingHandler::class]], ['GET']);
    $app->route('/api/failedloginips/findAll', [...$auth, [App\Handler\FailedLogins\FindAllIpAdressesHandler::class]], ['GET']);
    $app->route('/api/failedloginusernames/findAll', [...$auth, [App\Handler\FailedLogins\FindAllUsernamesHandler::class]], ['GET']);

    // Common (public)
    // 
    $app->route('/api/stream/events', App\Handler\Common\Stream\EventsHandler::class, ['GET']);
    $app->route('/api/locales/findAll', App\Handler\Common\Locales\FindAllHandler::class, ['GET']);
    $app->route('/api/years/findAll', App\Handler\Common\Years\FindAllHandler::class, ['GET']);
    $app->route('/api/months/findAll', App\Handler\Common\Months\FindAllHandler::class, ['GET']);
    $app->route('/api/cities/findAll', App\Handler\Common\Cities\FindAllHandler::class, ['GET']);
    $app->route('/api/countries/findAll', App\Handler\Common\Countries\FindAllHandler::class, ['GET']);
    $app->route('/api/currencies/findAll', App\Handler\Common\Currencies\FindAllHandler::class, ['GET']);
    $app->route('/api/areacodes/findAll', App\Handler\Common\AreaCodes\FindAllHandler::class, ['GET']);
    $app->route('/api/files/findOneById/:fileId', App\Handler\Common\Files\FindOneByIdHandler::class, ['GET']);
    $app->route('/api/files/readOneById/:fileId', App\Handler\Common\Files\ReadOneByIdHandler::class, ['GET']);

};
