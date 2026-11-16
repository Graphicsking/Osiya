<?php

namespace App\Http\Controllers;

use App\Models\Interest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function loginForm()
    {
        return Auth::user()?->is_admin ? redirect()->route('admin.index') : view('admin.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt(['email' => strtolower($data['email']), 'password' => $data['password'], 'is_admin' => true])) {
            throw ValidationException::withMessages(['email' => 'These sign-in details could not be verified.']);
        }
        $request->session()->regenerate();

        return redirect()->route('admin.index');
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'interest' => ['nullable', Rule::in(Interest::OPTIONS)],
            'status' => ['nullable', Rule::in(['new', 'contacted'])],
            'view' => ['nullable', Rule::in(['cards', 'table'])],
        ]);
        $query = Interest::query();
        if ($search = $filters['q'] ?? null) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'));
        }
        if ($category = $filters['interest'] ?? null) $query->where('interest', $category);
        if ($status = $filters['status'] ?? null) $query->where('status', $status);

        return view('admin.index', [
            'interests' => $query->latest('id')->paginate(20)->withQueryString(),
            'options' => Interest::OPTIONS,
            'displayView' => $filters['view'] ?? 'cards',
            'total' => Interest::count(),
            'newCount' => Interest::where('status', 'new')->count(),
            'today' => Interest::whereDate('created_at', today())->count(),
            'volunteers' => Interest::where('interest', 'Volunteer')->count(),
        ]);
    }

    public function update(Request $request, Interest $interest)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'contacted'])]]);
        $interest->status = $data['status'];
        $interest->save();

        return back()->with('status', 'Submission updated.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
