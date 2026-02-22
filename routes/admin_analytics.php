// Admin Analytics Routes
Route::prefix('admin')->middleware(['auth:admin'])->name('admin.')->group(function () {
    Route::get('/analytics', [App\Http\Controllers\Admin\AdminAnalyticsController::class, 'dashboard'])
        ->name('analytics.dashboard');
    
    Route::get('/analytics/revenue', [App\Http\Controllers\Admin\AdminAnalyticsController::class, 'revenue'])
        ->name('analytics.revenue');
    
    Route::get('/analytics/subscriptions', [App\Http\Controllers\Admin\AdminAnalyticsController::class, 'subscriptions'])
        ->name('analytics.subscriptions');
    
    Route::get('/analytics/users', [App\Http\Controllers\Admin\AdminAnalyticsController::class, 'users'])
        ->name('analytics.users');
    
    Route::get('/analytics/applications', [App\Http\Controllers\Admin\AdminAnalyticsController::class, 'applications'])
        ->name('analytics.applications');
    
    Route::get('/analytics/churn', [App\Http\Controllers\Admin\AdminAnalyticsController::class, 'churn'])
        ->name('analytics.churn');
    
    Route::get('/analytics/export', [App\Http\Controllers\Admin\AdminAnalyticsController::class, 'export'])
        ->name('analytics.export');
});
