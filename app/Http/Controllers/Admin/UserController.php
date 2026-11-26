<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Admin hanya melihat angka statistik, bukan isi chat atau foto pengguna.
     */
    public function index(Request $request)
    {
        $term = trim((string) $request->query('q'));

        $users = User::query()
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->withCount(['meals', 'scans', 'chatConversations'])
            ->orderByDesc('last_login_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', ['users' => $users, 'term' => $term]);
    }
}
