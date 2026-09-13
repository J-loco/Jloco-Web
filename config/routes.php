<?php

declare(strict_types=1);

/**
 * Every page of the portal. Names are used by url() in templates and controllers.
 * Legacy "?page=" links are redirected by src/Http/LegacyUrls.php.
 */

use StarLoco\Web\Controller\AccountController;
use StarLoco\Web\Controller\AdminController;
use StarLoco\Web\Controller\AuthController;
use StarLoco\Web\Controller\DropController;
use StarLoco\Web\Controller\HomeController;
use StarLoco\Web\Controller\LadderController;
use StarLoco\Web\Controller\PageController;
use StarLoco\Web\Controller\ShopController;
use StarLoco\Web\Controller\VoteController;
use StarLoco\Web\Http\Route;

return [
    Route::get('/', 'home', [HomeController::class, 'index']),
    Route::get('/join', 'join', [PageController::class, 'join']),
    Route::get('/terms', 'terms', [PageController::class, 'terms']),
    Route::get('/forum-news', 'forum_news', [PageController::class, 'forumNews']),

    Route::get('/ladder', 'ladder', [LadderController::class, 'pvm']),
    Route::get('/ladder/pvp', 'ladder_pvp', [LadderController::class, 'pvp']),
    Route::get('/ladder/guilds', 'ladder_guilds', [LadderController::class, 'guilds']),
    Route::get('/ladder/jobs', 'ladder_jobs', [LadderController::class, 'jobs']),
    Route::get('/ladder/jobs/{job:\d+}', 'ladder_job', [LadderController::class, 'job']),
    Route::get('/ladder/votes', 'ladder_votes', [LadderController::class, 'votes']),
    Route::get('/drops', 'drops', [DropController::class, 'index']),

    Route::form('/login', 'login', [AuthController::class, 'login']),
    Route::post('/logout', 'logout', [AuthController::class, 'logout']),
    Route::form('/register', 'register', [AuthController::class, 'register']),
    Route::form('/password/reset', 'password_reset', [AuthController::class, 'passwordReset']),
    Route::form('/password/reset/{selector:[a-f0-9]{24}}/{token:[a-f0-9]{64}}', 'password_reset_confirm', [AuthController::class, 'passwordResetConfirm']),
    Route::get('/captcha.png', 'captcha', [AuthController::class, 'captcha']),

    Route::get('/account', 'account', [AccountController::class, 'show']),
    // The Dedipass widget builds its own form: no CSRF token, the code is verified with Dedipass.
    Route::post('/account', 'account_dedipass', [AccountController::class, 'dedipass'], csrf: false),
    Route::post('/account/privacy/{flag:armory|position}', 'account_privacy', [AccountController::class, 'privacy']),
    Route::post('/account/password', 'account_password', [AccountController::class, 'password']),

    Route::get('/vote', 'vote', [VoteController::class, 'show']),
    Route::post('/vote', 'vote_submit', [VoteController::class, 'vote']),

    Route::get('/shop', 'shop', [ShopController::class, 'index']),
    Route::get('/shop/{server:\d+}', 'shop_server', [ShopController::class, 'server']),
    Route::get('/shop/{server:\d+}/{category:\d+}', 'shop_category', [ShopController::class, 'category']),
    Route::get('/shop/{server:\d+}/items/{template:\d+}', 'shop_item', [ShopController::class, 'item']),
    Route::post('/shop/{server:\d+}/items/{template:\d+}', 'shop_buy', [ShopController::class, 'buy']),

    Route::get('/admin', 'admin', [AdminController::class, 'index']),
    Route::post('/admin/news', 'admin_news_create', [AdminController::class, 'createNews']),
    Route::post('/admin/news/{id:\d+}/delete', 'admin_news_delete', [AdminController::class, 'deleteNews']),
    Route::post('/admin/game-news', 'admin_game_news_create', [AdminController::class, 'createGameNews']),
    Route::post('/admin/game-news/{id:\d+}/delete', 'admin_game_news_delete', [AdminController::class, 'deleteGameNews']),
];
