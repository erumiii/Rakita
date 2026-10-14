<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index()
    {
        // Post-context reads run as library_app under RLS (Task 6 ruling).
        $members = User::on('pgsql_app')->where('role', 'member')
            ->withCount(['borrowings as active_borrowings_count' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->paginate(15);

        return view('employee.members.index', compact('members'));
    }

    public function update(UpdateMemberRequest $request, User $member)
    {
        abort_unless($member->role === 'member', 404);

        $data = $request->validated();
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $member->update($data);

        return redirect('/employee/members')->with('success', 'Member updated.');
    }
}
