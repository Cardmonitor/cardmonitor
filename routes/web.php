<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::view('/impressum', 'impressum');

Route::post('/contact', 'ContactController@store')->middleware(['honey']);

Route::get('order/{order}/images', 'Images\ImageableController@index')->name('order.images.index');

Route::post('deploy', 'DeploymentController@store');

Auth::routes([
    'register' => false,
]);

Route::middleware(['auth'])->group(function () {

    Route::get('login/{provider}', [\App\Http\Controllers\ProviderController::class, 'redirectToProvider'])->name('login.provider.redirect');
    Route::get('login/{provider}/callback', [\App\Http\Controllers\ProviderController::class, 'handleProviderCallback'])->name('login.provider.callback');
    Route::delete('login/{provider}', [\App\Http\Controllers\ProviderController::class, 'destroy'])->name('login.provider.destroy');

    Route::get('/card/export', 'Cards\Export\CsvController@index')->name('card.export.csv.index');
    Route::post('/card/export', 'Cards\Export\CsvController@store')->name('card.export.csv.store');

    Route::get('/cardmarket/create', 'Cardmarket\CallbackController@create')->name('cardmarket.callback.create');
    Route::get('/cardmarket/callback/{request_token}', 'Cardmarket\CallbackController@store')->name('cardmarket.callback.store');
    Route::get('/cardmarket/callback', 'Cardmarket\CallbackController@update')->name('cardmarket.callback.update');
    Route::delete('/cardmarket/callback', 'Cardmarket\CallbackController@destroy')->name('cardmarket.callback.destroy');

    Route::put('/cardmarket/product/{card}', 'Cardmarket\Products\PriceController@update')->name('cardmarket.product.price.update');

    Route::get('/', 'HomeController@index');
    Route::get('/home', 'HomeController@index')->name('home');
    Route::get('/home/article', 'Home\Articles\ArticleController@index');
    Route::get('/home/order/month/{year}/{month}', 'Home\Orders\MonthController@index')->name('home.order.month');
    Route::get('/home/order/year/{year}', 'Home\Orders\YearController@index')->name('home.order.year');

    Route::put('api/{api}', [\App\Http\Controllers\Apis\ApiController::class, 'update'])->name('api.update');
    Route::delete('api/{api}', [\App\Http\Controllers\Apis\ApiController::class, 'destroy'])->name('api.destroy');

    Route::get('article/sync', 'Cardmarket\Articles\ArticleController@index')->name('article.sync.index');

    Route::post('article/import', 'Articles\Imports\ImportController@store')->name('article.import.store');

    Route::get('article/number', 'Articles\NumberController@index')->name('article.number.index');
    Route::put('article/{article}/number', 'Articles\NumberController@update')->name('article.number.update');
    Route::post('article/action', 'Articles\ActionController@store')->name('article.action.store');

    Route::get('article/storing_history', 'Articles\StoringHistoryController@index')->name('article.storing_history.index');
    Route::get('article/storing_history/{storing_history}', 'Articles\StoringHistoryController@show')->name('article.storing_history.show');
    Route::get('article/storing_history/{storing_history}/pdf', 'Articles\StoringHistory\PdfController@show')->name('article.storing_history.pdf.show');
    Route::post('article/storing_history/{storing_history}/pdf', 'Articles\StoringHistory\PdfController@store')->name('article.storing_history.pdf.store');

    Route::get('article/stock/logfile', [\App\Http\Controllers\Articles\Stock\LogFileController::class, 'index'])->name('article.stock.logfile.index');

    Route::get('article/{article}/cardmarket', [\App\Http\Controllers\Cardmarket\Articles\ArticleController::class, 'show'])->name('article.cardmarket.show');
    Route::put('article/{article}/cardmarket', [\App\Http\Controllers\Cardmarket\Articles\ArticleController::class, 'update'])->name('article.cardmarket.update');

    Route::resource('article', 'Articles\ArticleController');

    Route::resource('card', 'Cards\CardController');

    Route::get('expansions/{expansion}/cardmarket', [\App\Http\Controllers\Cardmarket\Expansions\ExpansionController::class, 'show'])->name('expansions.cardmarket.show');

    Route::get('expansions', [\App\Http\Controllers\ExpansionController::class, 'index'])->name('expansions.index');
    Route::post('expansions', [\App\Http\Controllers\ExpansionController::class, 'store'])->name('expansions.store');
    Route::get('expansions/{expansion}', [\App\Http\Controllers\ExpansionController::class, 'show'])->name('expansions.show');
    Route::put('expansions/{expansion}', [\App\Http\Controllers\ExpansionController::class, 'update'])->name('expansions.update');

    Route::post('item/reload', 'Items\ReloadController@store');
    Route::resource('item', 'Items\ItemController');

    Route::resource('image', 'Images\ImageController')->only([
        'index',
        'destroy',
    ]);

    Route::post('order/export/download', 'Orders\Export\DownloadController@store');
    Route::get('order/export/dropbox', 'Orders\Export\DropboxController@index');

    Route::post('order/import/sent', 'Orders\Import\SentController@store');

    Route::get('order/picklist/{view?}', 'Orders\Picklists\PicklistController@index')->name('order.picklist.index');

    Route::post('order/{order}/images', 'Images\ImageableController@store')->name('order.images.store');

    Route::get('order/sync', [\App\Http\Controllers\Cardmarket\Orders\OrderController::class, 'index'])->name('order.sync.index');
    Route::put('order/sync', [\App\Http\Controllers\Cardmarket\Orders\OrderController::class, 'update'])->name('order.sync.update');
    Route::get('order/{order}/cardmarket', [\App\Http\Controllers\Cardmarket\Orders\OrderController::class, 'show'])->name('order.cardmarket.show');

    Route::resource('order', 'Orders\OrderController')->except([
        'create',
        'store',
        'delete',
    ]);

    Route::post('order/{order}/send', 'Cardmarket\Orders\SendController@store')->name('order.send.store');
    Route::get('order/{order}/message/create', 'Cardmarket\Orders\MessageController@create')->name('order.message.create');
    Route::post('order/{order}/message', 'Cardmarket\Orders\MessageController@store')->name('order.message.store');
    Route::put('order/{order}/sync', 'Cardmarket\Orders\OrderController@update')->name('order.sync.update');

    Route::post('order/{order}/transactions', 'Orders\TransactionController@store');
    Route::put('order/{order}/transactions/{transaction}', 'Orders\TransactionController@update');
    Route::delete('order/{order}/transactions/{transaction}', 'Orders\TransactionController@destroy');

    Route::put('order/{order}/articles/state', [\App\Http\Controllers\Orders\Articles\StateController::class, 'update'])->name('order.articles.state.update');

    Route::get('priceguide/{game}', 'PriceguideController@show');

    Route::post('purchases/{order}/sellable', [\App\Http\Controllers\Orders\Purchases\SellableController::class, 'store'])->name('purchases.sellable.store');
    Route::post('purchases/{order}/cancel', [\App\Http\Controllers\Orders\Purchases\CancelController::class, 'store'])->name('purchases.cancel.store');

    Route::get('purchases/{order}/pdf', [\App\Http\Controllers\Orders\Purchases\PdfController::class, 'show'])->name('purchases.pdf.show');

    Route::get('purchases', [\App\Http\Controllers\Orders\PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('purchases/{order}', [\App\Http\Controllers\Orders\PurchaseController::class, 'show'])->name('purchases.show');
    Route::put('purchases/{order}', [\App\Http\Controllers\Orders\PurchaseController::class, 'update'])->name('purchases.update');
    Route::get('purchases/{order}/edit', [\App\Http\Controllers\Orders\PurchaseController::class, 'edit'])->name('purchases.edit');

    Route::get('rule/apply', 'Rules\ApplyController@index');
    Route::post('rule/apply', 'Rules\ApplyController@store');
    Route::put('rule/sort', 'Rules\SortController@update');
    Route::post('rule/{rule}/activate', 'Rules\ActiveController@store');
    Route::delete('rule/{rule}/activate', 'Rules\ActiveController@destroy');
    Route::resource('rule', 'Rules\RuleController');

    Route::post('storages/assign', 'Storages\AssignController@store');
    Route::resource('storages', 'Storages\StorageController');
    Route::put('storages/{storage}/parent', 'Storages\ParentController@update');

    Route::resource('content', 'Storages\ContentController')->except([
        'index',
        'store',
    ]);
    Route::get('storages/{storage}/content', 'Storages\ContentController@index')->name('storage.content.index');
    Route::post('storages/{storage}/content', 'Storages\ContentController@store')->name('storage.content.store');

    Route::get('transaction/{item}', 'Items\Transactions\TransactionController@index')->name('transaction.index');
    Route::post('transaction/{item}', 'Items\Transactions\TransactionController@store')->name('transaction.store');
    Route::resource('transaction', 'Items\Transactions\TransactionController')->except([
        'index',
        'store',
    ]);

    Route::resource('quantity', 'Items\QuantityController')->except([
        'index',
        'store',
    ]);
    Route::get('item/{item}/quantity', 'Items\QuantityController@index')->name('quantity.index');
    Route::post('item/{item}/quantity', 'Items\QuantityController@store')->name('quantity.store');

    Route::get('/user/reset', 'Users\ResetController@index')->name('user.index');

    Route::get('/user/backgroundtasks', [\App\Http\Controllers\Users\BackgroundTaskController::class, 'index'])->name('user.backgroundtasks.index');
    Route::post('/user/backgroundtasks', [\App\Http\Controllers\Users\BackgroundTaskController::class, 'store'])->name('user.backgroundtasks.store');
    Route::get('/user/backgroundtasks/{task}', [\App\Http\Controllers\Users\BackgroundTaskController::class, 'show'])->name('user.backgroundtasks.show');
    Route::delete('/user/backgroundtasks/{task}', [\App\Http\Controllers\Users\BackgroundTaskController::class, 'destroy'])->name('user.backgroundtasks.destroy');

    Route::get('/user/settings', 'Users\UserController@edit')->name('user.edit');
    Route::put('/user/settings', 'Users\UserController@update')->name('user.update');
    Route::post('/user/settings/api_token', 'Users\ApiTokenController@store')->name('user.api_token.store');
    Route::put('/user/settings/api_token', 'Users\ApiTokenController@update')->name('user.api_token.update');

    Route::get('/woocommerce/order', 'WooCommerce\OrderController@index')->name('woocommerce.order.index');
    Route::post('/woocommerce/order', 'WooCommerce\OrderController@store')->name('woocommerce.order.store');
    Route::get('/woocommerce/order/{id}', [\App\Http\Controllers\WooCommerce\OrderController::class, 'show'])->name('woocommerce.order.show');

});
