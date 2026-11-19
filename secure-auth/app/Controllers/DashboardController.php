<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\OAuthAccount;
use App\Services\Auth;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = Auth::user();
        if ($user === null) {
            $this->redirect('/login');
        }
        $this->render('dashboard', [
            'title'     => 'Dashboard',
            'user'      => $user,
            'providers' => OAuthAccount::providersFor((int) $user['id']),
        ]);
    }
}
